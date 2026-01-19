# Laravel 11 API Project Created
Location: api/

Database Migrations (21 tables)
All tables from your schema have been created as migrations in api/database/migrations/:

-  users, categories, items, options, option_values
-  item_options, item_option_values, option_dependencies
-  taxes, item_taxes, customers, cash_sessions, statuses
-  orders, order_items, order_item_options, payments, tips
-  withdrawals, settings, branches, branch_items
-  Eloquent Models (19 models)
-  Located in api/app/Models/ with all relationships defined.

API Controllers (17 controllers)
Located in api/app/Http/Controllers/Api/:

AuthController - Login/logout with Sanctum tokens
Full CRUD controllers for all resources
API Endpoints
All routes are prefixed with /api and protected by Sanctum authentication:

POST /api/login - Authenticate and get token
POST /api/logout - Revoke token
Full REST endpoints for all resources
Seeders
Default data seeders for: statuses, admin user, categories, and settings.

To get started:

cd api
cp .env.example .env
# Configure your database in .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
The API uses Laravel Sanctum for token-based authentication, which works well with Flutter apps and web applications. CORS is configured to allow requests from any origin.