# Logistics Core SaaS

Logistics Core SaaS is a Laravel backend foundation for multi-tenant delivery, merchant, warehouse, driver, and shipment operations. The project focuses on clean architecture, strong tenant isolation, token-based authentication, and fast bitwise authorization for high-throughput logistics workflows.

## Project Strengths

- **Clean architecture first:** core authentication operations are isolated in action classes instead of fat controllers.
- **Multi-tenant database design:** tenants own users, warehouses, shipments, and related operational data.
- **Tenant data isolation:** scoped Eloquent models automatically restrict authenticated queries to the current tenant.
- **Bitwise permission system:** permissions are stored as an integer mask for fast authorization checks.
- **Sanctum API authentication:** login issues bearer tokens and logout revokes the current token.
- **Strict API contracts:** feature tests validate response structures, status codes, tokens, permissions, and database side effects.
- **Production-minded testing:** PHPUnit plus Paratest support sequential and parallel test execution.
- **CI-ready workflow:** GitHub Actions run formatting, static analysis, and parallel tests for pull requests.

## Tech Stack

- PHP 8.5
- Laravel 13
- Laravel Sanctum
- PostgreSQL
- Redis
- Docker Compose / Laravel Sail
- PHPUnit 12
- Paratest
- Laravel Pint

## Core Domain

The current backend foundation includes:

- Tenants for logistics companies
- Users with tenant membership and status control
- Merchant profiles with pickup information
- Warehouses scoped to tenants
- Shipments connected to merchants, drivers, warehouses, and tenants
- Shipment logs for status transitions
- Personal access tokens for API authentication

## Permission Model

Permissions are defined in `App\Enums\Permission` and stored on users as `permissions_mask`.

| Permission | Value | Purpose |
| --- | ---: | --- |
| `CREATE_SHIPMENT` | 1 | Merchant shipment creation |
| `VIEW_SHIPMENT` | 2 | Shared shipment visibility |
| `SORT_PACKAGES` | 4 | Warehouse package sorting |
| `ASSIGN_DRIVERS` | 8 | Admin or management dispatch |
| `DELIVER_SHIPMENT` | 16 | Driver delivery workflow |
| `MANAGE_TENANT` | 32 | Tenant administration |

Default masks:

- Merchant: `CREATE_SHIPMENT | VIEW_SHIPMENT`
- Driver: `VIEW_SHIPMENT | DELIVER_SHIPMENT`
- Warehouse: `VIEW_SHIPMENT | SORT_PACKAGES`

Routes can use the permission middleware:

```php
Route::middleware(['auth:sanctum', 'permission:CREATE_SHIPMENT'])->group(function () {
    // Protected logistics endpoints
});
```

## API Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/auth/register-company` | Register a tenant company and admin user |
| `POST` | `/api/auth/register-merchant` | Register a merchant user and merchant profile |
| `POST` | `/api/auth/login` | Issue a Sanctum bearer token |
| `GET` | `/api/auth/me` | Return the authenticated user |
| `POST` | `/api/auth/logout` | Revoke the current bearer token |

## Quick Start

Clone the repository and install dependencies:

```bash
git clone git@github.com:YousefBZo/logistics-core-saas.git
cd logistics-core-saas
composer install
cp .env.example .env
```

Start the Docker services:

```bash
docker compose up -d
```

Generate the application key and migrate the database:

```bash
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate
```

Run the test suite:

```bash
docker compose exec laravel.test composer test
docker compose exec laravel.test php artisan test --parallel --processes=2
```

Check formatting:

```bash
docker compose exec laravel.test ./vendor/bin/pint --test
```

## Example API Usage

Register a logistics company:

```bash
curl -X POST http://localhost/api/auth/register-company \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "Acme Logistics",
    "subdomain": "acme-hub",
    "name": "Acme Admin",
    "email": "admin@example.com",
    "phone": "+15550100001",
    "password": "password-secret",
    "password_confirmation": "password-secret"
  }'
```

Log in:

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password-secret"
  }'
```

Use the returned token:

```bash
curl http://localhost/api/auth/me \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## Testing Strategy

The test suite covers:

- Permission bit values and default masks
- Company registration contracts and database writes
- Merchant registration contracts and merchant profile creation
- Duplicate identity validation
- Login token issuance
- Suspended user login rejection
- Authenticated `/api/auth/me` and logout behavior
- Permission middleware allow, deny, and developer-error paths
- Tenant-scoped query isolation

## GitFlow

This repository follows a GitFlow-style workflow:

- `main` is the production-ready branch.
- `develop` is the integration branch.
- Feature branches should use professional slugs, for example `feature/auth-db-bitwise-permissions`.
- Pull requests should merge feature branches into `develop`.
- Release-ready `develop` changes can then be merged into `main`.

## Current Status

The project currently provides the backend foundation for authentication, tenant-aware database structure, and authorization. Shipment creation and operational logistics workflows can now be built on top of this tested base.
