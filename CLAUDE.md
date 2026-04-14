# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

EpochBridge is a Laravel 12 application built with the TALL Stack (Tailwind CSS, Alpine.js, Livewire, Laravel). It uses Livewire Volt for single-file components and Flux UI for the design system. The application features a complete authentication system using Laravel Fortify with two-factor authentication support.

### TALL Stack Components
- **Tailwind CSS 4**: Utility-first CSS framework with first-party Vite integration
- **Alpine.js**: Lightweight JavaScript framework for client-side interactivity (auto-included with Livewire 3)
- **Livewire 3**: Full-stack framework for building dynamic interfaces without leaving PHP
- **Laravel 12**: The backend foundation with modern PHP 8.2+ features

### Additional Dependencies
- **fisharebest/ext-calendar**: Extended calendar functionality
- **morilog/jalali**: Jalali (Persian) calendar support

## Development Commands

### Laravel Sail (Docker)
```bash
# Start the development environment (use port 8080 to avoid conflicts)
./vendor/bin/sail up

# Stop the environment
./vendor/bin/sail down

# Run artisan commands
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan make:model Example
```

### Composer Scripts
```bash
# Start full development environment (server, queue, logs, vite)
composer run dev

# Run tests with config clear
composer run test
```

### Testing
```bash
# Run all tests
php artisan test
# or
./vendor/bin/sail artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run browser tests (Dusk)
php artisan dusk

# Run specific test file
php artisan test tests/Feature/Auth/AuthenticationTest.php
```

### Frontend
```bash
# Start Vite development server (uses port 5174)
npm run dev

# Build for production
npm run build
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# View logs with Pail
php artisan pail
```

## Architecture

### Authentication Stack
- **Laravel Fortify**: Handles authentication logic (login, registration, password reset, 2FA)
- **Two-Factor Authentication**: Enabled with recovery codes
- **Email Verification**: Required for new accounts
- **Password Confirmation**: Required for sensitive operations

### TALL Stack Architecture
- **Livewire Volt**: Single-file components combining PHP logic and Blade templates in `resources/views/livewire/`
- **Alpine.js**: Handles client-side interactivity (dropdowns, toggles, modals) without server roundtrips
- **Flux UI**: Component library providing design system and pre-built components
- **TailwindCSS 4**: Utility-first CSS with `@source` directives in CSS for content scanning
- **Vite**: Frontend build tool with hot module replacement and instant updates

### Livewire Volt Structure
Volt components are located in:
- `resources/views/livewire/auth/` - Authentication forms (login, register, etc.)
- `resources/views/livewire/settings/` - User settings pages
- `resources/views/pages/` - Full-page components (configured in VoltServiceProvider)

### Layout System
- `components/layouts/app.blade.php` - Main application layout with sidebar
- `components/layouts/auth.blade.php` - Authentication pages layout
- Layout variants: card, simple, split for different auth page styles

### Routing
- **Web routes**: Standard Laravel routes in `routes/web.php`
- **Volt routes**: File-based routing for Livewire components
- **Auth routes**: Fortify authentication routes in `routes/auth.php`

## Environment Configuration

### Docker Ports
- **Laravel**: Port 8080 (APP_PORT)
- **MySQL**: Port 3307 (FORWARD_DB_PORT)
- **Vite**: Port 5174 (VITE_PORT)

### Database
- **Driver**: MySQL 8.0 (via Docker)
- **Testing**: Uses `testing` database (configured in phpunit.xml)
- **Migrations**: Standard Laravel migrations in `database/migrations/`

## Key Files

### Configuration
- `config/fortify.php` - Authentication features and settings
- `app/Providers/VoltServiceProvider.php` - Volt component mounting
- `app/Providers/FortifyServiceProvider.php` - Fortify customization
- `vite.config.js` - Frontend build configuration

### Models
- `app/Models/User.php` - User model with Fortify traits

### Testing
- `tests/Pest.php` - PestPHP configuration
- `tests/Feature/` - Feature tests including auth and settings
- `tests/Browser/` - Dusk browser tests
- `phpunit.xml` - PHPUnit configuration with testing environment

