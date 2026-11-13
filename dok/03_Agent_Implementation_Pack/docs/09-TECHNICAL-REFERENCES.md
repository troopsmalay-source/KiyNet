# 09 — Technical References and Assumptions

## Repository baseline sources
Public repository checked: https://github.com/troopsmalay-source/KiyNet

Useful paths inspected during preparation:
- `composer.json` — Laravel/PHP dependency baseline.
- `package.json` — Vite/Tailwind/Alpine/Axios/DataTables.
- `.env.example` — PostgreSQL default connection.
- `routes/web.php` and `bootstrap/app.php` — current routing/bootstrap.
- `app/Models/User.php` and role middleware — current user/role/partner structure.
- customer, partner, package, subscription and billing controllers.
- `BillingInvoiceRepository.php` and mock JSON — legacy/session-backed invoice path.

Repository inspection from this preparation was limited to public source files available to browsing. Agent A's Phase 0 local clone audit supersedes this document if it finds changed branch content, unobserved migrations, or different runtime versions.

## Official technical documentation
- Laravel 12 Sanctum: https://laravel.com/docs/12.x/sanctum
- Laravel 12 Authentication: https://laravel.com/docs/12.x/authentication
- Laravel 12 Queues: https://laravel.com/docs/12.x/queues
- Laravel 12 Database: https://laravel.com/docs/12.x/database
- Laravel 12 Migrations: https://laravel.com/docs/12.x/migrations
- Laravel 12 Authorization / Policies: https://laravel.com/docs/12.x/authorization
- OpenAPI 3.1: https://spec.openapis.org/oas/v3.1.0
- FreeRADIUS SQL module: https://www.freeradius.org/documentation/freeradius-server/4.0.0/reference/raddb/mods-available/sql.html
- MikroTik RouterOS RADIUS manual: https://manual.mikrotik.com/docs/authentication-authorization-accounting/radius/
- MikroTik PPP AAA manual: https://manual.mikrotik.com/docs/authentication-authorization-accounting/ppp-aaa/

## Version and compatibility warnings
- Laravel 12 and PHP ^8.2 were visible in repo dependency metadata at preparation time. Confirm actual lockfile/PHP runtime in Phase 0.
- FreeRADIUS 3.x/4.x configuration and SQL details may differ. Pin a version and use matching schema and docs.
- MikroTik RouterOS v7 documentation currently describes supported RADIUS attributes and accounting behavior. Validate exact target RouterOS version/model in lab.
- PostgreSQL support in FreeRADIUS SQL modules is documented, but connection driver, schema naming, SQL dialect, query customization and DB privileges must be validated with pinned versions.
- Sanctum abilities do not replace application Policy/tenant ownership checks.
- Tax categories and rates in this project are configurable administration data. Obtain formal legal/tax approval before using calculation results for filing or statutory decisions.
