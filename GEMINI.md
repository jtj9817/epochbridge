# GEMINI.md

## Project Overview

This is a comprehensive calendar conversion platform named **EpochBridge**. It's built with **Laravel 12** and the **TALL Stack** (Tailwind CSS, Alpine.js, Livewire, Laravel). The application supports over 15 historical and modern calendar systems, providing real-time conversions between them. It also includes a full authentication system with user management features.

The core of the application is the `DateTimeConverterService`, which orchestrates the conversion between different calendar systems. Each calendar system has its own dedicated service class that handles the specific conversion logic. The frontend is built using Livewire and Livewire Volt, with a modular component-based architecture.

## Building and Running

The project uses Laravel Sail (Docker) for its development environment.

**Key Commands:**

*   **Start the development environment:**
    ```bash
    ./vendor/bin/sail up
    ```
*   **Run database migrations:**
    ```bash
    ./vendor/bin/sail artisan migrate
    ```
*   **Install frontend dependencies:**
    ```bash
    npm install
    ```
*   **Start the frontend development server:**
    ```bash
    npm run dev
    ```
*   **Run tests:**
    ```bash
    composer run test
    ```
    or
    ```bash
    php artisan test
    ```
*   **Run browser tests:**
    ```bash
    php artisan dusk
    ```

## Database Seeding

The database can be seeded with initial data using the following command:

```bash
./vendor/bin/sail artisan db:seed
```

This will run the `JapaneseEraSeeder` and `MayanCalendarDataSeeder`.

## API

Currently, the application does not have any public API endpoints. However, a future API is planned to allow for third-party integrations. The API is designed to be RESTful and will use Laravel Sanctum for authentication. The API implementation plan can be found in `docs/api-endpoint-evaluation.md`.

## Development Conventions

*   **Coding Style:** The project follows the standard Laravel coding style. Use Laravel Pint to format code:
    ```bash
    ./vendor/bin/pint
    ```
*   **Testing:** The project uses PestPHP for testing. Tests are organized into `Feature`, `Unit`, and `Browser` suites. All new features should be accompanied by tests.
*   **Branching:** Create a new feature branch for each new feature.
*   **Commits:** Write clear and concise commit messages.
*   **Frontend:** The project uses Livewire Volt for single-file components. Components are located in `resources/views/livewire`.
*   **CSS:** The project uses Tailwind CSS. Custom CSS should be added to `resources/css/app.css`.

## Error Handling

The application uses a custom exception hierarchy for handling calendar-related errors. The base exception is `App\Exceptions\CalendarConversionException`. Other more specific exceptions inherit from this base exception.

## Frontend Components

The frontend is built with Livewire Volt components, which are located in `resources/views/livewire`. The main landing page is `landing.blade.php`. This component is composed of several smaller components, which can be found in `resources/views/livewire/components`.