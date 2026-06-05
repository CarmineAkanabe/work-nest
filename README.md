
# WorkNest API

A role-based workspace management REST API built with Laravel 13. WorkNest demonstrates
core Laravel backend concepts including authentication, authorization, middleware,
events, task scheduling, and Redis caching.

---

## Tech Stack

| Layer          | Technology                        |
|----------------|-----------------------------------|
| Framework      | Laravel 13                        |
| Language       | PHP 8.4                           |
| Database       | PostgreSQL                        |
| Auth           | Laravel Sanctum (API tokens)      |
| Cache          | Redis via Memurai (Windows)       |
| Redis Client   | Predis                            |
| Dev Server     | php artisan serve                 |

---

## Domain

WorkNest manages users, projects, and tasks across three roles:

| Role     | Permissions                                              |
|----------|----------------------------------------------------------|
| Admin    | Full access to everything. Creates user accounts.        |
| Manager  | Creates and owns Projects. Assigns Tasks to Employees.   |
| Employee | Views and updates Tasks assigned to them.                |

### Relationships
```
users ──< projects (user_id → manager who owns the project)
projects ──< tasks (project_id)
users ──< tasks (assigned_to → employee assigned to the task)
```

---

## Project Structure

```
app/
├── Console/Commands/       # Scheduled artisan commands
├── Enums/                  # Strict type definitions (roles, statuses)
├── Events/                 # System event classes
├── Http/
│   ├── Controllers/        # Traffic cops — thin, no business logic
│   ├── Middleware/         # Request interceptors
│   ├── Requests/           # Form request validators (Firewall)
│   └── Resources/          # API response formatters (Serialization Filter)
├── Listeners/              # Event handlers
├── Models/                 # Eloquent ORM models
├── Policies/               # Model-bound authorization rules
├── Providers/              # AppServiceProvider — gates, events, rate limits
└── Services/               # Business logic layer (Engine)
database/
├── factories/              # Fake data blueprints
├── migrations/             # Database version control
└── seeders/                # Data injection scripts
routes/
├── api.php                 # All API routes
└── console.php             # Scheduled command definitions
bootstrap/
└── app.php                 # Middleware registration
```

---

## Setup

### 1. Clone and install dependencies
```bash
git clone <repo-url>
cd work-nest
composer install
```

### 2. Environment
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=work_nest
DB_USERNAME=your_pg_username
DB_PASSWORD=your_pg_password

CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Database
Create an empty PostgreSQL database named `work_nest`, then:
```bash
php artisan migrate
php artisan db:seed
```

### 4. Run
```bash
php artisan serve
```

API is available at `http://127.0.0.1:8000/api`

---

## Seeded Accounts

| Role     | Email                    | Password  |
|----------|--------------------------|-----------|
| Admin    | admin@worknest.com       | password  |
| Manager  | (check DB)               | password  |
| Employee | (check DB)               | password  |

---

## API Endpoints

### Auth
| Method | Endpoint        | Access     | Description               |
|--------|-----------------|------------|---------------------------|
| POST   | /api/login      | Public     | Login, receive token      |
| GET    | /api/me         | All roles  | Authenticated user info   |
| POST   | /api/logout     | All roles  | Revoke current token      |
| POST   | /api/register   | Admin only | Create a new user account |

### Projects
| Method | Endpoint               | Access              | Description             |
|--------|------------------------|---------------------|-------------------------|
| GET    | /api/projects          | Admin, Manager      | List projects           |
| POST   | /api/projects          | Manager only        | Create a project        |
| GET    | /api/projects/{id}     | All roles           | View a single project   |
| PUT    | /api/projects/{id}     | Admin, owning Mgr   | Update a project        |
| DELETE | /api/projects/{id}     | Admin, owning Mgr   | Delete a project        |

### Tasks
| Method | Endpoint            | Access                        | Description           |
|--------|---------------------|-------------------------------|-----------------------|
| GET    | /api/tasks          | Admin, Manager                | List tasks            |
| POST   | /api/tasks          | Manager only                  | Create a task         |
| GET    | /api/tasks/{id}     | All roles                     | View a single task    |
| PUT    | /api/tasks/{id}     | Admin, Manager, assigned Emp  | Update a task         |
| DELETE | /api/tasks/{id}     | Admin, Manager                | Delete a task         |

---

## Authentication

WorkNest uses Laravel Sanctum for token-based API authentication.

1. POST to `/api/login` with email and password
2. Receive a Bearer token in the response
3. Include the token in all subsequent requests:
```
Authorization: Bearer <your_token>
```

---

## Concepts Applied

| Concept              | Implementation                                         |
|----------------------|--------------------------------------------------------|
| Sanctum Auth         | Token issuance on login, revocation on logout          |
| Policies             | ProjectPolicy, TaskPolicy — model-bound rules          |
| Gates                | is-admin, is-manager — simple role checks              |
| Role Middleware      | Blocks routes by UserRole before hitting controllers   |
| ForceJson Middleware | Forces Accept: application/json on all requests        |
| BlacklistIp          | Blocks requests from specific IP addresses             |
| Rate Limiting        | Per-role request limits via RateLimiter in AppServiceProvider |
| Events & Listeners   | TaskCompleted fires NotifyProjectManager + LogTaskCompletion |
| Task Scheduling      | MarkOverdueTasks runs nightly via Laravel scheduler    |
| Redis Caching        | Project and Task index queries cached in Redis db1     |

---

## Artisan Commands

```bash
# Mark overdue tasks manually
php artisan tasks:mark-overdue

# Run scheduler locally (simulates cron)
php artisan schedule:work

# Clear Redis cache
php artisan cache:clear

# Fresh migrate and seed
php artisan migrate:fresh --seed
```
```

---
