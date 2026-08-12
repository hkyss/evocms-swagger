# evo-swagger

Пакет для Evolution CMS 3: модуль менеджера «API документация» (Swagger UI) и команда `apidocs:generate`.

Спецификация в пакет не входит. Генератор сканирует каталоги вашего проекта и собирает OpenAPI из атрибутов `#[OA\*]`.

## Требования

- Evolution CMS 3.x
- PHP >= 8.2

## Установка

Из каталога `core` сайта:

```bash
php artisan package:installrequire vvvladv/evo-swagger "*"
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
composer update vvvladv/evo-swagger
php artisan package:discover
```

| Шаг | Результат |
|-----|-----------|
| `composer update` | Пакет и `zircote/swagger-php` в vendor |
| `package:discover` | Регистрация service provider |
| Первый boot | Автокопия `core/custom/config/evo-swagger.php`, если файла нет |

`package:discover` читает `core/custom/composer.json`, а не `core/composer.json` — если провайдер не зарегистрировался, требование пакета лежит не там.

Модуль и Swagger UI работают из пакета (vendor). URL статики строится относительно `MODX_BASE_PATH`.

Если vendor не отдаётся по HTTP:

```bash
php artisan vendor:publish --provider="EvolutionCMS\EvoSwagger\EvoSwaggerServiceProvider" --tag=evo-swagger-assets
```

Публикация обязательна, если пользуетесь кнопкой «Сгенерировать»: её обработчик `ajax.php` должен быть доступен по HTTP.

Повторная публикация конфига:

```bash
php artisan vendor:publish --provider="EvolutionCMS\EvoSwagger\EvoSwaggerServiceProvider" --tag=evo-swagger-config
```

## Модуль

Две вкладки:

- **Спецификация** — Swagger UI. Строится сканированием при каждом открытии страницы, файл для этого не нужен.
- **Параметры** — настройки. Сохранение перезаписывает `core/custom/config/evo-swagger.php` целиком, комментарии в нём не переживают.

Кнопка **Сгенерировать** пишет спецификацию в файл и перерисовывает Swagger UI на месте.

Требуется право `exec_module`.

## Настройка

Файл: `core/custom/config/evo-swagger.php`. Редактируется руками или на вкладке «Параметры».

```php
return [
    'openapi' => '3.0.0',
    'controllers_path' => [
        'core/vendor/acme/package/src/Api',
        'core/custom/OpenApi/stubs',
    ],
    'output' => 'assets/modules/ApiDocs/docs/openapi.json',
    'module_name' => 'API документация',
];
```

| Ключ | Смысл |
|------|-------|
| `openapi` | `3.0.0` или `3.1.0` |
| `controllers_path` | Каталоги с `#[OA\*]`. Список; строка тоже принимается — формат прежних конфигов |
| `output` | Файл спецификации. `null` → `assets/modules/ApiDocs/docs/openapi.json`. Расширение задаёт формат: `.json`, `.yaml`, `.yml` |
| `module_name` | Подпись модуля в меню менеджера |

**Пути.** Относительные считаются от `MODX_BASE_PATH`, абсолютные берутся как есть. Храните относительные — конфиг переживёт переезд проекта.

Несуществующий каталог не ошибка: он сохраняется, помечается в интерфейсе как ненайденный и пропускается при сканировании. Если не найден ни один — генерация падает.

## Использование

- Менеджер → модуль **API документация**
- CLI: `php artisan apidocs:generate`
- Опции: `--output=`, `--format=json|yaml`

`--output` подчиняется тому же правилу: относительный путь считается от корня сайта, а не от `core`, откуда запускается artisan.

Генератор загружает каждый класс в просканированных каталогах. Если класс требует окружения EVO (константы, хелперы), запускайте команду через artisan — вне EVO загрузка упадёт.

## Структура

```text
evo-swagger/
├── composer.json
├── config/evo-swagger.php
├── src/
│   ├── EvoSwaggerServiceProvider.php
│   ├── Module.php
│   ├── ConfigStore.php
│   ├── OpenApiSpecGenerator.php
│   └── Console/GenerateOpenApiCommand.php
└── assets/modules/ApiDocs/
    ├── module.php
    ├── index.php
    ├── ajax.php
    ├── frontend/
    └── docs/
```
