# PhytoQuest

A location-aware botanical research support system, built with Laravel 13, Blade, Laravel Breeze and Tailwind CSS for the ITCC3101 Midterm Practical Exam (José Rizal University, A.Y. 2026-2027).

PhytoQuest supports research and education only. It does not diagnose, prescribe, recommend dosage or advise plant consumption.

## Features

- Public pages: Home, About, Contact, plant search and plant profiles
- Authentication (Laravel Breeze): register, log in, log out, profile, password reset
- Plants CRUD: anyone can browse; logged-in users can create, edit and delete
- Sightings CRUD (the "scanner"): logged-in users log sightings with a photo, plant part, GPS coordinates and location status. New and edited sightings are always `pending` until review, and users can only see or change their own.
- Dashboard with counts and recent sightings

## Setup

Requirements: PHP 8.3+, Composer, Node.js and npm.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan storage:link
npm install && npm run build
php artisan serve
```

Open http://127.0.0.1:8000.

## Demo account

| Email | Password |
| --- | --- |
| demo@phytoquest.test | password |

## Database

| Table | Seeder | Rows |
| --- | --- | --- |
| `users` | `UserSeeder` | 10 (demo account + 9 Faker users) |
| `plants` | `PlantSeeder` | 12 Philippine plants from `PlantFactory` |
| `sightings` | `SightingSeeder` | 19, around the JRU campus |

`DatabaseSeeder` calls all three seeders.

## Project map

- Routes: `routes/web.php`
- Controllers: `app/Http/Controllers/{PageController,PlantController,SightingController}.php`
- Validation: `app/Http/Requests/{StorePlantRequest,StoreSightingRequest}.php`
- Models: `app/Models/{Plant,Sighting,User}.php`
- Migrations, factories, seeders: `database/`
- Views: `resources/views/` (`home`, `about`, `contact`, `dashboard`, `plants/*`, `sightings/*`, `auth/*`)
- Design tokens (colours and fonts): `tailwind.config.js`

## Tests

```bash
php artisan test
```
