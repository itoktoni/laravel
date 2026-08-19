# AGENTS.md — Laravel CMS Project Guide for AI Agents

> **Project:** `itoktoni/starterkit` — Laravel 13+ Livewire CMS + WMS  
> **Purpose:** Guidelines for AI agents (Zoo Code / Cline / Copilot) to create files, functions, and features consistently across this codebase.

---

## Tech Stack

| Category | Technology |
|---|---|
| **Framework** | Laravel 13.x (PHP ^8.3) |
| **Frontend** | Livewire 4.x + Flux UI 2.x + Tailwind CSS + Vite |
| **Auth** | Laravel Fortify + Sanctum (API) |
| **CMS** | Orbit (flat-file JSON, `ryangjchandler/orbit`) |
| **CRUD Actions** | `lorisleiva/laravel-actions` |
| **Auto Routes** | `izniburak/laravel-auto-routes` |
| **Real-time** | Centrifugo |
| **PDF** | `barryvdh/laravel-dompdf` |
| **Barcode** | `milon/barcode` |
| **Charts** | `arielmejiadev/larapex-charts` |
| **Testing** | Pest PHP + Laravel Dusk |
| **Enums** | `bensampo/laravel-enum` (^6.14) |
| **AI Boost** | Laravel Boost with Zoo Code agent |
| **DB** | MySQL (primary) + SQLite (Orbit flat-file) |

---

## Directory Structure

```
app/
├── Actions/           # Laravel Actions (Create/Update/Delete via lorisleiva)
│   └── Fortify/       # Fortify action overrides
├── Boost/Agents/      # Laravel Boost AI agent definitions
├── Charts/            # Larapex chart classes
├── Concerns/          # Shared traits (reusable behavior)
├── Console/Commands/  # Artisan commands
├── Contracts/         # Interfaces
├── Enums/              # Enum classes (bensampo/laravel-enum)
│   ├── Wms/            # WMS-specific enums
│   └── Telegram/       # Telegram content type enums
├── Http/
│   ├── Controllers/
│   │   ├── Api/       # API controllers (Media, etc.)
│   │   ├── Auth/      # Fortify auth controllers
│   │   ├── Cms/       # CMS controllers (Content, Section, Field, Type, etc.)
│   │   └── Wms/       # WMS controllers (Product, Stock, PO, SO, etc.)
│   ├── Livewire/      # Legacy Livewire components (prefer app/Livewire/)
│   ├── Middleware/     # HTTP middleware
│   └── Requests/      # Form request classes
├── Jobs/              # Queued jobs
├── Listeners/         # Event listeners
├── Livewire/          # Livewire components (main location)
│   └── Cms/           # CMS-specific Livewire components
├── Models/            # Eloquent + Orbit models
├── Notifications/     # Email/notification classes
├── Policies/          # Authorization policies
├── Providers/         # Service providers
├── Services/          # Business logic services
├── Telegram/          # Telegram content generation
│   └── ContentType/   # Telegram content type strategies
└── Wms/               # WMS-specific enums

config/                # Configuration files
database/
├── factories/         # Model factories
├── migrations/        # Database migrations
└── seeders/           # Database seeders

function/
└── Global.php         # Global helper functions + constants

resources/views/
├── components/        # Blade components
│   ├── cms/           # CMS-specific components
│   ├── form/          # Form components
│   └── warehouse/     # WMS-specific components
├── frontend/          # Public-facing views
│   ├── layouts/
│   └── sections/      # Dynamic content sections
├── layouts/           # Admin/auth layouts
├── livewire/          # Livewire component views
├── pages/             # CRUD page views (pages/{module}/{action})
│   └── {module}/      # One folder per module
│       └── partials/  # Module-specific partials
└── pdf/               # PDF templates

routes/
├── web.php            # Main web routes
├── api.php            # Sanctum API routes
└── settings.php       # Profile/settings routes
```

---

## Naming Conventions

| Item | Convention | Example |
|---|---|---|
| **Model** | `PascalCase` singular | `Content.php` → `class Content` |
| **Controller** | `PascalCase` + `Controller` suffix | `ProductController.php` |
---