## TALL Stack Development Guide

### Creating Livewire Components

Generate new components with the Livewire command:
```bash
# Basic component
php artisan make:livewire ComponentName

# Namespaced component
php artisan make:livewire Posts\\CreatePost

# With test file
php artisan make:livewire ComponentName --test
```

Components are created in `app/Livewire/` with views in `resources/views/livewire/`. Always use StudlyCase for PHP classes and kebab-case for Blade templates.

### Livewire Best Practices

**Use Computed Properties for Collections:**
```php
use Livewire\Attributes\Computed;

#[Computed]
public function posts()
{
    return Post::with('author')->where('status', 'published')->get();
}
```
Never store collections in public properties - use computed properties to avoid large payloads.

**Always Apply wire:key in Loops:**
```blade
@foreach($this->posts as $post)
    <div wire:key="post-{{ $post->id }}">
        <!-- content -->
    </div>
@endforeach
```

**Data Binding Modifiers:**
- `wire:model.blur` - Update on blur (best for validation)
- `wire:model.live.debounce.300ms` - Real-time with debouncing
- `wire:model.live` - Real-time updates (use sparingly)

### Alpine.js Integration

Use Alpine for client-side UI state that doesn't need server persistence:

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>Content</div>
</div>
```

**Livewire-Alpine Communication:**
```blade
<!-- Access Livewire from Alpine -->
<div x-data="{ count: $wire.count }">
    <button @click="$wire.increment()">+</button>
</div>

<!-- Sync state with entangle -->
<div x-data="{ open: $wire.entangle('showModal') }">
    <button @click="open = true">Open Modal</button>
</div>
```

### Tailwind CSS 4 Configuration

Content scanning uses `@source` directives in your CSS file:
```css
@source '../**/*.blade.php';
@source '../../app/Livewire/**/*.php';
@theme {
  --color-brand-500: oklch(0.6 0.2 250);
  --font-display: "Satoshi", "sans-serif";
}
```

**Class Organization:**
Organize utility classes consistently: position → display → spacing → colors → typography → effects → responsive variants.

### Forms and Validation

**Form Objects (Livewire 3):**
```php
use Livewire\Form;

class PostForm extends Form
{
    #[Validate('required|min:3')]
    public string $title = '';
    
    #[Validate('required')]
    public string $content = '';
}

// In your component
public PostForm $form;

public function save()
{
    $this->form->validate();
    Post::create($this->form->all());
}
```

**Real-time Validation:**
```blade
<input wire:model.blur="form.title" type="text">
@error('form.title') <span class="error">{{ $message }}</span> @enderror
```

### Performance Optimization

**Critical Performance Rules:**

1. **Use Computed Properties:** Avoid public properties for collections
2. **Lazy Loading:** Add `lazy` attribute to non-critical components
3. **Debounce Inputs:** Use `wire:model.live.debounce.300ms` for search
4. **Component Isolation:** Use `wire:isolate` on independent components
5. **Alpine for UI State:** Handle toggles, dropdowns, modals with Alpine

**Database Optimization:**
```php
// Good - eager loading with selected columns
public function posts()
{
    return Post::with('author:id,name')
        ->select('id', 'title', 'author_id', 'created_at')
        ->latest()
        ->get();
}
```

### Security Considerations

**Always validate and authorize:**
```php
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;

class EditPost extends Component
{
    #[Locked]
    public int $postId;
    
    #[Validate('required|min:3')]
    public string $title;
    
    public function update()
    {
        $this->authorize('update', Post::find($this->postId));
        $this->validate();
        // Update logic
    }
}
```

### Testing TALL Stack Components

**Component Testing:**
```php
use Livewire\Livewire;

