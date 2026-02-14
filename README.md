[![](https://avatars.githubusercontent.com/u/158830885?s=200&v=4)](https://www.liberu.co.uk)
# Liberu Social Network

![](https://img.shields.io/badge/PHP-8.5-informational?style=flat&logo=php&color=4f5b93)
![](https://img.shields.io/badge/Laravel-12-informational?style=flat&logo=laravel&color=ef3b2d)
![](https://img.shields.io/badge/Filament-5-informational?style=flat&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgeG1sbnM6dj0iaHR0cHM6Ly92ZWN0YS5pby9uYW5vIj48cGF0aCBkPSJNMCAwaDQ4djQ4SDBWMHoiIGZpbGw9IiNmNGIyNWUiLz48cGF0aCBkPSJNMjggN2wtMSA2LTMuNDM3LjgxM0wyMCAxNWwtMSAzaDZ2NWgtN2wtMyAxOEg4Yy41MTUtNS44NTMgMS40NTQtMTEuMzMgMy0xN0g4di01bDUtMSAuMjUtMy4yNUMxNCAxMSAxNCAxMSAxNS40MzggOC41NjMgMTkuNDI5IDYuMTI4IDIzLjQ0MiA2LjY4NyAyOCA3eiIgZmlsbD0iIzI4MjQxZSIvPjxwYXRoIGQ9Ik0zMCAxOGg0YzIuMjMzIDUuMzM0IDIuMjMzIDUuMzM0IDEuMTI1IDguNUwzNCAyOWMtLjE2OCAzLjIwOS0uMTY4IDMuMjA5IDAgNmwtMiAxIDEgM2gtNXYyaC0yYy44NzUtNy42MjUuODc1LTcuNjI1IDItMTFoMnYtMmgtMnYtMmwyLTF2LTQtM3oiIGZpbGw9IiMyYTIwMTIiLz48cGF0aCBkPSJNMzUuNTYzIDYuODEzQzM4IDcgMzggNyAzOSA4Yy4xODggMi40MzguMTg4IDIuNDM4IDAgNWwtMiAyYy0yLjYyNS0uMzc1LTIuNjI1LS4zNzUtNS0xLS42MjUtMi4zNzUtLjYyNS0yLjM3NS0xLTUgMi0yIDItMiA0LjU2My0yLjE4N3oiIGZpbGw9IiM0MDM5MzEiLz48cGF0aCBkPSJNMzAgMThoNGMyLjA1NSA1LjMxOSAyLjA1NSA1LjMxOSAxLjgxMyA4LjMxM0wzNSAyOGwtMyAxdi0ybC00IDF2LTJsMi0xdi00LTN6IiBmaWxsPSIjMzEyODFlIi8+PHBhdGggZD0iTTI5IDI3aDN2MmgydjJoLTJ2MmwtNC0xdi0yaDJsLTEtM3oiIGZpbGw9IiMxNTEzMTAiLz48cGF0aCBkPSJNMzAgMThoNHYzaC0ydjJsLTMgMSAxLTZ6IiBmaWxsPSIjNjA0YjMyIi8+PC9zdmc+&&color=fdae4b&link=https://filamentphp.com)
![](https://img.shields.io/badge/Livewire-4.1-informational?style=flat&logo=Livewire&color=fb70a9)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
![Open Source Love](https://img.shields.io/badge/Open%20Source-%E2%9D%A4-red.svg)


## Welcome to Liberu, our visionary open-source initiative that marries the power of Laravel 12, PHP 8.5 and Filament 5.2 to redefine the landscape of web development.

[![Contact us on WhatsApp](https://img.shields.io/badge/WhatsApp-25D366?style=for-the-badge&logo=whatsapp&logoColor=white)](https://wa.me/+447762430333)
[![YouTube](https://img.shields.io/badge/YouTube-%23FF0000.svg?style=for-the-badge&logo=YouTube&logoColor=white)](https://www.youtube.com/@liberusoftware)
[![Facebook](https://img.shields.io/badge/Facebook-%231877F2.svg?style=for-the-badge&logo=Facebook&logoColor=white)](https://www.facebook.com/liberusoftware)
[![Instagram](https://img.shields.io/badge/Instagram-%23E4405F.svg?style=for-the-badge&logo=Instagram&logoColor=white)](https://www.instagram.com/liberusoftware)
[![X](https://img.shields.io/badge/X-%23000000.svg?style=for-the-badge&logo=X&logoColor=white)](https://www.x.com/liberusoftware)
[![LinkedIn](https://img.shields.io/badge/linkedin-%230077B5.svg?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/company/liberugroup)
[![GitHub](https://img.shields.io/badge/github-%23121011.svg?style=for-the-badge&logo=github&logoColor=white)](https://www.github.com/liberusoftware)

[![Install](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/install.yml/badge.svg)](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/install.yml) [![Tests](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/tests.yml) [![Docker](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/main.yml/badge.svg)](https://github.com/liberu-social-network/social-network-laravel/actions/workflows/main.yml)


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
- **Admin panel (Filament)** with advanced features:
  - User management with role-based access control
  - Role and permission management via Filament Shield
  - Email verification management
  - Account status controls
  - Module management
  - Menu management
- Modular architecture for easy extensions

### Admin Panel

The admin panel is accessible at `/admin` and provides comprehensive management capabilities:

- **User Management**: Create, edit, and delete users with role assignment
- **Role & Permission System**: Powered by Spatie Laravel Permission and Filament Shield
  - Super Admin: Full system access
  - Admin: Standard administrative privileges
  - Panel User: Basic panel access
  - Free: Limited view-only permissions
- **Dashboard**: Overview of system statistics and quick actions
- **Module System**: Enable/disable application modules
- **Menu Management**: Configure navigation menus

For detailed documentation, see [Admin Panel User Management Guide](docs/ADMIN_PANEL_USER_MANAGEMENT.md)

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
