# Liberu Social Network

[![Install](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/install.yml/badge.svg)](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/install.yml) [![Tests](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/tests.yml) [![Docker](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/main.yml/badge.svg)](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/main.yml)

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT) ![](https://img.shields.io/badge/PHP-8.4-informational?style=flat&logo=php&color=4f5b93) ![](https://img.shields.io/badge/Laravel-12-informational?style=flat&logo=laravel&color=ef3b2d)

A modular Laravel-based social network skeleton focused on extensibility, privacy, and developer productivity.

- Modern stack: Laravel 12, Jetstream, Livewire 3, Filament
- Optional: RoadRunner/Octane for performance
- Batteries included: profiles, messaging, notifications, admin panel

## Quick start

Requirements: PHP 8.3+, Composer, (optional) Docker.

1. Copy environment and install dependencies

```bash
cp .env.example .env
composer install
php artisan key:generate
```

2. Configure `.env` (DB, mail, app URL), then run migrations and seeders

```bash
php artisan migrate --seed
```

3. Run locally

- Using PHP built-in server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

- Using Docker (build & run):

```bash
docker build -t liberu-laravel .
docker run -p 8000:8000 liberu-laravel
```

- Using Laravel Sail:

```bash
./vendor/bin/sail up
```

Notes:
- The provided `setup.sh` automates install and seeding; it may overwrite `.env` if you consent.
- Ensure `.env` DB settings are correct before running migrations.

## Features

- Authentication (Jetstream)
- Profiles with avatars and bios
- Private messaging
- Real-time notifications
- Search (users, posts)
- Admin panel (Filament)
- Modular architecture for easy extensions

## Related projects

A curated list of projects from the Liberu organization:

| Project | Repository |
|---|---|
| Accounting | https://github.com/liberu-accounting/accounting-laravel |
| Automation | https://github.com/liberu-automation/automation-laravel |
| Billing | https://github.com/liberu-billing/billing-laravel |
| Boilerplate | https://github.com/liberusoftware/boilerplate |
| Browser Game | https://github.com/liberu-browser-game/browser-game-laravel |
| CMS | https://github.com/liberu-cms/cms-laravel |
| Control Panel | https://github.com/liberu-control-panel/control-panel-laravel |
| CRM | https://github.com/liberu-crm/crm-laravel |
| eCommerce | https://github.com/liberu-ecommerce/ecommerce-laravel |
| Genealogy | https://github.com/liberu-genealogy/genealogy-laravel |
| Maintenance | https://github.com/liberu-maintenance/maintenance-laravel |
| Real Estate | https://github.com/liberu-real-estate/real-estate-laravel |
| Social Network | https://github.com/liberu-social-network/social-network-laravel |

## Contributing

Contributions are welcome. Please fork the repo, create a feature branch, and open a pull request. For major changes, open an issue first to discuss your approach.

## License

This project is licensed under the MIT License — see the LICENSE file for details.

---
Maintained by Liberu — https://liberu.co.uk
