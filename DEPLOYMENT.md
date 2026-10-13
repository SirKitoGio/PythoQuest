# Deploying PhytoQuest

PhytoQuest runs as Docker containers on one Linux server: the website, database, background
queue, live updates and the identification model. This guide uses the **AWS free tier**.
The same steps work on any Arm or x86 Ubuntu server, including Oracle Cloud's Always Free
tier (see the end).

```
                       ┌──────────────── AWS EC2 t4g.small (2 vCPU, 2 GB + 4 GB swap) ────────────────┐
 Browser ─ HTTPS 443 ─▶│ web (FrankenPHP + Caddy, auto HTTPS) ──▶ postgres                             │
   ▲  WebSocket /app ─▶│ reverb (live notifications)              queue ──▶ ml (BioCLIP identification) │
   └── live updates ◀──└────────────────────────────────────────────────────────────────────────────────┘
```

## What it costs

| Part | Where | Cost |
| --- | --- | --- |
| Everything (site, database, queue, live updates, model) | AWS EC2 `t4g.small` | Free 750 h/month **until 31 Dec 2026** under AWS's t4g trial |
| Public IP, 30 GB disk | AWS | About US$6/month, paid from new-account credits |
| Domain + HTTPS | sslip.io or DuckDNS + Let's Encrypt | Free |

**AWS "Free plan" accounts close after 6 months or when the credits run out.** Check the
banner in the AWS console. Before that date, either upgrade to a paid plan (roughly US$6/month
until 31 Dec 2026, then about US$15–20/month) or move to Oracle's Always Free tier.

> Hugging Face Docker Spaces now require a paid PRO plan, so the model runs on the same
> server instead. Pointing `ML_SERVICE_URL` at any other host that runs `ml-service/` also works.

---

## 1. Put the code on GitHub

The server pulls from your repository's `main` branch. Never commit `.env.production` or the
PFAF CSV; both are already in `.gitignore`.

## 2. AWS server

1. Sign up at <https://aws.amazon.com/free>. Before anything else, create a cost budget:
   **Billing → Budgets → Create budget → Monthly cost budget**, US$5, with your email.
2. Choose the region **Asia Pacific (Singapore) `ap-southeast-1`**.
3. **EC2 → Launch instance**:
   - Name: `phytoquest`
   - Image: **Ubuntu Server 24.04 LTS**, **64-bit (Arm)**
   - Type: **`t4g.small`**
   - Key pair: create one (`.pem`)
   - Security group:
     - SSH (22) from **My IP**
     - HTTP (80) and HTTPS (443) from anywhere
   - Storage: **30 GB gp3**
4. **EC2 → Elastic IPs → Allocate**, then **Associate** it with the instance. Use this **public** address from now on, not the `172.31.x.x` private one.
5. On your Mac, move the key into place and lock it down:

   ```bash
   mv ~/Downloads/phytoquest.pem ~/.ssh/ && chmod 400 ~/.ssh/phytoquest.pem
   ```

## 3. Free domain

- **No account needed: sslip.io.** For IP `54.251.133.46`, the name `phytoquest.54-251-133-46.sslip.io` works immediately (dashes instead of dots).
- **Nicer name: DuckDNS.** Create a subdomain at <https://www.duckdns.org> pointing at the Elastic IP, e.g. `phytoquest.duckdns.org`.

## 4. Prepare the server

```bash
ssh -i ~/.ssh/phytoquest.pem ubuntu@YOUR_ELASTIC_IP
```

Then, on the server:

```bash
# 4 GB of swap: the model needs headroom on a 2 GB machine
sudo fallocate -l 4G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Docker
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker ubuntu && exit
```

Reconnect with the same `ssh` command, then create the settings file. Replace `YOUR_DOMAIN`
first; the secrets are generated on the server and never leave it:

```bash
git clone https://github.com/SirKitoGio/PythoQuest.git phytoquest && cd phytoquest
cp .env.production.example .env.production && chmod 600 .env.production
DOMAIN=YOUR_DOMAIN
sed -i \
  -e "s|phytoquest\.duckdns\.org|$DOMAIN|g" \
  -e "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" \
  -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 16)|" \
  -e "s|^REVERB_APP_ID=.*|REVERB_APP_ID=$(shuf -i 100000-999999 -n 1)|" \
  -e "s|^REVERB_APP_KEY=.*|REVERB_APP_KEY=$(openssl rand -hex 10)|" \
  -e "s|^REVERB_APP_SECRET=.*|REVERB_APP_SECRET=$(openssl rand -hex 16)|" \
  -e "s|^ML_SERVICE_TOKEN=.*|ML_SERVICE_TOKEN=$(openssl rand -hex 24)|" \
  .env.production
grep -c CHANGE_ME .env.production   # should print 1 (the explanatory comment)
```

## 5. Start the site and load the plant data

```bash
alias dc='docker compose -f compose.production.yaml --env-file .env.production'
dc build web
dc up -d --no-build --wait web queue reverb postgres
```

