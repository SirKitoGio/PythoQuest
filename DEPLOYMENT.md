# Deploying PhytoQuest

PhytoQuest runs as Docker containers. The same files work on any Linux server; this guide
uses the **AWS free tier** for the website and a **free Hugging Face Space** for the
identification model.

```
                          ┌──────────────── AWS EC2 t4g.small (free trial) ────────────────┐
 Browser ── HTTPS 443 ──▶ │  web (FrankenPHP + Caddy, auto HTTPS)  ──▶ postgres (data)     │
   ▲   WebSocket /app ──▶ │  reverb (live notifications)              queue (background)   │
   │                      └────────────────────────────────────────────────┬───────────────┘
   └──────── live updates ◀──────────────────────────────────────────────── │ HTTPS + token
                                                                            ▼
                                                  Hugging Face Space (free CPU, 16 GB)
                                                  ml-service: BioCLIP identification
```

## What it costs

| Part | Where | Cost |
| --- | --- | --- |
| Website, database, queue, live updates | AWS EC2 `t4g.small` (2 vCPU, 2 GB) | Free (750 h/month) **until 31 Dec 2026** under AWS's t4g trial |
| Public IP, 30 GB disk | AWS | About US$6/month, paid from the US$100–200 new-account credits |
| Identification model | Hugging Face Space, CPU basic | Free (sleeps after 48 h without use) |
| Domain + HTTPS certificate | DuckDNS + Let's Encrypt | Free |

After 31 Dec 2026 the AWS server costs roughly US$12–20/month (check your region). To stay
at $0 long-term, move the same stack to Oracle Cloud's Always Free tier (see the end of this
guide). Set an AWS budget alert before launching anything (step 2).

---

## 1. Put the code on GitHub

The server pulls from your repository, so commit everything, including `Dockerfile`,
`compose.production.yaml` and `deploy/`, and push to `main`.

Never commit `.env.production` or the PFAF CSV. Both are already in `.gitignore`.

## 2. Hugging Face Space (identification model)

1. Create a free account at <https://huggingface.co>, then **New Space**:
   name `phytoquest-ml`, SDK **Docker** (blank template), hardware **CPU basic (free)**, visibility **Public**.
2. Open the Space's **Settings → Variables and secrets → New secret**:
   - Name: `ML_API_TOKEN`
   - Value: a long random string. Generate one with `openssl rand -hex 24` and keep it; the server uses the same value.
3. Export the species list on your laptop (it must match the catalogue you import in step 6):

   ```bash
   php artisan phytoquest:export-taxa
   ```

4. Push the service to the Space (replace `YOUR_HF_USERNAME`; use an HF access token with *write* scope as the password):

   ```bash
   git clone https://huggingface.co/spaces/YOUR_HF_USERNAME/phytoquest-ml /tmp/phytoquest-ml
   rsync -a --exclude .venv --exclude cache --exclude .gitignore --exclude __pycache__ ml-service/ /tmp/phytoquest-ml/
   cd /tmp/phytoquest-ml && git add -A && git commit -m "Deploy PhytoQuest ML service" && git push
   ```

5. The first build takes 15–25 minutes, because it downloads BioCLIP and pre-computes embeddings for every species. When the Space shows **Running**, check it:

   ```bash
   curl https://YOUR_HF_USERNAME-phytoquest-ml.hf.space/health
   ```

   Expect `"status":"ok"` and `"taxa_count":4138`.

## 3. AWS account and server

1. Sign up at <https://aws.amazon.com/free> (choose the **Free plan**). Then, before anything else:
   **Billing → Budgets → Create budget → Zero spend / monthly cost budget of US$1** with your email.
2. Choose the region **Asia Pacific (Singapore) `ap-southeast-1`**. It is closest to the Philippines.
3. **EC2 → Launch instance**:
   - Name: `phytoquest`
   - Image: **Ubuntu Server 24.04 LTS**, architecture **64-bit (Arm)**
   - Instance type: **`t4g.small`**
   - Key pair: create one, download the `.pem`
   - Network settings → security group, allow:
     - SSH (22) from **My IP**
     - HTTP (80) and HTTPS (443) from **Anywhere**
   - Storage: **30 GB gp3**
4. **EC2 → Elastic IPs → Allocate**, then **Associate** it with the instance. This gives the server an address that survives restarts.

## 4. Free domain

1. Sign in at <https://www.duckdns.org>, create a subdomain (e.g. `phytoquest`), and set its IP to your Elastic IP.
2. Your site will be `https://phytoquest.duckdns.org`.
3. Students can instead claim a free `.me` domain from the GitHub Student Developer Pack (Namecheap) and add an **A record** pointing to the Elastic IP.

## 5. Prepare the server

```bash
ssh -i phytoquest.pem ubuntu@YOUR_ELASTIC_IP

# 2 GB swap so image builds never run out of memory
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Docker
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker ubuntu && exit
```

Reconnect with the same `ssh -i …` command so the docker group takes effect, then:

```bash
git clone https://github.com/SirKitoGio/PythoQuest.git phytoquest && cd phytoquest
cp .env.production.example .env.production
nano .env.production
```

Fill in every `CHANGE_ME`:

