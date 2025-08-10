# AGENT.md - Laravel Inventory System

## Commands
- **Test**: `vendor/bin/phpunit` or `php artisan test`
- **Single test**: `vendor/bin/phpunit tests/Feature/TestName.php`
- **Lint**: `vendor/bin/pint` (Laravel Pint)
- **Build assets**: `npm run build` or `npm run dev`
- **Serve**: `php artisan serve`
- **Database**: `php artisan migrate` | `php artisan db:seed`

## Architecture
- **Laravel 10** with **Filament 3** admin panel
- **Spatie packages**: Permissions (roles/permissions), Media Library  
- **Database**: MySQL with migrations in `database/migrations/`
- **Models**: Standard Eloquent in `app/Models/` 
- **Resources**: Filament resources in `app/Filament/Resources/`
- **Key directories**: `app/` (MVC), `resources/views/`, `config/`, `database/`

## Code Style
- **PSR-4** autoloading with `App\` namespace
- **Type hints**: Use for params and return types 
- **Imports**: Use statements at top, group by vendor then local
- **Naming**: PascalCase for classes, camelCase for methods/properties
- **Arrays**: Use square brackets `[]` syntax
- **Strings**: Single quotes unless interpolation needed
- **Logging**: Use `Log::info()` with context arrays for debugging
