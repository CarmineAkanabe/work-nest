
# WorkNest API

A role-based workspace management REST API built with Laravel 13. WorkNest demonstrates
a production-grade Laravel architecture covering authentication, authorization, background
processing, scheduling, and caching.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Language | PHP 8.4 |
| Database | PostgreSQL |
| Cache / Queue Store | Redis (Memurai on Windows) |
| Authentication | Laravel Sanctum |
| HTTP Client | Predis |

---

## Architecture

WorkNest follows a strict layered architecture:

```
Request
  → Middleware (ForceJson, BlacklistIp, RoleMiddleware, ThrottleRequests, auth:sanctum)
    → Form Request (validation firewall)
      → Controller (traffic cop, no business logic)
        → Service (all business logic)
          → Model (ORM, database interaction)
            → Resource (serialization filter, shapes JSON response)
```

---

## Domain

Three user roles manage two owned resources:

```
users ──< projects ──< tasks
  └─────────────────────────< tasks (via assigned_to)
```

| Model | Owned By | Description |
|---|---|---|
| User | — | System actor with a role |
| Project | Manager | A workspace container |
| Task | Employee (assigned) | A unit of work inside a Project |

---

## Roles

Defined as a PHP Enum in `app/Enums/UserRole.php`.

| Role | Capabilities |
|---|---|
| `admin` | Full access. Creates users. Deletes anything. No rate limit. |
| `manager` | Creates and owns Projects. Creates and manages Tasks. 60 req/min. |
| `employee` | Views Projects. Updates and views their assigned Tasks. 30 req/min. |

---

## Concepts Applied

| Concept | Implementation |
|---|---|
| **Sanctum Auth** | Token-based login/logout. Tokens stored in `personal_access_tokens`. |
| **Policies** | `ProjectPolicy`, `TaskPolicy` — model-bound ownership checks. |
| **Gates** | `is-admin`, `is-manager` — role checks in `AppServiceProvider`. |
| **Middleware** | `ForceJsonMiddleware`, `RoleMiddleware`, `BlacklistIp`, rate limiting. |
| **Events & Listeners** | `TaskCompleted` event fires `NotifyProjectManager` and `LogTaskCompletion`. |
| **Task Scheduling** | `MarkOverdueTasks` command runs nightly via Laravel Scheduler. |
| **Redis Caching** | Project and Task index responses cached by user ID with auto-invalidation. |

---

## Project Structure

```
app/
├── Console/Commands/
│   └── MarkOverdueTasks.php
├── Enums/
│   ├── UserRole.php
│   ├── ProjectStatus.php
│   └── TaskStatus.php
├── Events/
│   └── TaskCompleted.php
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── ProjectController.php
│   │   └── TaskController.php
│   ├── Middleware/
│   │   ├── BlacklistIp.php
│   │   ├── ForceJsonMiddleware.php
│   │   └── RoleMiddleware.php
│   ├── Requests/
│   │   ├── LoginRequest.php
│   │   ├── RegisterRequest.php
│   │   ├── StoreProjectRequest.php
│   │   ├── UpdateProjectRequest.php
│   │   ├── StoreTaskRequest.php
│   │   └── UpdateTaskRequest.php
│   └── Resources/
│       ├── UserResource.php
│       ├── ProjectResource.php
│       └── TaskResource.php
├── Listeners/
│   ├── NotifyProjectManager.php
│   └── LogTaskCompletion.php
├── Models/
│   ├── User.php
│   ├── Project.php
│   └── Task.php
├── Policies/
│   ├── ProjectPolicy.php
│   └── TaskPolicy.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    ├── AuthService.php
    ├── ProjectService.php
    └── TaskService.php
routes/
└── api.php
bootstrap/
└── app.php
database/
├── factories/
│   ├── UserFactory.php
│   ├── ProjectFactory.php
│   └── TaskFactory.php
├── migrations/
│   ├── create_users_table.php
│   ├── add_role_to_users_table.php
│   ├── create_projects_table.php
│   └── create_tasks_table.php
└── seeders/
    └── DatabaseSeeder.php
```

---

## Setup

```bash
# 1. Install dependencies
composer install

# 2. Copy environment file
cp .env.example .env

# 3. Configure .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=work_nest
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 4. Generate app key
php artisan key:generate

# 5. Run migrations and seed
php artisan migrate --seed

# 6. Start the server
php artisan serve
```

---

## API Endpoints

### Auth
| Method | Endpoint | Access | Description |
|---|---|---|---|
| POST | `/api/login` | Public | Login and receive token |
| POST | `/api/logout` | All roles | Invalidate current token |
| GET | `/api/me` | All roles | Get authenticated user |
| POST | `/api/register` | Admin only | Create a new user |

### Projects
| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/api/projects` | Admin, Manager | List projects |
| POST | `/api/projects` | Admin, Manager | Create a project |
| GET | `/api/projects/{id}` | All roles | View a project |
| PUT | `/api/projects/{id}` | Admin, owning Manager | Update a project |
| DELETE | `/api/projects/{id}` | Admin, owning Manager | Delete a project |

### Tasks
| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/api/tasks` | Admin, Manager | List tasks |
| POST | `/api/tasks` | Admin, Manager | Create a task |
| GET | `/api/tasks/{id}` | All roles | View a task |
| PUT | `/api/tasks/{id}` | Admin, Manager, assigned Employee | Update a task |
| DELETE | `/api/tasks/{id}` | Admin, Manager | Delete a task |

---

## Artisan Commands

```bash
# Manually mark overdue tasks
php artisan tasks:mark-overdue

# Run scheduler locally (every minute)
php artisan schedule:work

# Clear Redis cache
php artisan cache:clear

# Fresh migration with seed
php artisan migrate:fresh --seed
```

---

## Seeded Test Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@worknest.com | password |
| Manager | (generated) | password |
| Employee | (generated) | password |

Check pgAdmin for generated manager and employee emails.
````