| Setting | Value |
| --- | --- |
| `APP_URL`, `SERVER_NAME`, `REVERB_CLIENT_HOST`, `MAIL_FROM_ADDRESS` | your domain |
| `APP_KEY` | `echo "base64:$(openssl rand -base64 32)"` |
| `DB_PASSWORD`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | `openssl rand -hex 16` each |
| `REVERB_APP_ID` | any number, e.g. `482910` |
| `ML_SERVICE_URL` | `https://YOUR_HF_USERNAME-phytoquest-ml.hf.space` |
| `ML_SERVICE_TOKEN` | the same value as the Space's `ML_API_TOKEN` |
| `ACME_EMAIL` | your email (for certificate expiry notices) |

## 6. Start it and load the plant data

```bash
docker compose -f compose.production.yaml --env-file .env.production up -d --build --wait
```

The web container creates the database tables on start; the queue and live-update workers start once it is healthy.

Upload the PFAF export from your laptop, then import it on the server:

```bash
# on your laptop
scp -i phytoquest.pem ~/Downloads/pfaf_plants_merged.csv ubuntu@YOUR_ELASTIC_IP:~/

# on the server, inside ~/phytoquest
docker compose -f compose.production.yaml --env-file .env.production cp ~/pfaf_plants_merged.csv web:/app/storage/app/private/imports/pfaf_plants_merged.csv
docker compose -f compose.production.yaml --env-file .env.production exec web php artisan db:seed --class=PlantSeeder --force
```

Then download the plant photos to the server once. pfaf.org is slow and drops requests
under load, so serving our own copies keeps pages fast. This takes 20–40 minutes; it is
safe to re-run, and it resumes where it stopped:

```bash
docker compose -f compose.production.yaml --env-file .env.production exec -d web php artisan phytoquest:cache-plant-images
```

This imports 4,131 PFAF species plus the 20 Philippine pilot species. Do **not** run the
full `db:seed` in production: it creates demo accounts with the password `password`.

Create your administrator:

1. Open `https://YOUR_DOMAIN/register` and sign up.
2. On the server, run:

   ```bash
   docker compose -f compose.production.yaml --env-file .env.production exec web php artisan phytoquest:make-admin you@example.com
   ```

3. Promote reviewers from **Manage users** in the site menu.

## 7. Check everything

- `https://YOUR_DOMAIN` loads with a padlock (the first HTTPS certificate can take a minute).
- Plants search returns results, and the map shows the campus.
- **Scanner:** upload a leaf photo. If the Space was asleep, the first result can take up to a few minutes while it wakes; the page updates on its own.
- **Live notifications:** with one browser logged in as a researcher and another as a reviewer, review a sighting; the researcher's bell updates without reloading.
- Logs: `docker compose -f compose.production.yaml --env-file .env.production logs -f web queue reverb`

## Updating

After pushing new commits to GitHub, run on the server:

```bash
cd ~/phytoquest && ./deploy/deploy.sh
```

If the plant catalogue changes, re-export the taxa and push to the Space again (step 2.3–2.4).

## Backups

Run a nightly database dump, keeping 14 days:

```bash
mkdir -p ~/backups
( crontab -l 2>/dev/null; echo '0 18 * * * cd ~/phytoquest && docker compose -f compose.production.yaml --env-file .env.production exec -T postgres sh -c "pg_dump -U \$POSTGRES_USER \$POSTGRES_DB" | gzip > ~/backups/phytoquest-$(date +\%F).sql.gz && find ~/backups -mtime +14 -delete' ) | crontab -
```

Copy `~/backups` off the server occasionally. Uploaded photos live in the `phytoquest_storage` Docker volume.

## Things to know

- **Camera, GPS and offline saving only work over HTTPS.** Always use the domain, never the raw IP.
- **Email:** password-reset emails are only written to the log until you add an SMTP provider (Brevo and Resend have free tiers) in `MAIL_*`.
- **Plant data licence:** PFAF content and images are copyrighted. Check their terms before opening the site to the public.
- **Hugging Face Space sleep:** a free Space sleeps after 48 hours without requests. Scans made while it wakes are retried automatically for about 4 minutes.

## Alternative: everything on one always-free server (Oracle Cloud)

Oracle's Always Free tier offers an Arm VM with 2 OCPUs and 12 GB RAM, enough to run the model alongside the site.

1. Create an Ubuntu 24.04 **VM.Standard.A1.Flex** instance (2 OCPU / 12 GB).
2. In the VCN **security list**, add ingress rules for TCP 80 and 443. Oracle's Ubuntu image also blocks them in its firewall, so open them there too:

   ```bash
   sudo iptables -I INPUT 6 -p tcp -m multiport --dports 80,443 -j ACCEPT
   sudo netfilter-persistent save
   ```

3. Follow steps 4–7 above. In `.env.production`, set:
   - `COMPOSE_PROFILES=ml`
   - `ML_SERVICE_URL=http://ml:8001`
   - `ML_SERVICE_TOKEN` to any random value

   Skip the Hugging Face step.
4. After the PFAF import, run `docker compose … exec web php artisan phytoquest:export-taxa` and then `docker compose … restart ml`, so the model ranks against the full catalogue.

Oracle may reclaim Always Free instances that sit idle for long periods, and A1 capacity is sometimes unavailable in busy regions; retry or pick another region.