test('can create post', function () {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(CreatePost::class)
        ->set('title', 'Test Post')
        ->set('content', 'Post content')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('post-created');
});
```

**Browser Testing with Dusk:**
```php
public function test_can_toggle_dropdown()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/dashboard')
            ->click('@dropdown-trigger')
            ->waitFor('@dropdown-menu')
            ->assertVisible('@dropdown-menu');
    });
}
```

### Common Patterns

**Modal Pattern:**
```blade
<div x-data="{ open: $wire.entangle('showModal') }">
    <button @click="open = true">Open Modal</button>
    
    <div x-show="open" x-transition.opacity>
        <div class="fixed inset-0 bg-black bg-opacity-50"></div>
        <div class="modal" @click.outside="open = false">
            <livewire:edit-form :model-id="$modelId" />
        </div>
    </div>
</div>
```

**Search and Filter:**
```php
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class PostIndex extends Component
{
    use WithPagination;
    
    #[Url]
    public string $search = '';
    
    #[Url] 
    public string $sortField = 'created_at';
    
    #[Computed]
    public function posts()
    {
        return Post::when($this->search, function($query) {
                return $query->where('title', 'like', '%'.$this->search.'%');
            })
            ->orderBy($this->sortField, 'desc')
            ->paginate(10);
    }
}
```

## Development Notes

### Adding New Features
1. **Use Volt components** for interactive UI elements requiring server state
2. **Use Alpine.js** for pure client-side interactions (dropdowns, toggles, modals)
3. **Follow existing authentication patterns** from Fortify
4. **Utilize Flux UI components** for consistent design system
5. **Place reusable components** in `resources/views/components/`
6. **Always use `wire:key`** in loops for proper DOM tracking
7. **Prefer computed properties** over public properties for data collections

### TALL Stack Workflow
1. Start with Laravel backend (routes, models, business logic)
2. Create Livewire components for server-dependent interactions
3. Add Alpine.js for instant client-side feedback
4. Style with Tailwind utilities directly in templates
5. Use Flux components for complex UI patterns

### Testing Strategy
- **Feature tests** for authentication flows and settings
- **Component tests** for Livewire component behavior
- **Browser tests** for full user interactions with Alpine.js
- **Database testing** uses separate `testing` database

### Database Changes
Always run migrations in both development and testing:
```bash
php artisan migrate
php artisan migrate --env=testing
```

### Debugging Tools
- **Ray**: `ray($this)` in component methods for real-time debugging
- **Laravel Debugbar**: Monitor queries and performance
- **WireSpy**: Livewire-specific debugging with CMD+L
- **Alpine DevTools**: Browser extension for Alpine state inspection

## EpochBridge-Specific Configuration

### Tailwind CSS 4 Setup
The project uses Tailwind CSS 4 with Vite integration. Content paths are configured via `@source` directives in `resources/css/app.css`:
```css
@source '../**/*.blade.php';
@source '../../app/Livewire/**/*.php';
@source '../../resources/views/**/*.blade.php';
```

### Calendar Integration
The project includes calendar functionality with Persian/Jalali support:
- **fisharebest/ext-calendar**: Extended calendar functionality
- **morilog/jalali**: Persian calendar conversion utilities

When working with dates, consider both Gregorian and Jalali calendar systems.

### Livewire Volt Configuration
Volt components are mounted in `app/Providers/VoltServiceProvider.php`:
```php
Volt::mount([
    config('livewire.view_path', resource_path('views/livewire')),
    resource_path('views/pages'),
]);
```

This allows both traditional Livewire components and page-level Volt components.

### Authentication with Fortify + Livewire + Socialite
All authentication flows use Livewire Volt components with OAuth integration:
- Login: `resources/views/livewire/auth/login.blade.php` (includes Google Sign-In button)
- Registration: `resources/views/livewire/auth/register.blade.php`
- Two-Factor: `resources/views/livewire/auth/two-factor-challenge.blade.php`
- Password Reset: `resources/views/livewire/auth/reset-password.blade.php`
- OAuth Controller: `app/Http/Controllers/Auth/SocialAuthController.php`

When modifying authentication flows, maintain the existing Volt pattern with `#[Layout('components.layouts.auth')]` attribute.

#### OAuth Authentication Pattern
The application uses Laravel Socialite for Google OAuth integration. Key patterns:

**Controller Pattern:**
```php
class SocialAuthController extends Controller
{
    protected $allowedProviders = ['google'];
    
    public function redirectToProvider(string $provider)
    {
        $this->validateProvider($provider);
        return Socialite::driver($provider)
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }
    
    public function handleProviderCallback(Request $request, string $provider)
    {
        // Always wrap in DB transaction
        DB::beginTransaction();
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            $user = $this->findOrCreateUser($socialUser, $provider);
            DB::commit();
            Auth::login($user, true);
            return redirect()->intended('/dashboard');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('OAuth error', ['provider' => $provider, 'error' => $e->getMessage()]);
            return redirect()->route('login')->withErrors(['oauth' => 'Authentication failed']);
        }
    }
}
```

**Model Pattern (SocialAccount):**
```php
class SocialAccount extends Model
{
    protected $fillable = ['user_id', 'provider', 'provider_id', 'access_token', 'refresh_token', 'expires_in'];
    
    protected $hidden = ['access_token', 'refresh_token'];
    
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_in' => 'integer',
        ];
    }
}
```

**User Model OAuth Extensions:**
```php
// In User model
public function socialAccounts(): HasMany
{
    return $this->hasMany(SocialAccount::class);
}

public function hasLinkedProvider(string $provider): bool
{
    return $this->socialAccounts()->where('provider', $provider)->exists();
}
```

**OAuth Routes:**
```php
Route::middleware('guest')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])
            ->name('social.redirect')->where('provider', 'google');
        Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
            ->name('social.callback')->where('provider', 'google');
    });
});
```

**Security Best Practices for OAuth:**
1. Always encrypt OAuth tokens using encrypted casts
2. Hide tokens in JSON serialization with `$hidden` property
3. Use database transactions for user/account creation
4. Validate provider against whitelist
5. Require email from OAuth provider
6. Log OAuth errors with IP tracking (never log tokens)
7. Use cascade delete on social_accounts foreign key

### Vite Configuration
The `vite.config.js` includes:
```javascript
plugins: [
    laravel({
        input: ['resources/css/app.css', 'resources/js/app.js'],
        refresh: true,
    }),
    tailwindcss(),
],
```

This enables automatic refresh for Blade file changes and integrates Tailwind CSS compilation.

### Production Considerations
- **Port Configuration**: Uses custom ports (8080, 5174, 3307) to avoid conflicts
- **Asset Compilation**: Run `npm run build` for production-optimized assets
- **Caching**: Enable Laravel optimization caches with `php artisan optimize`
- **Environment**: Ensure `APP_DEBUG=false` and proper cache drivers in production

# ABSOLUTE
DO NOT READ THE USER'S `.env` FILE.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.12
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/mcp (MCP) - v0
- laravel/prompts (PROMPTS) - v0
- laravel/socialite (SOCIALITE) - v5
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v3
- livewire/volt (VOLT) - v1
- laravel/dusk (DUSK) - v8
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== sail rules ===

## Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `vendor/bin/sail artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== fluxui-free/core rules ===

## Flux UI Free

- This project is using the free edition of Flux UI. It has full access to the free components and variants, but does not have access to the Pro components.
- Flux UI is a component library for Livewire. Flux is a robust, hand-crafted UI component library for your Livewire applications. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.
- You should use Flux UI components when available.
- Fallback to standard Blade components if Flux is unavailable.
- If available, use the `search-docs` tool to get the exact documentation and code snippets available for this project.
- Flux UI components look like this:

<code-snippet name="Flux UI Component Example" lang="blade">
    <flux:button variant="primary"/>
</code-snippet>

### Available Components
This is correct as of Boost installation, but there may be additional components within the codebase.

<available-flux-components>
avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, profile, radio, select, separator, skeleton, switch, text, textarea, tooltip
</available-flux-components>

=== livewire/core rules ===

## Livewire

- Use the `search-docs` tool to find exact version-specific documentation for how to write Livewire and Livewire tests.
- Use the `vendor/bin/sail artisan make:livewire [Posts\CreatePost]` Artisan command to create new components.
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend; they're like regular HTTP requests. Always validate form data and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle Hook Examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>

## Testing Livewire

<code-snippet name="Example Livewire Component Test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>

