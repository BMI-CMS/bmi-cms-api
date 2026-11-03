---
name: laravel-artisan-make
description: Use whenever a new file is created in this Laravel project (model, migration, controller, form request, resource, service, repository, query class, command, seeder, factory, test, middleware, policy, exception, enum, and similar). Generate the file with `php artisan make:*` first, then edit the generated file; never write a new class file from scratch when an artisan generator exists.
---

# Create new files with artisan

When a task needs a new file, do not type the whole file from start to end. Generate it with the matching `php artisan make:*` command from the project root, then edit the generated file to add the code.

## Steps

1. Pick the generator from the table below.
2. Run it from the project root (`C:\Users\H2\Desktop\MOBILE CMS\bmi-cms-api`).
3. Read the generated file, then add the code with Edit. Keep the namespace and structure that artisan generated.
4. List the generated file under **New files** in the end-of-task summary.

## Generators

| New file | Command |
|---|---|
| Model | `php artisan make:model AuditLog` (add `-m` migration, `-f` factory, `-s` seeder when needed) |
| Migration (new table) | `php artisan make:migration create_audit_logs_table` |
| Migration (change a table) | `php artisan make:migration add_locked_at_to_users_table --table=users` |
| API controller | `php artisan make:controller Api/V1/AuditLogController --api` |
| Form request | `php artisan make:request LoginRequest` |
| API resource | `php artisan make:resource AccountResource` |
| Service, repository, query, constants or other plain class | `php artisan make:class Services/AuditLogService` (path is relative to `app/`) |
| Interface / trait / enum | `php artisan make:interface`, `make:trait`, `make:enum` |
| Artisan command | `php artisan make:command UnlockUserLogin` |
| Seeder / factory | `php artisan make:seeder AccountSeeder`, `php artisan make:factory AccountFactory` |
| Feature test / unit test | `php artisan make:test LoginLockoutTest`, `php artisan make:test SomethingTest --unit` |
| Test in a subfolder | `php artisan make:test VisitPlan/TeamAssignedAccountsTest` |
| Middleware / policy / rule | `php artisan make:middleware`, `make:policy`, `make:rule` |
| Exception / job / event / listener / observer | `php artisan make:exception`, `make:job`, `make:event`, `make:listener`, `make:observer` |
| Config file | `php artisan make:config visit_plan` |

Run `php artisan list make` to see every generator.

## Files created manually (no artisan generator)

Only these may be written from scratch:

- Documentation: `docs/*.md`, `CLAUDE.md`, skill files.
- Route files: `routes/api/v1/*.php`.
- `.http` request files, `docs/openapi.yaml`, scripts and deployment files (`scripts/*.sh`, `render.yaml`, Docker files).
- Any other file type that `php artisan list make` has no command for.

## Not covered by this skill

Changes to existing files are made with Edit, not by regenerating them.