## Model Pattern

### Standard Database Model

```php
<?php

namespace App\Models;

class Product extends BaseModel
{
    protected $table = 'product';                    // table name
    protected $primaryKey = 'product_id';            // primary key
    public $timestamps = true;

    // Filterable & sortable columns (for laravel-purity)
    public static $filterColumns = ['column_a', 'column_b'];
    public static $sortColumns = ['column_a', 'column_b'];

    protected $fillable = ['column_a', 'column_b'];
    protected $casts = [
        'decimal_col' => 'decimal:2',
        'json_col'    => 'array',
        'bool_col'    => 'boolean',
        'date_col'    => 'datetime',
    ];

    // REQUIRED: display name field for UI
    public static function field_name(): string
    {
        return 'column_a';
    }

    // REQUIRED: validation rules
    public function rules(): array
    {
        return [
            'column_a' => ['required', 'string', 'max:255'],
            'column_b' => ['nullable', 'integer'],
        ];
    }

    // Lifecycle hooks
    protected static function booted(): void
    {
        static::created(function (self $model) { /* ... */ });
    }

    // Relationships
    public function relationName()
    {
        return $this->hasMany(Related::class, 'foreign_key', 'local_key');
    }

    // Accessors
    public function getCustomAttribute()
    {
        return $this->relationLoaded('stock')
            ? $this->stock->sum('qty')
            : $this->stock()->sum('qty');
    }
}
```

### Orbit (Flat-File JSON) Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class Example extends BaseModel
{
    use Orbital;

    protected $fillable = ['name', 'slug', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public static $sortColumns = ['name'];
    public static $filterColumns = ['name'];

    public static function field_name(): string
    {
        return 'name';
    }

    // REQUIRED: define database schema
    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('name');
        $table->string('slug')->nullable();
        $table->json('data')->nullable();
    }

    // REQUIRED: return 'json' for flat-file
    public static function getOrbitalDriver(): string
    {
        return 'json';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
        ];
    }
}
```

**Important:** Orbit models CANNOT use `belongsToMany` — they use separate SQLite databases so pivot tables won't cross-connect. Store category/tag IDs as JSON arrays instead.
| **Livewire** | `PascalCase` matching filename | `FlexibleContainer.php` |
| **Policy** | `PascalCase` + `Policy` suffix | `ContentPolicy.php` |
| **Service** | `PascalCase` + `Service` suffix | `CentrifugoService.php` |
| **Trait/Concern** | `PascalCase` + `Trait` suffix | `ControllerTrait.php` |
| **Enum** | `PascalCase` + `Enum` suffix | `StatusEnum.php` |
| **Event** | `PascalCase` past-tense verb | `NotificationSent.php` |
| **Job** | `PascalCase` descriptive | `SendTelegramContentJob.php` |
| **Listener** | `PascalCase` descriptive | `SendNotificationViaCentrifugo.php` |
| **Request** | `PascalCase` + `Request` suffix | `GeneralRequest.php` |
| **Migration** | Laravel default timestamp prefix | `2025_01_01_000000_create_xxx.php` |

### Database Tables
- Legacy WMS: `snake_case` singular (e.g., `product`, `stock`, `po`)
- New tables: Laravel convention `snake_case` plural recommended

### View Templates
- **Page views:** `pages/{module}/{action}.blade.php`
  - Module = controller name without "Controller", lowercase
  - Action: `table`, `form` (for create/update), `show`, custom
- **Components:** `components/{group}/{name}.blade.php`
- **Livewire views:** `livewire/{component-name}.blade.php`
- **Frontend:** `frontend/{page}.blade.php`

### Routes
- Named routes: `{prefix}-{module}.{action}` (e.g., `wms-product.table`)
- Use `Route::auto()` macro for standard CRUD routing
---

## Controller Pattern

### Minimal Controller (uses trait defaults)

```php
<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Supplier;

class SupplierController extends Controller
{
    use ControllerTrait;

