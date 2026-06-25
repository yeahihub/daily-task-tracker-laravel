# Daily Task Tracker

Современное веб-приложение для управления ежедневными задачами и их сортировка по категориям. Разработано с использованием Laravel, Tailwind CSS и Alpine.js.

## Features

- **Аутентификация пользователей**: безопасная система входа и регистрации.
- **Управление задачами**: создание, просмотр, редактирование и удаление задач.
- **Категории**: организация задач по пользовательским категориям.
- **Повторяющиеся задачи**: настройка задач, выполняющихся по расписанию.
- **Панель управления**: просмотр статистики задач и просроченных задач.
- **Профили пользователей**: управление настройками учетной записи.
- **Адаптивный дизайн**: корректная работа на компьютерах и мобильных устройствах.

## Технологический стек
- **Бэкенд**: Laravel 13 (PHP 8.4)
- **База данных**: MySQL 8.4
- **Фронтенд**: Alpine.js 3, Tailwind CSS 4
- **Сборщик**: Vite
- **Среда разработки**: Docker с Laravel Sail
- **Тестирование**: PHPUnit 12

## Требования

- Docker & Docker Compose
- Composer
- Node.js (устанавливается и используется через Sail)

## Установка

### 1. Клонируйте репозиторий

```bash
git clone <repository-url>
cd daily-task-tracker-laravel
```

### 2. Установите зависимости

```bash
# Установите PHP зависимости
composer install

# Запистите Docker контейнеры
./vendor/bin/sail up -d

# Установите Node зависимости
./vendor/bin/sail npm install
```

### 3. Установка Окружения

```bash
# Скопируйте файл .env
cp .env.example .env

# Сгенерируйте ключи для приложения
./vendor/bin/sail artisan key:generate
```

### 4. Настройка базы данных

```bash
# Запустите миграции
./vendor/bin/sail artisan migrate

# (Необязательно) Заполнените базы данных тестовыми данными
./vendor/bin/sail artisan db:seed
```

### 5. Сборка фронтенда

```bash
./vendor/bin/sail npm run build
```

### 6. Доступ к приложению

Откройте браузер и перейдите по адресу: **http://localhost**

## Использование

### Ежедневный рабочий процесс

1. **Вход**: авторизуйтесь под своей учетной записью.
2. **Просмотр панели управления**: получите обзор задач и статистики.
3. **Создание задач**: добавляйте новые задачи и назначайте им категории.
4. **Управление категориями**: создавайте и организуйте категории задач.
5. **Отслеживание прогресса**: отмечайте задачи как выполненные или удаляйте их.
6. **Настройка повторяющихся задач**: создавайте задачи, которые будут автоматически повторяться.

### Управление задачами

- **Создать задачу**: нажмите на кнопку «НОВАЯ ЗАДАЧА» и заполните необходимые поля.
- **Редактировать задачу**: откройте задачу для изменения её данных.
- **Удалить задачу**: удалите задачу, которая больше не нужна.
- **Просмотреть задачи**: просматривайте все задачи или фильтруйте их по категориям.

### Категории

- **Создать категорию**: добавьте новую категорию задач.
- **Редактировать категорию**: измените название и описание категории.
- **Удалить категорию**: удалите категорию (задачи останутся без категории).
- **Фильтрация по категории**: просматривайте задачи, сгруппированные по категориям.

## Разработка

### Запуск сервера разработки

```bash
# Запустите все службы
./vendor/bin/sail up -d

# Наблюдайте за изменениями фронтенда без перезагрузки страницы
./vendor/bin/sail npm run dev

# Или запустите в фоновом режиме
./vendor/bin/sail composer run dev
```

### База данных

Создайте и управляйте тестовой базой данных:

```bash
./vendor/bin/sail mysql -h mysql -u sail -ppassword -e "CREATE DATABASE IF NOT EXISTS testing;"
```

## Тестирование

Для проверки корректной работы приложения выполните тесты:

```bash
# Запуск всех тестов
./vendor/bin/sail artisan test

# Запуск с компактным выводом
./vendor/bin/sail artisan test --compact

# Запуск конкретного файла тестов
./vendor/bin/sail artisan test tests/Feature/Controllers/TaskControllerTest.php

# Запуск тестов по фильтру
./vendor/bin/sail artisan test --filter=testMethodName
```

## Качество кода

Отформатируйте код с помощью Laravel Pint:

```bash
./vendor/bin/sail bin pint
```

## Полезные команды

```bash
# Остановка контейнеров
./vendor/bin/sail stop

# Остановка и удаление контейнеров
./vendor/bin/sail down

# Просмотр логов
./vendor/bin/sail logs

# Доступ к Laravel Tinker (REPL)
./vendor/bin/sail tinker

# Создание модели с миграцией и фабрикой
./vendor/bin/sail artisan make:model ModelName -mf

# Создание контроллера
./vendor/bin/sail artisan make:controller ControllerName

# Создание миграции
./vendor/bin/sail artisan make:migration create_table_name

# Очистка кэша
./vendor/bin/sail artisan cache:clear

# Просмотр маршрутов
./vendor/bin/sail artisan route:list
```

## Структура проекта

```
app/
├── Actions/          # Action classes for business logic
├── Models/           # Eloquent models
├── Http/
│   ├── Controllers/  # HTTP controllers
│   ├── Requests/     # Form requests
│   └── Resources/    # API resources
├── Policies/         # Authorization policies
└── Services/         # Service classes

database/
├── migrations/       # Database migrations
├── factories/        # Model factories
└── seeders/         # Database seeders

resources/
├── css/             # Stylesheets
├── js/              # JavaScript files
└── views/           # Blade templates

routes/
├── web.php          # Web routes
└── console.php      # Console commands

tests/
├── Feature/         # Feature tests
└── Unit/            # Unit tests
```

## Переменные окружения

Основные переменные в файле `.env`:

```
APP_NAME="Daily Task Tracker"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

## Решение распространённых проблем

### Контейнеры не запускаются

```bash
docker compose down -v
docker compose up -d --build
```

### Ошибка ключа приложения

```bash
./vendor/bin/sail artisan key:generate
```

### Ошибка подключения к базе данных

Убедитесь, что контейнер MySQL запущен и база данных существует:

```bash
./vendor/bin/sail artisan migrate
```

### Изменения на фронтенде не отображаются

Пересоберите ресурсы:

```bash
./vendor/bin/sail npm run build
```

## Лицензия

Проект распространяется с открытым исходным кодом по лицензии MIT.

## Поддержка

Если у вас возникли вопросы или проблемы, ознакомьтесь с документацией Laravel (https://laravel.com/docs)  или создайте Issue в репозитории.
