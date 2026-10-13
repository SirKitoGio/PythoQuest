# PhytoQuest

A location-aware botanical research support system (José Rizal University, A.Y. 2026-2027).

PhytoQuest supports research and education only. It does not diagnose, prescribe, recommend dosage or advise plant consumption.

## Architecture

| Component | Technology | Responsibility |
| --- | --- | --- |
| Web app + REST API | Laravel 13, Blade, Alpine, Tailwind | Auth, roles, plant profiles, sightings, review, history, notifications, audit log, `/api/v1` for the React client |
| Database | PostgreSQL 18 (`pg_trgm`, full-text search) | Authoritative records; typo-tolerant search over ~4,100 species |
| Event layer | Database queue + Laravel Reverb (WebSockets) | Background identification, review notifications pushed live |
| ML service | Python 3.12, FastAPI, BioCLIP (`ml-service/`) | Ranked species candidates with confidence scores |

## Features

- **Catalogue** (FR-01–04): 4,131 species imported from the Plants For A Future (PFAF) export plus 20 curated Philippine pilot species with Filipino names; search by common, scientific or family name (typos tolerated); filters for family, traditional use, plant part, toxins and IUCN status; every profile shows its source label, provider, link and retrieval date.
- **Scanner** (FR-05–07): upload a tagged plant-part photo; BioCLIP ranks candidates against the catalogue; low-confidence results ask for more images and can be escalated to an expert.
- **Sightings** (FR-08–11): GPS pin + required photo evidence; every submission is pending until expert review; community members can confirm records, but confirmations never approve on their own.
- **Location privacy**: exact pins only for trusted, public records of non-sensitive plants. Unverified, protected and Vulnerable+ species are generalized to a ~1 km grid, and private or rejected records never appear. Uploaded photos are re-encoded so EXIF GPS cannot leak.
- **Account features** (FR-12–14): search/scan/submission/confirmation history, favorites, live notifications, plant profiles saved for offline reading.
- **Roles**: researcher, expert reviewer (review queue, plant editing), admin (roles, audit log, deletions).

PFAF data policy: taxonomy, names, range, hazards and traditional medicinal actions are imported. Edible uses, preparation and propagation text are deliberately excluded. Traditional uses are labelled as such and are not presented as clinical evidence.

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for the Docker-based production setup (AWS free tier + Hugging Face Space, or a single always-free server).

## Setup

Requirements: PHP 8.3+, Composer, Node.js, PostgreSQL 18, [uv](https://docs.astral.sh/uv/).

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
createdb phytoquest                      # set DB_USERNAME/DB_PASSWORD in .env
cp /path/to/pfaf_plants_merged.csv storage/app/private/imports/
php artisan migrate:fresh --seed         # imports PFAF when the CSV is present, Faker sample data otherwise
php artisan storage:link
php artisan reverb:install               # generates REVERB_* keys if missing
php artisan phytoquest:export-taxa       # label set for the ML service
(cd ml-service && uv sync)
npm run build
composer run dev                         # web, queue, Reverb, Vite, logs and the ML service
```

The first ML start downloads BioCLIP (~600 MB) and encodes the catalogue (~90 s); later starts take seconds. Re-run `phytoquest:import-pfaf` or `phytoquest:export-taxa` at any time; both are idempotent.

## Demo accounts (password: `password`)

| Email | Role |
| --- | --- |
| keithalan.speirs25@gmail.com | Admin |
| reviewer@phytoquest.test | Expert reviewer |
| demo@phytoquest.test | Researcher |

## REST API (`/api/v1`)

Authenticate with `POST /auth/token` (`email`, `password`, `device_name`) and send `Authorization: Bearer <token>`. First-party pages can also use the session cookie.

| Method & path | Access | Purpose |
| --- | --- | --- |
| `GET /plants?q=&family=&action=&part=&toxins=&conservation=` | public | Search and filter the catalogue |
| `GET /plants/{id}` | public | Full profile with traditional uses and provenance |
| `GET /map/sightings?bbox=&plant_id=&status=` | public | GeoJSON with per-viewer location precision |
| `GET /vocabulary` | public | Controlled vocabularies for filters and forms |
| `GET /me`, `DELETE /auth/token` | user | Current user, revoke token |
| `GET/POST/PUT/DELETE /sightings` | owner | Submit and manage own sightings |
| `POST/DELETE /sightings/{id}/confirmation` | user | Community confirmation |
| `POST /identifications` → `202` | user | Start a scan; poll `GET /identifications/{id}` or listen for `.identification.updated` on `private-App.Models.User.{id}` |
| `POST /identifications/{id}/escalation` | owner | Ask an expert |
| `GET /favorites`, `PUT/DELETE /favorites/{plant}` | user | Favorites |
| `GET/PATCH /notifications` | user | List, mark all read |
| `GET /review/sightings`, `POST /review/sightings/{id}` | reviewer | Review queue and decisions |

## Tests

```bash
php artisan test                 # Laravel (SQLite in-memory, PFAF fixture in tests/fixtures)
(cd ml-service && uv run pytest) # ML service (mock backend, offline)
```