    public function __construct(Supplier $model)
    {
        $this->model = $model::getModel();
    }
}
```

### Full Controller with Custom Logic

```php
<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ControllerTrait;

    public function __construct(Product $model)
    {
        $this->model = $model::getModel();
    }

    // Override share() to add view data
    protected function share($data = [])
    {
        return array_merge([
            'model'          => $this->model,
            'categoryOptions' => Category::pluck('name', 'id'),
        ], $data);
    }

    // Override getData() for custom queries
    protected function getData()
    {
        return $this->model->with('stock')->filter()->sort();
    }

    // Custom action method
    public function getCustomAction(Request $request, int $id)
    {
        $record = $this->model->findOrFail($id);

        return $this->views('pages.product.custom', [
            'model' => $record,
        ]);
    }
}
```

### Key Controller Conventions

- **Always use `$model::getModel()`** to ensure a fresh instance
- **`share($data)`**: add data available to all views; always call `array_merge()` with defaults
- **`getData()`**: customize the base query for `getTable` (eager loading, scopes)
- **`views($view, $data)`**: render view or JSON based on request type
- **`template()`**: auto-resolves view path as `pages.{module}.{action}` where module = controller name minus "Controller"
- **`response($payload)`**: send JSON for API, flash+redirect for web

### Controller Action Methods (from ControllerTrait)

| Method | Route Action | Purpose |
|---|---|---|
| `index()` | `index` | Redirects to `getTable` |
| `getTable()` | `table` | Paginated data table |
| `getCreate()` | `form` | Create form |
| `postCreate()` | `form` (POST) | Handle create |
| `getUpdate($id)` | `form` | Edit form |
| `postUpdate($id)` | `form` (POST) | Handle update |
| `getDelete($id)` | `delete` | Single delete |
| `postDelete()` | `delete` (POST) | Bulk delete |
| `getShow($id)` | `show` | Show record (API-normally) |

---

## Policy Pattern

All policies extend `BasePolicy` — extremely minimal:

```php
<?php

namespace App\Policies;

class ProductPolicy extends BasePolicy {}
```

`BasePolicy` reads `config('permision.php')` which maps roles to allowed actions per module.

---

## Enum Pattern

Use **`bensampo/laravel-enum`** (class extending `BenSampo\Enum\Enum`) with `EnumTrait`:

```php
<?php

namespace App\Enums;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class StatusEnum extends Enum
{
    use EnumTrait;

    const PENDING  = 'pending';
    const APPROVED = 'approved';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::PENDING  => 'Pending',
            self::APPROVED => 'Approved',
            default => parent::getDescription($value),
        };
    }
}
```

### Enum Folder Structure

```
app/Enums/
├── RoleEnum.php            # App-wide enums
├── StatusEnum.php
├── Wms/                    # WMS domain enums
│   ├── MasukStatusEnum.php
│   ├── PoStatusEnum.php
│   └── SoStatusEnum.php
└── Telegram/               # Telegram domain enums
    └── TelegramContentType.php
```

### Key Conventions

- All enums extend `BenSampo\Enum\Enum` (not native PHP enums)
- All enums use `App\Concerns\EnumTrait` for `getOptions()` and `getApi()`
- Constants define key→value pairs: `const KEY = 'value';`
- `getDescription(mixed $value): string` — static, uses `match($value)` not `$this`
- Domain-specific enums go in subdirectories: `App\Enums\Wms\`, `App\Enums\Telegram\`
- `getOptions()` → `['pending' => 'Pending', 'approved' => 'Approved']` (for select inputs)
- `getApi()` → `[['id' => 'pending', 'name' => 'Pending'], ...]` (for API responses)
- Built-in bensampo methods: `getKeys()`, `getValues()`, `asArray()`, `asSelectArray()`, `fromValue()`, `coerce()`, `getRandomInstance()`, etc.

---

## Service Pattern

```php
<?php

namespace App\Services;

class MyService
{
    public function doSomething(string $param): mixed
    {
        // Business logic
    }
}

// Usage:
$service = app(MyService::class);
$result  = $service->doSomething('value');
```

Services are plain PHP classes. Use DI (`app()`) or constructor injection.

---

## Livewire Component Pattern

```php
<?php

namespace App\Livewire\Cms;

