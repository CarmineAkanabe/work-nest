
# WorkNest — Technical Documentation

Complete reference for every file, function, concept, and known error in WorkNest.

---

## Table of Contents

1. [Enums](#enums)
2. [Models](#models)
3. [Migrations](#migrations)
4. [Factories & Seeders](#factories--seeders)
5. [Authentication](#authentication)
6. [Form Requests](#form-requests)
7. [Services](#services)
8. [Controllers](#controllers)
9. [Resources](#resources)
10. [Policies & Gates](#policies--gates)
11. [Middleware](#middleware)
12. [Events & Listeners](#events--listeners)
13. [Task Scheduling](#task-scheduling)
14. [Redis Caching](#redis-caching)
15. [Routes](#routes)
16. [Bootstrap](#bootstrap)
17. [Known Errors & Fixes](#known-errors--fixes)

---

## Enums

Enums enforce strict type safety. They prevent arbitrary strings from being inserted
into the database and make role/status comparisons safe and readable.

### `app/Enums/UserRole.php`
Defines the three user roles in the system.

| Case | Value | Description |
|---|---|---|
| `Admin` | `'admin'` | Full system access |
| `Manager` | `'manager'` | Owns and manages Projects and Tasks |
| `Employee` | `'employee'` | Executes assigned Tasks |

### `app/Enums/ProjectStatus.php`
Defines the lifecycle states of a Project.

| Case | Value | Description |
|---|---|---|
| `Active` | `'active'` | Default state on creation |
| `Completed` | `'completed'` | All tasks done |
| `Archived` | `'archived'` | Closed/inactive |

### `app/Enums/TaskStatus.php`
Defines the lifecycle states of a Task.

| Case | Value | Description |
|---|---|---|
| `Pending` | `'pending'` | Default state on creation |
| `InProgress` | `'in_progress'` | Actively being worked on |
| `Completed` | `'completed'` | Done — triggers TaskCompleted event |
| `Overdue` | `'overdue'` | Set automatically by the scheduler |

---

## Models

### `app/Models/User.php`

Extends `Authenticatable` — gives it password hashing and Sanctum token support.

| Trait | Purpose |
|---|---|
| `HasApiTokens` | Sanctum — enables `createToken()` and `currentAccessToken()` |
| `HasFactory` | Enables `User::factory()` for seeding |
| `Notifiable` | Required for sending mail/notifications |

**Attributes (via PHP Attributes syntax — Laravel 13):**

`#[Fillable]` — replaces the `$fillable` array. Fields: `name`, `email`, `password`, `role`.

`#[Hidden]` — replaces the `$hidden` array. Fields: `password`.

**Casts:**
- `password` → `hashed` — auto-hashes on assignment, never stores plaintext
- `role` → `UserRole::class` — auto-converts DB string to Enum instance

**Relationships:**
- `projects()` — HasMany → Project (projects this user owns as Manager)
- `tasks()` — HasMany → Task (tasks assigned to this user as Employee, FK: `assigned_to`)

---

### `app/Models/Project.php`

| Field | Type | Description |
|---|---|---|
| `user_id` | FK → users | The Manager who owns this project |
| `name` | string | Project title |
| `description` | text, nullable | Project details |
| `status` | string (Enum) | Current lifecycle state |
| `deadline` | timestamp, nullable | Target completion date |

**Casts:**
- `status` → `ProjectStatus::class`
- `deadline` → `datetime`

**Relationships:**
- `owner()` — BelongsTo → User (via `user_id`)
- `tasks()` — HasMany → Task

---

### `app/Models/Task.php`

| Field | Type | Description |
|---|---|---|
| `project_id` | FK → projects | The Project this task belongs to |
| `assigned_to` | FK → users | The Employee assigned to this task |
| `title` | string | Task title |
| `description` | text, nullable | Task details |
| `status` | string (Enum) | Current lifecycle state |
| `deadline` | timestamp, nullable | Target completion date |

**Casts:**
- `status` → `TaskStatus::class`
- `deadline` → `datetime`

**Relationships:**
- `project()` — BelongsTo → Project
- `assignee()` — BelongsTo → User (via `assigned_to`)

---

## Migrations

Migrations are version control for the database. Every developer runs them to get
the exact same schema.

| File | Description |
|---|---|
| `create_users_table` | Base users schema — name, email, password |
| `add_role_to_users_table` | Adds `role` column with default `employee` |
| `create_projects_table` | Projects schema with `user_id` FK |
| `create_tasks_table` | Tasks schema with `project_id` and `assigned_to` FKs |

**Important:** `cascadeOnDelete()` on FKs means deleting a Project deletes its Tasks,
and deleting a User deletes their Projects and assigned Tasks.

---

## Factories & Seeders

### Factories
Factories use the Faker library to generate realistic dummy data.

| Factory | Fakes |
|---|---|
| `UserFactory` | name, unique email, hashed password, role (default: Employee) |
| `ProjectFactory` | user_id (null — always set in seeder), name via `fake()->bs()`, description, Active status, future deadline |
| `TaskFactory` | project_id (null), assigned_to (null), title, description, Pending status, near deadline |

`user_id`, `project_id`, and `assigned_to` are always set explicitly in the seeder,
never generated by the factory, to ensure correct ownership relationships.

### `DatabaseSeeder.php`

Execution order:
1. Creates 1 Admin with known credentials (`admin@worknest.com`)
2. Creates 2 Managers
3. For each Manager, creates 2 Projects (4 total)
4. Creates 2 Employees
5. For each Employee, creates 2 Tasks assigned to a random Project (4 total)

**Commands:**
```bash
php artisan db:seed              # seed only
php artisan migrate:fresh --seed # wipe and reseed
```

---

## Authentication

**Technology:** Laravel Sanctum — issues opaque API tokens stored in
the `personal_access_tokens` table.

### `app/Services/AuthService.php`

**`login(array $data): array`**
- Finds user by email
- Verifies password using `Hash::check()`
- Calls `$user->createToken('api-token')->plainTextToken`
- Returns `['user' => User, 'token' => string]`
- Throws `ValidationException` on bad credentials

**`register(array $data): array`**
- Creates User with role from request
- Generates token immediately
- Returns `['user' => User, 'token' => string]`

**`logout(Request $request): void`**
- Calls `$request->user()->currentAccessToken()->delete()`
- Deletes only the current token, not all tokens

**`me(Request $request): User`**
- Returns `$request->user()` — the authenticated user from the token

### `app/Http/Controllers/AuthController.php`

Thin controller — delegates everything to `AuthService`, wraps user in
`UserResource` before returning.

---

## Form Requests

Form Requests act as a validation firewall. They run before the controller and
automatically return a `422 Unprocessable Entity` if validation fails.

`authorize()` returns `true` in all requests — authorization is handled by
Policies and Middleware, not Form Requests.

### `LoginRequest`
| Field | Rules |
|---|---|
| email | required, email |
| password | required, string |

### `RegisterRequest`
| Field | Rules |
|---|---|
| name | required, string, max:255 |
| email | required, email, unique:users |
| password | required, string, min:8, confirmed |
| password_confirmation | required, string |
| role | required, in:admin,manager,employee |

### `StoreProjectRequest`
| Field | Rules |
|---|---|
| name | required, string, max:255 |
| description | nullable, string |
| deadline | nullable, date, after:today |

### `UpdateProjectRequest`
Uses `sometimes` — only validates fields that are present in the request.

| Field | Rules |
|---|---|
| name | sometimes, string, max:255 |
| description | nullable, string |
| status | sometimes, in:active,completed,archived |
| deadline | nullable, date, after:today |

### `StoreTaskRequest`
| Field | Rules |
|---|---|
| project_id | required, exists:projects,id |
| assigned_to | required, exists:users,id |
| title | required, string, max:255 |
| description | nullable, string |
| deadline | nullable, date, after:today |

### `UpdateTaskRequest`
| Field | Rules |
|---|---|
| title | sometimes, string, max:255 |
| description | nullable, string |
| status | sometimes, in:pending,in_progress,completed,overdue |
| deadline | nullable, date, after:today |

---

## Services

Services contain all business logic. Controllers never touch the DB directly.

### `app/Services/ProjectService.php`

**`getAll(User $user): Collection`**
- Checks Redis cache by key `projects.user.{id}`
- Cache hit → fetches projects by cached IDs with relationships
- Cache miss → queries DB, stores IDs in Redis for 10 minutes
- Admins see all projects, Managers see only their own

**`create(User $user, array $data): Project`**
- Creates project with spread operator `...$data` + `user_id`
- Calls `->fresh()` to reload DB defaults (status)
- Clears cache for the creating user and all Admins

**`update(Project $project, array $data): Project`**
- Updates project, clears cache, returns `$project->fresh()`

**`delete(Project $project): void`**
- Clears cache before deleting

**`clearCache(User $user): void`** (private)
- Forgets `projects.user.{user_id}`
- Also forgets cache for all Admins (they see all projects)

---

### `app/Services/TaskService.php`

**`getAll(User $user): Collection`**
- Same caching pattern as ProjectService
- Admins and Managers see all tasks
- Employees see only their assigned tasks

**`create(array $data): Task`**
- Creates task, eager loads `project` and `assignee` relationships
- Clears cache for all users

**`update(Task $task, array $data): Task`**
- Captures `$previousStatus` before update
- After update, checks if status changed to `Completed`
- If so, dispatches `TaskCompleted` event
- Reloads relationships, clears cache

**`delete(Task $task): void`**
- Clears cache, deletes task

**`clearCache(): void`** (private)
- Clears task cache for every user in the system

---

### `app/Services/AuthService.php`
Documented in the Authentication section above.

---

## Controllers

All controllers extend the base `Controller` class which uses `AuthorizesRequests`.
This provides `$this->authorize()` for Policy checks.

Controllers follow strict rules:
- Constructor injects the Service via dependency injection
- Each method calls `$this->authorize()` then delegates to the Service
- Returns a Resource-wrapped JSON response
- Contains zero business logic

### `app/Http/Controllers/ProjectController.php`

| Method | Policy Check | Service Call |
|---|---|---|
| `index` | `viewAny` | `getAll($user)` |
| `show` | `view` | — (route model binding) |
| `store` | `create` | `create($user, $data)` |
| `update` | `update` | `update($project, $data)` |
| `destroy` | `delete` | `delete($project)` |

### `app/Http/Controllers/TaskController.php`
Same pattern as ProjectController.

---

## Resources

Resources are serialization filters. They intercept Model objects and shape them
into clean, safe JSON. They hide internal columns and format values.

### `UserResource`
Exposes: `id`, `name`, `email`, `role` (as string value via `->value`)

### `ProjectResource`
Exposes: `id`, `name`, `description`, `status`, `deadline`, `owner` (id + name only), `created_at`

Note: `$this->status->value` extracts the raw string from the Enum.
`$this->deadline?->toDateString()` uses the null-safe operator — returns null if no deadline.

### `TaskResource`
Exposes: `id`, `title`, `description`, `status`, `deadline`, `project` (id + name), `assignee` (id + name), `created_at`

---

## Policies & Gates

### Gates — `app/Providers/AppServiceProvider.php`

Gates are simple closures registered in `boot()`. They answer yes/no questions
not tied to a specific model.

```php
Gate::define('is-admin', fn($user) => $user->role === UserRole::Admin);
Gate::define('is-manager', fn($user) => $user->role === UserRole::Manager);
```

Used via `$this->authorize('is-admin')` in controllers.

---

### `app/Policies/ProjectPolicy.php`

Policies are classes bound to a model. Laravel auto-discovers them by naming
convention (`Project` → `ProjectPolicy`).

| Method | Who can | Logic |
|---|---|---|
| `viewAny` | Admin, Manager | Role check only |
| `view` | Admin, owning Manager, any Employee | Role or ownership |
| `create` | Manager only | Role check |
| `update` | Admin or owning Manager | Role or `user_id` match |
| `delete` | Admin or owning Manager | Role or `user_id` match |

`restore()` and `forceDelete()` are excluded — WorkNest does not use Soft Deletes.
Soft Deletes stamp a `deleted_at` column instead of removing the row. `forceDelete()`
permanently removes soft-deleted rows. `restore()` recovers them. Neither applies here.

---

### `app/Policies/TaskPolicy.php`

| Method | Who can | Logic |
|---|---|---|
| `viewAny` | Admin, Manager | Role check |
| `view` | Everyone | Always true |
| `create` | Manager only | Role check |
| `update` | Admin, Manager, or assigned Employee | Role or `assigned_to` match |
| `delete` | Admin, Manager | Role check |

---

## Middleware

Middleware intercepts every request before it reaches the controller.

### `app/Http/Middleware/ForceJsonMiddleware.php`

Sets `Accept: application/json` on every request globally. Without this, Laravel
returns HTML error pages when something breaks, which is useless for an API.
Registered via `$middleware->append()` so it runs on every request.

---

### `app/Http/Middleware/RoleMiddleware.php`

Alias: `role` — used as `role:admin,manager` in routes.

Accepts variadic `string ...$roles` — can check one or multiple roles at once.

**Flow:**
1. Checks `$request->user()` is not null (safety net — `auth:sanctum` runs before this)
2. Gets `$request->user()->role->value` as plain string
3. Checks if it's in the allowed `$roles` array
4. Returns `403` if not

**Note:** `$request->user()` is guaranteed non-null here because `auth:sanctum`
runs before `RoleMiddleware` in the middleware stack and already rejects
unauthenticated requests with a 401.

---

### `app/Http/Middleware/BlacklistIp.php`

Checks `$request->ip()` against a hardcoded array of blocked IPs.

**Known limitation:** `$request->ip()` returns `127.0.0.1` in local development
via `php artisan serve` regardless of your actual machine IP. This works correctly
on a deployed server.

---

### Rate Limiting — `AppServiceProvider.php`

Uses Laravel's built-in `RateLimiter` facade. Configured in `boot()`.

| Role | Limit |
|---|---|
| Admin | None |
| Manager | 60 requests/minute (keyed by user ID) |
| Employee | 30 requests/minute (keyed by user ID) |
| Unauthenticated | 10 requests/minute (keyed by IP) |

Returns HTTP `429 Too Many Requests` when exceeded.
Rate limit state is stored in Redis database 0.

---

## Events & Listeners

Events and Listeners decouple side effects from business logic. Instead of calling
multiple services in one place, you fire one event and each Listener handles its
own responsibility independently.

### `app/Events/TaskCompleted.php`

A data container — holds the `Task` model and carries it to all Listeners.
Uses `Dispatchable` (enables `TaskCompleted::dispatch($task)`) and
`SerializesModels` (safely serializes Eloquent models if queued).

**Dispatch condition (in TaskService):**
```php
if status changes to 'completed' AND was not already 'completed'
    → TaskCompleted::dispatch($task)
```

---

### `app/Listeners/NotifyProjectManager.php`

Handles `TaskCompleted`. Gets the task's project owner (Manager) and
logs a notification message.

In production this would send a `Mailable` via SMTP. To make it
asynchronous, implement `ShouldQueue` — Laravel will push it to the
jobs table and a background worker will execute it, preventing the
API request from waiting for the email to send.

---

### `app/Listeners/LogTaskCompletion.php`

Handles `TaskCompleted`. Logs a completion record with task and project name.

Both Listeners are registered in `AppServiceProvider::boot()`:

```php
Event::listen(TaskCompleted::class, NotifyProjectManager::class);
Event::listen(TaskCompleted::class, LogTaskCompletion::class);
```

They fire independently — one failing does not affect the other.

---

## Task Scheduling

### `app/Console/Commands/MarkOverdueTasks.php`

**Signature:** `tasks:mark-overdue`

**Logic:**
- Queries Tasks where status is NOT `completed` or `overdue`
- AND `deadline` is in the past
- AND `deadline` is not null
- Bulk updates matching rows to `overdue`
- Logs the count of updated tasks

**Why bulk update instead of looping:**
`->update()` runs a single SQL query regardless of row count.
Looping would run one query per task — catastrophic at scale.

**Schedule registration — `routes/console.php`:**
```php
Schedule::command(MarkOverdueTasks::class)->dailyAt('00:00');
```

**Production cron (Linux server):**
```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

**Local testing:**
```bash
php artisan tasks:mark-overdue  # run manually
php artisan schedule:work       # simulate cron locally
```

**Laravel 13 note:** The command is generated with PHP Attribute syntax
(`#[Signature()]`, `#[Description()]`). These take precedence over the
`$signature` and `$description` properties. Do not mix both — use one
or the other.

---

## Redis Caching

### Strategy: Cache-Aside (Lazy Loading)

```
Request comes in
  → Check Redis for cached IDs
    → Hit: query DB with those IDs + relationships → return
    → Miss: query DB fully → store IDs in Redis → return
Mutation (create/update/delete)
  → Clear relevant cache keys immediately
```

### Why cache IDs, not full objects?

Storing Eloquent Collections directly in Redis causes `__PHP_Incomplete_Class`
errors on deserialization because PHP cannot reliably reconstruct complex objects
from serialized Redis data. Storing IDs (plain integers) is safe and clean.
Relationships are re-loaded fresh on every cache-hit retrieval.

### Cache Keys

| Key | Stores | TTL |
|---|---|---|
| `projects.user.{id}` | JSON array of project IDs | 10 minutes |
| `tasks.user.{id}` | JSON array of task IDs | 10 minutes |

**Full Redis key format:** `laravel-database-laravel-cache-projects.user.1`

Redis database: **1** (Laravel uses DB 1 for cache by default, DB 0 for other data).

**Inspecting cache (Memurai/Windows):**
```bash
memurai-cli -n 1 keys "*"      # list all cache keys
memurai-cli -n 1 dbsize        # count keys
memurai-cli -n 1 get "key"     # read a key value
```

### Cache Invalidation

- **ProjectService:** clears the creating/updating user's cache AND all Admin caches
  (Admins see all projects so their cache must also be invalidated)
- **TaskService:** clears cache for all users (tasks are visible across roles)

---

## Routes

### `routes/api.php`

```
POST   /api/login              → Public
POST   /api/logout             → auth:sanctum
GET    /api/me                 → auth:sanctum
POST   /api/register           → auth:sanctum + role:admin
GET    /api/projects           → auth:sanctum + role:admin,manager
POST   /api/projects           → auth:sanctum + role:admin,manager
GET    /api/projects/{id}      → auth:sanctum + role:admin,manager
PUT    /api/projects/{id}      → auth:sanctum + role:admin,manager
DELETE /api/projects/{id}      → auth:sanctum + role:admin,manager
GET    /api/tasks              → auth:sanctum + role:admin,manager
POST   /api/tasks              → auth:sanctum + role:admin,manager
GET    /api/tasks/{id}         → auth:sanctum + role:admin,manager,employee
PUT    /api/tasks/{id}         → auth:sanctum + role:admin,manager,employee
DELETE /api/tasks/{id}         → auth:sanctum + role:admin,manager
```

`Route::apiResource` generates 5 routes (index, store, show, update, destroy).
It excludes `create` and `edit` which return HTML forms — irrelevant for APIs.

Task show and update are split into a separate group to allow Employee access.

---

## Bootstrap

### `bootstrap/app.php`

The application ignition file. Wires up:

- `ForceJsonMiddleware` — appended globally
- `BlacklistIp` — appended globally
- `RoleMiddleware` — aliased as `role`
- `ThrottleRequests` — aliased as `throttle.api`

Also registers `MarkOverdueTasks` command explicitly via `->withCommands([])`.

**Laravel 13 note:** Commands are auto-discovered from `app/Console/Commands`
but explicit registration in `bootstrap/app.php` ensures they're always found.

---

## Known Errors & Fixes

### `Attempt to read property "value" on null` in Resource
**Cause:** Model returned from `create()` doesn't include DB default values
(like `status`) — defaults are set at the database level, not PHP level.
**Fix:** Call `->fresh()` after `create()` to reload the full row from the DB.

---

### `Attempt to read property "id" on null` on relationship in Resource
**Cause:** Relationship not loaded on the model being passed to the Resource.
**Fix:** Use `->load(['relation'])` or `Task::with(['relation'])->find($id)`.

---

### `Call to undefined relationship [assignee]`
**Cause:** Typo in relationship method name on the model (`asignee` instead of `assignee`).
**Fix:** Check `app/Models/Task.php` — method must be named exactly `assignee()`.

---

### `Too few arguments to function TaskCompleted::__construct()`
**Cause:** `TaskCompleted::dispatch()` called without passing the `$task` argument.
**Fix:** `TaskCompleted::dispatch($task)` — the task must be passed explicitly.

---

### `There are no commands defined in the "tasks" namespace`
**Cause:** Laravel 13 generates commands with PHP Attribute `#[Signature()]` which
overrides the `$signature` property. If both exist and conflict, the attribute wins.
**Fix:** Use only one — either remove the attributes and keep the properties, or
update the attribute to match your intended signature.

---

### `__PHP_Incomplete_Class returned` from Redis cache
**Cause:** Eloquent Collections stored directly in Redis cannot be reliably
deserialized back to PHP objects.
**Fix:** Store only IDs as JSON (`$collection->pluck('id')->toJson()`), re-query
with relationships on cache hit.

---

### `Cache::put` returns `true` but Memurai shows no keys
**Cause:** Laravel's Redis cache uses **database 1** by default. `memurai-cli`
connects to database 0 by default.
**Fix:** Use `memurai-cli -n 1 keys "*"` to inspect the correct database.

---

### `memurai-cli keys *` returns error on Windows
**Cause:** Windows shell interprets `*` as a glob before passing to memurai-cli.
**Fix:** Quote the wildcard: `memurai-cli -n 1 keys "*"`

---

### `$this->authorize()` — method not found in Controller
**Cause:** Laravel 13's base Controller class is nearly empty and does not include
`AuthorizesRequests` by default.
**Fix:** Add `use Illuminate\Foundation\Auth\Access\AuthorizesRequests;` and
`use AuthorizesRequests;` to `app/Http/Controllers/Controller.php`.
````

---

Both files are complete. Copy them into your project root. When you're ready, tell me and we'll plan your solo rebuild.
