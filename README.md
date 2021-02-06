# Laravel/Lumen schema builder
![schema-builder](./doc/schema-builder.png?raw=true "schema-builder")

Database designer & migration generator package for laravel & lumen.

Checkout **[agontuk.github.io/schema-designer](https://agontuk.github.io/schema-designer)** to see how it works.

## Installation
```php
composer require --dev noud/schema-builder
```

For Laravel(>=5.5) this package utilises package auto-discovery. There is no need to add the service providers manually. You may notice the following message post-autoload.

```bash
Discovered Package: noud/schema-builder
```

or for Laravel(<5.5), register SchemaServiceProvider on the `providers` array in `config/app.php`,
```php
\Agontuk\Schema\SchemaServiceProvider::class
```
or for Lumen in `bootstrap/app.php`
```php
$app->register(\Agontuk\Schema\SchemaServiceProvider::class);
```

Finally enable required routes via `.env`,
```php
SCHEMA_ROUTES_ENABLED=true
```
> NOTE: APP_ENV should be `local` to use this package.

## Usage
Navigate to `yoursite.com/schema` and build your database schema, then use the export button to generate migration files.

> NOTE: Not all features of migration are supported yet. Feel free to submit any issues or pull requests.

## License
[MIT](https://github.com/noud/schema-builder/blob/master/LICENSE)