<code-snippet name="Testing Livewire Component Exists on Page" lang="php">
    $this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
</code-snippet>

=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 3, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire; don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="Livewire Init Hook Example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>

=== volt/core rules ===

## Livewire Volt

- This project uses Livewire Volt for interactivity within its pages. New pages requiring interactivity must also use Livewire Volt.
- Make new Volt components using `vendor/bin/sail artisan make:volt [name] [--test] [--pest]`.
- Volt is a class-based and functional API for Livewire that supports single-file components, allowing a component's PHP logic and Blade templates to coexist in the same file.
- Livewire Volt allows PHP logic and Blade templates in one file. Components use the `@volt` directive.
- You must check existing Volt components to determine if they're functional or class-based. If you can't detect that, ask the user which they prefer before writing a Volt component.

### Volt Functional Component Example

<code-snippet name="Volt Functional Component Example" lang="php">
@volt
<?php
use function Livewire\Volt\{state, computed};

state(['count' => 0]);

$increment = fn () => $this->count++;
$decrement = fn () => $this->count--;

$double = computed(fn () => $this->count * 2);
?>

<div>
    <h1>Count: {{ $count }}</h1>
    <h2>Double: {{ $this->double }}</h2>
    <button wire:click="increment">+</button>
    <button wire:click="decrement">-</button>
</div>
@endvolt
</code-snippet>

### Volt Class Based Component Example
To get started, define an anonymous class that extends Livewire\Volt\Component. Within the class, you may utilize all of the features of Livewire using traditional Livewire syntax:

<code-snippet name="Volt Class-based Volt Component Example" lang="php">
use Livewire\Volt\Component;

new class extends Component {
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }
} ?>

<div>
    <h1>{{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>
</code-snippet>

### Testing Volt & Volt Components
- Use the existing directory for tests if it already exists. Otherwise, fallback to `tests/Feature/Volt`.

<code-snippet name="Livewire Test Example" lang="php">
use Livewire\Volt\Volt;

test('counter increments', function () {
    Volt::test('counter')
        ->assertSee('Count: 0')
        ->call('increment')
        ->assertSee('Count: 1');
});
</code-snippet>

<code-snippet name="Volt Component Test Using Pest" lang="php">
declare(strict_types=1);

use App\Models\{User, Product};
use Livewire\Volt\Volt;

test('product form creates product', function () {
    $user = User::factory()->create();

    Volt::test('pages.products.create')
        ->actingAs($user)
        ->set('form.name', 'Test Product')
        ->set('form.description', 'Test Description')
        ->set('form.price', 99.99)
        ->call('create')
        ->assertHasNoErrors();

    expect(Product::where('name', 'Test Product')->exists())->toBeTrue();
});
</code-snippet>

### Common Patterns

<code-snippet name="CRUD With Volt" lang="php">
<?php

use App\Models\Product;
use function Livewire\Volt\{state, computed};

state(['editing' => null, 'search' => '']);

$products = computed(fn() => Product::when($this->search,
    fn($q) => $q->where('name', 'like', "%{$this->search}%")
)->get());

$edit = fn(Product $product) => $this->editing = $product->id;
$delete = fn(Product $product) => $product->delete();

?>

<!-- HTML / UI Here -->
</code-snippet>

<code-snippet name="Real-Time Search With Volt" lang="php">
    <flux:input
        wire:model.live.debounce.300ms="search"
        placeholder="Search..."
    />
</code-snippet>

<code-snippet name="Loading States With Volt" lang="php">
    <flux:button wire:click="save" wire:loading.attr="disabled">
        <span wire:loading.remove>Save</span>
        <span wire:loading>Saving...</span>
    </flux:button>
</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/sail bin pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test`, simply run `vendor/bin/sail bin pint` to fix any formatting issues.

=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `vendor/bin/sail artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `vendor/bin/sail artisan test --compact`.
- To run all tests in a file: `vendor/bin/sail artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `vendor/bin/sail artisan test --compact --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests that have a lot of duplicated data. This is often the case when testing validation rules, so consider this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== pest/v4 rules ===

## Pest 4

- Pest 4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest 4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