use Livewire\Component;
use App\Models\SomeModel;

class ExampleComponent extends Component
{
    public int $id;
    public string $name = '';
    public array $items = [];

    protected $listeners = ['refresh' => '$refresh'];

    public function mount(int $id)
    {
        $this->id = $id;
        // Initialize state
    }

    public function someAction()
    {
        // Handle action
    }

    public function render()
    {
        return view('livewire.cms.example-component', [
            'data' => SomeModel::find($this->id),
        ]);
    }
}
```

- Components in `app/Livewire/{Group}/`
- Views in `resources/views/livewire/{group}/{component-name}.blade.php`
- Public properties are reactive; use `$listeners` for events

---

## Laravel Action Pattern (lorisleiva)

```php
// Usage in controllers:
$response = CreateAction::run($request, $this->model);
$response = UpdateAction::run($request, $id, $this->model);
$response = DeleteAction::run($request, $this->model);
```

Actions return a payload array:
```php
[
    'code'    => 200,        // HTTP status
    'status'  => true,       // success/fail
    'message' => '...',      // TOAST_SUCCESS or TOAST_FAILED
    'data'    => $result,     // the model or error message
]
```

When creating new actions: extend from `Lorisleiva\Actions\Action`, use `AsAction` trait.
---

## Route Registration

### Standard CRUD (auto-routes)

```php
// In routes/web.php inside the auth middleware group:

Route::auto('/wms/product', 'Wms\ProductController', ['name' => 'wms-product']);
// Generates: GET /wms/product → getTable, GET /wms/product/create → getCreate, etc.
```

### Custom Routes

```php
Route::get('/wms/product/{id}/qrcode', [ProductController::class, 'getQrcode'])
    ->name('wms-product.getQrcode');

Route::post('/wms/product/{id}/qrcode', [ProductController::class, 'postQrcode'])
    ->name('wms-product.postQrcode');
```

### Livewire Routes

```php
Route::livewire('settings/appearance', 'pages::settings.appearance')
    ->name('appearance.edit');
```

### Frontend Public Routes

```php
Route::get('/{slug}', [PublicController::class, 'page'])->name('page');
```

---

## Request/Validation Pattern

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneralRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Reads model from controller and checks policy
        $controller = request()->route()->getController();
        $model = $controller->model ?? null;
        if ($model === null) return true;

        $action = str_replace(['get', 'post'], '', strtolower(
            request()->route()->getActionMethod()
        ));
        $action = $action === 'index' ? 'table' : $action;

        return $this->user()->can($action, $model);
    }

    public function rules(): array
    {
        return [];
    }
}
```

- Model validation rules are defined in the model's `rules()` method
- Controller uses `$request->validate($this->model->rules())` or the Laravel Action handles it

---

## Global Helpers & Constants

Defined in `function/Global.php` (autoloaded via `composer.json` `files`):

```php
// Constants
define('ACTION_CREATE', 'getCreate');
define('ACTION_UPDATE', 'getUpdate');
define('TOAST_SUCCESS', 'Data berhasil di proses !');
define('TOAST_FAILED', 'Proses Error !');

// Key functions
formatDate($value, $datetime = false)   // Format date d/m/Y
formatAngka(int $value, $simbol = null) // Format number with thousand separator
formatQty($value)                       // Smart decimal formatting
module($action = null)                  // Current route module name
moduleLabel()                           // Human-readable module label from menu config
unic_string($length)                    // Random uppercase string
unic_number($length)                    // Random integer
renderFieldInput($fName, $fType, ...)   // Render form input HTML for custom fields
```

---

## Events & Listeners Pattern

```php
// Event
<?php
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockCreated
{
    use Dispatchable, SerializesModels;
    public function __construct(public $stock) {}
}

// Listener
<?php
namespace App\Listeners;

class LogStockActivity
{
    public function handle(StockCreated $event): void { /* ... */ }
}

// Register in AppServiceProvider::boot():
Event::listen(NotificationSent::class, SendNotificationViaCentrifugo::class);
```

---

## Notification Service Pattern

Notifications use a Channel Factory pattern in `app/Services/Notification/`:

