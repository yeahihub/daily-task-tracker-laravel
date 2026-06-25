# Daily Task Tracker

A modern web application for managing your daily tasks and organizing them by categories. Built with Laravel, Tailwind CSS, and Alpine.js.

## Features

- **User Authentication**: Secure login and registration system
- **Task Management**: Create, view, update, and delete tasks with ease
- **Categories**: Organize tasks into custom categories
- **Recurring Tasks**: Set up tasks that repeat on a schedule
- **Dashboard**: View task statistics and overdue tasks at a glance
- **User Profiles**: Manage your account settings
- **Responsive Design**: Works seamlessly on desktop and mobile devices

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.4)
- **Database**: MySQL 8.4
- **Frontend**: Alpine.js 3, Tailwind CSS 4
- **Build Tool**: Vite
- **Development Environment**: Docker with Laravel Sail
- **Testing**: PHPUnit 12

## Requirements

- Docker & Docker Compose
- Composer
- Node.js (handled by Sail)

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd daily-task-tracker-laravel
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Start Docker containers
./vendor/bin/sail up -d

# Install Node dependencies
./vendor/bin/sail npm install
```

### 3. Setup Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
./vendor/bin/sail artisan key:generate
```

### 4. Database Setup

```bash
# Run migrations
./vendor/bin/sail artisan migrate

# (Optional) Seed sample data
./vendor/bin/sail artisan db:seed
```

### 5. Build Frontend Assets

```bash
./vendor/bin/sail npm run build
```

### 6. Access the Application

Open your browser and navigate to: **http://localhost**

## Usage

### Daily Workflow

1. **Login**: Sign in with your credentials
2. **View Dashboard**: See your task overview and statistics
3. **Create Tasks**: Add new tasks and assign them to categories
4. **Manage Categories**: Create and organize task categories
5. **Track Progress**: Mark tasks as complete or delete them
6. **Set Recurring Tasks**: Schedule tasks that repeat automatically

### Task Management

- **Create Task**: Click "Create Task" and fill in the details
- **Edit Task**: Click on a task to update its information
- **Delete Task**: Remove tasks you no longer need
- **View Tasks**: Browse all tasks or filter by category

### Categories

- **Create Category**: Add new task categories
- **Edit Category**: Update category names and details
- **Delete Category**: Remove categories (tasks will be unassigned)
- **Filter by Category**: View tasks organized by category

## Development

### Running the Development Server

```bash
# Start all services
./vendor/bin/sail up -d

# Watch for frontend changes
./vendor/bin/sail npm run dev

# Or run in background
./vendor/bin/sail composer run dev
```

### Database

Create and manage testing database:

```bash
# Create testing database
./vendor/bin/sail mysql -h mysql -u sail -ppassword -e "CREATE DATABASE IF NOT EXISTS testing;"
```

## Testing

Run the test suite to ensure everything is working correctly:

```bash
# Run all tests
./vendor/bin/sail artisan test

# Run with compact output
./vendor/bin/sail artisan test --compact

# Run specific test file
./vendor/bin/sail artisan test tests/Feature/Controllers/TaskControllerTest.php

# Run with filter
./vendor/bin/sail artisan test --filter=testMethodName
```

## Code Quality

Format your code using Laravel Pint:

```bash
./vendor/bin/sail bin pint
```

## Common Commands

```bash
# Stop containers
./vendor/bin/sail stop

# Stop and remove containers
./vendor/bin/sail down

# View logs
./vendor/bin/sail logs

# Access Laravel Tinker (REPL)
./vendor/bin/sail tinker

# Create model with migration and factory
./vendor/bin/sail artisan make:model ModelName -mf

# Create controller
./vendor/bin/sail artisan make:controller ControllerName

# Create migration
./vendor/bin/sail artisan make:migration create_table_name

# Cache clear
./vendor/bin/sail artisan cache:clear

# View routes
./vendor/bin/sail artisan route:list
```

## Project Structure

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

## Environment Variables

Key environment variables in `.env`:

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

## Troubleshooting

### Containers won't start

```bash
docker compose down -v
docker compose up -d --build
```

### Application key error

```bash
./vendor/bin/sail artisan key:generate
```

### Database connection error

Ensure MySQL container is running and database exists:

```bash
./vendor/bin/sail artisan migrate
```

### Frontend changes not showing

Rebuild assets:

```bash
./vendor/bin/sail npm run build
```

## License

This project is open source and available under the MIT License.

## Support

For issues or questions, please check the [Laravel documentation](https://laravel.com/docs) or create an issue in the repository.
