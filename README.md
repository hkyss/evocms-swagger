# evo-swagger

Пакет для Evolution CMS 3: модуль менеджера «API документация» (Swagger UI) и команда `apidocs:generate`.

Спецификация вашего API в пакет не входит — сканируется каталог из `controllers_path`.

## Требования

- Evolution CMS 3.x
- PHP >= 8.2

## Установка

Из каталога `core` сайта:

```bash
php artisan package:installrequire evolution-cms/evo-swagger "*"
php artisan package:discover
```

Через Composer (VCS), в `composer.json` ядра или `core/custom/composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/YOUR_USER/evo-swagger"
    }
  ],
  "require": {
    "evolution-cms/evo-swagger": "dev-main"
  }
}
```

```bash
composer update evolution-cms/evo-swagger
php artisan package:discover
```

| Шаг | Результат |
|-----|-----------|
| `composer update` | Пакет и `zircote/swagger-php` в vendor |
| `package:discover` | Регистрация service provider |
| Первый boot | Автокопия `core/custom/config/evo-swagger.php`, если файла нет |

Модуль и Swagger UI работают из пакета (vendor). URL статики строится относительно `MODX_BASE_PATH`.

Если vendor не отдаётся по HTTP:

```bash
php artisan vendor:publish --provider="EvolutionCMS\EvoSwagger\EvoSwaggerServiceProvider" --tag=evo-swagger-assets
```

Повторная публикация конфига:

```bash
php artisan vendor:publish --provider="EvolutionCMS\EvoSwagger\EvoSwaggerServiceProvider" --tag=evo-swagger-config
```

## Настройка

Файл: `core/custom/config/evo-swagger.php`

```php
return [
    'openapi' => '3.0.0',
    'controllers_path' => '/absolute/path/to/OpenApi/stubs',
    'output' => null, // → assets/modules/ApiDocs/docs/openapi.json
    'module_name' => 'API документация',
];
```

`controllers_path` — абсолютный путь к каталогу с `#[OA\*]` в вашем проекте.

## Использование

- Менеджер → модуль **API документация**
- CLI: `php artisan apidocs:generate`
- Опции: `--output=`, `--format=json|yaml`

## Структура

```text
evo-swagger/
├── composer.json
├── config/evo-swagger.php
├── src/
│   ├── EvoSwaggerServiceProvider.php
│   ├── Module.php
│   ├── OpenApiSpecGenerator.php
│   └── Console/GenerateOpenApiCommand.php
└── assets/modules/ApiDocs/
    ├── module.php
    ├── index.php
    ├── frontend/
    └── docs/
```

## Лицензия

MIT. Автор: vvvladv.
