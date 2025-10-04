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

### Authentication with Fortify + Livewire
All authentication flows use Livewire Volt components:
- Login: `resources/views/livewire/auth/login.blade.php`
- Registration: `resources/views/livewire/auth/register.blade.php`
- Two-Factor: `resources/views/livewire/auth/two-factor-challenge.blade.php`
- Password Reset: `resources/views/livewire/auth/reset-password.blade.php`

When modifying authentication flows, maintain the existing Volt pattern with `#[Layout('components.layouts.auth')]` attribute.

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