The web container creates the database tables and obtains the HTTPS certificate on start.
Check that `https://YOUR_DOMAIN` loads with a padlock.

Upload the PFAF export from your Mac:

```bash
scp -i ~/.ssh/phytoquest.pem ~/Downloads/pfaf_plants_merged.csv ubuntu@YOUR_ELASTIC_IP:~/
```

Then, on the server:

```bash
dc cp ~/pfaf_plants_merged.csv web:/app/storage/app/private/imports/pfaf_plants_merged.csv
dc exec web php artisan db:seed --class=PlantSeeder --force
dc exec -d web php artisan phytoquest:cache-plant-images
```

This imports 4,131 PFAF species and the 20 Philippine pilot species, then downloads the plant
photos in the background. The download takes 20–40 minutes, and re-running the command resumes it.

Do **not** run the full `db:seed` in production: it creates demo accounts with the password `password`.

## 6. Identification model

The model image pre-computes embeddings for every species when it is built. That takes about
15 minutes on a Mac but over an hour on a `t4g.small`, so build it on an Apple Silicon Mac
(the same Arm architecture) and copy it to the server.

On your Mac, from the project folder, with Docker Desktop running:

```bash
# 1. Use the server's exact species list, so the pre-computed embeddings match it
ssh -i ~/.ssh/phytoquest.pem ubuntu@YOUR_ELASTIC_IP \
  'cd phytoquest && docker compose -f compose.production.yaml --env-file .env.production exec -T web sh -c "php artisan phytoquest:export-taxa >/dev/null && cat storage/app/private/taxa.json"' \
  > ml-service/data/taxa.json

# 2. Build (about 15 minutes) and copy to the server (about 1.1 GB)
docker build -t phytoquest-ml:latest ml-service
docker save phytoquest-ml:latest | gzip | ssh -i ~/.ssh/phytoquest.pem ubuntu@YOUR_ELASTIC_IP 'gunzip | docker load'
```

Then, on the server:

```bash
dc up -d --no-build --wait ml
dc up -d --no-build queue
```

On an x86 Mac or PC, build with `docker buildx build --platform linux/arm64 …` instead. That is
slower, because it emulates Arm.

## 7. Create your administrator

1. Sign up at `https://YOUR_DOMAIN/register`.
2. On the server, run:

   ```bash
   dc exec web php artisan phytoquest:make-admin you@example.com
   ```

3. Promote reviewers from **Manage users** in the site menu.

## 8. Check everything

- **Search:** plant search returns results; the map shows the campus.
- **Scanner:** upload a leaf photo; ranked matches appear within seconds.
- **Live notifications:** review a sighting from a reviewer account, and the researcher's bell updates without reloading.
- **Logs:** `dc logs -f web queue reverb ml`
- **Memory:** `free -h`. Swap use of 1–2 GB is normal with the model loaded.

## Updating

After pushing new commits to `main`, run on the server:

```bash
cd ~/phytoquest && ./deploy/deploy.sh
```

This rebuilds and restarts the website. Repeat step 6 only when `ml-service/` or the plant
catalogue changes.

## Backups

Run a nightly database dump, keeping 14 days:

```bash
mkdir -p ~/backups
( crontab -l 2>/dev/null; echo '0 18 * * * cd ~/phytoquest && docker compose -f compose.production.yaml --env-file .env.production exec -T postgres sh -c "pg_dump -U \$POSTGRES_USER \$POSTGRES_DB" | gzip > ~/backups/phytoquest-$(date +\%F).sql.gz && find ~/backups -mtime +14 -delete' ) | crontab -
```

Copy `~/backups` off the server occasionally. Uploaded photos live in the `phytoquest_storage` Docker volume.

## Things to know

- **HTTPS only.** Camera, GPS and offline saving need HTTPS, so always use the domain, never the raw IP.
- **Email:** password-reset emails go to the log until you set `MAIL_*` to an SMTP provider (Brevo and Resend have free tiers).
- **Plant data licence:** PFAF content and images are copyrighted. Check their terms before opening the site widely.

## Moving to Oracle Cloud Always Free (stays free)

Oracle's Always Free Arm VM (2 OCPU, 12 GB RAM) runs the same stack with room to spare.

1. Create an Ubuntu 24.04 **VM.Standard.A1.Flex** instance (2 OCPU / 12 GB).
2. In the VCN **security list**, allow TCP 80 and 443. Also open them in Ubuntu's firewall:

   ```bash
   sudo iptables -I INPUT 6 -p tcp -m multiport --dports 80,443 -j ACCEPT
   sudo netfilter-persistent save
   ```

3. Follow steps 3–8 above. The swap step is optional there, and with 12 GB you can build the model directly on the server: `dc build ml`.
4. To move existing data, restore a `pg_dump` into the new database and copy the `phytoquest_storage` volume.

A1 capacity is sometimes unavailable in busy regions; retry or choose another region.