```php
$channel = NotificationChannelFactory::create($type); // 'email', 'telegram', 'whatsapp', 'log'
$channel->send($recipient, $subject, $body);
```

Available channels: `EmailChannel`, `TelegramChannel`, `WhatsAppChannel` (with multiple providers: `FonnteProvider`, `TwilioProvider`, `CustomProvider`, `LogProvider`), `LogChannel`.
---

## File Creation Checklist

When creating a new feature/module, follow these steps:

1. **[ ] Model** — `app/Models/Name.php` extending `BaseModel` (or with `Orbital` trait for flat-file)
   - Set `$table`, `$primaryKey` (if non-standard), `$fillable`, `$casts`
   - Define `$filterColumns`, `$sortColumns` static properties
   - Define `field_name()` static method
   - Define `rules()` method for validation
   - Define relationships
   - Define `static function schema(Blueprint $table)` if using Orbit

2. **[ ] Migration** — `database/migrations/YYYY_MM_DD_create_table_name.php` (if database model)

3. **[ ] Policy** — `app/Policies/NamePolicy.php` extending `BasePolicy`

4. **[ ] Controller** — `app/Http/Controllers/{Group}/NameController.php`
   - Use `ControllerTrait`
   - Set `$this->model` in constructor
   - Override `share()` if extra view data needed
   - Override `getData()` if custom query needed

5. **[ ] Route** — Add `Route::auto()` in `routes/web.php`

6. **[ ] Views** — Create in `resources/views/pages/{module}/`:
   - `table.blade.php` — data table view
   - `form.blade.php` — create/edit form

7. **[ ] Permission** — Update `config/permision.php` if role restrictions needed

8. **[ ] Menu** — Update `config/menu.php` to add navigation item

9. **[ ] Tests** — Pest tests in `tests/`

---

## Important Project-Specific Details

### Laravel Boost / Zoo Code Agent
- The project registers a custom `zoo_code` agent via `App\Boost\Agents\CustomAgent`
- Guidelines file: `AGENTS.md` (this file)
- MCP config: `.roo/mcp.json`
- Skills directory: `.agents/skills/`

### Orbit Models (Flat-file CMS)
- Content types: `Type`, `Section`, `Field` are Orbit models
- Content entries: `Content` is also Orbit (but can be mixed)
- These use `.json` files in `content/` directory
- **No belongsToMany** relationships — store references as JSON arrays

### Model Aliases
- `ModelAliasServiceProvider` auto-creates global class aliases for all models
- Example: `use App\Models\Product` → can also be referenced as just `Product`

### Permission System
- Config file: `config/permision.php`
- `BasePolicy` checks `$restrict[$role][$module]` array for allowed actions
- `GeneralRequest::authorize()` calls `$user->can($action, $model)`

### Multi-language
- Lang files in `lang/en/` and `lang/id/`
- Indonesian is primary language

### NativePHP Mobile
- Mobile app support via `nativephp/mobile`
- Routes defined in `routes/web.php` with mobile-specific views
---

## Command Reference

```bash
# Development
composer dev              # Start server + queue + vite concurrently

# Testing
composer test             # Run tests with lint check
php artisan test          # Run Pest tests only
php artisan dusk          # Browser tests

# Linting
composer lint             # Fix linting with Pint
composer lint:check       # Check linting only

# Code Generation
php artisan make:model    # Create model
php artisan make:livewire # Create Livewire component
php artisan boost:update  # Update Laravel Boost
```

---

## Code Style Notes

- PHP uses 4-space indentation, no tabs
- String literals use double quotes (preferred in this project)
- Array syntax: `[]` (short array syntax)
- Type hints: use PHP 8.3 type declarations everywhere
- Return types: always declare return types (`: void`, `: string`, `: array`)
- `declare(strict_types=1)` only where explicitly needed
- Comments: `//` for single-line, `/** */` for docblocks
- PSR-4 autoloading: `App\` → `app/`, `Function\` → `function/`, `Tests\` → `tests/`
- Some files have `// ponytail:` comments — these are code review notes from a previous AI agent; include similar context notes when making non-obvious decisions
