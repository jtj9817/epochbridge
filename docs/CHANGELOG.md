# Changelog

All notable changes to EpochBridge will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [October 07, 2025] - Major Feature Implementation: Date Conversion System

### Added
- **DateTimeConverterService** (`app/Services/DateTimeConverterService.php`) - Core business logic for multi-calendar date conversion
  - Converts dates between Gregorian and Persian/Jalali calendars
  - Returns comprehensive date information including year, month names, day of week
  - Provides English transliterations for Persian calendar names
  - Outputs Unix timestamps and ISO 8601 formatted dates
  - Robust input validation with exception handling
- **Landing Page** (`resources/views/livewire/landing.blade.php`) - Interactive date conversion interface
  - Livewire Volt single-file component architecture
  - Real-time date conversion with form validation
  - Quick-action buttons for Today, Yesterday, Tomorrow
  - Loading states and error handling
  - Responsive design with Tailwind CSS
- **Comprehensive Testing Suite**
  - Unit tests for `DateTimeConverterService` (`tests/Unit/Services/DateTimeConverterServiceTest.php`)
  - Feature tests for landing page functionality (`tests/Feature/Livewire/LandingPageTest.php`)
  - Test coverage for date conversion logic and user interactions
- **Persian Calendar Integration**
  - Authentic Jalali month names: Farvardin, Ordibehesht, Khordad, etc.
  - Persian day names with English transliterations
  - Cultural-appropriate date formatting
- **Public Layout System** (`resources/views/components/layouts/public.blade.php`)
  - Dedicated layout for public-facing pages
  - Authentication-free access for landing page

### Changed
- **Project Structure**: Added `app/Services/` directory for business logic encapsulation
- **Routing Configuration**: Landing page now serves as application homepage (`/` route)
- **TALL Stack Implementation**: Enhanced Livewire Volt usage with advanced state management
- **User Interface**: Applied retro-computing aesthetic with modern design principles
- **Error Handling**: Improved validation messages and user feedback systems

### Fixed
- **Livewire Volt Syntax**: Resolved ParseError issues with proper state declaration syntax
- **Validation Methods**: Corrected Livewire Volt validation message handling
- **Authentication Flow**: Fixed authentication errors on public landing page
- **Date Input Styling**: Centered convert date button and improved form layout
- **Jalali Date Methods**: Replaced undefined methods with proper format calls

### Technical Improvements
- **Code Quality**: Implemented proper separation of concerns with service layer
- **Type Safety**: Added comprehensive type hints and documentation
- **Performance**: Optimized date conversion algorithms
- **Maintainability**: Structured code following Laravel best practices
- **Testing Strategy**: Established comprehensive test coverage for critical functionality

### Documentation
- **Architecture Overview**: Created comprehensive technical documentation
- **Implementation Plans**: Documented API and landing page implementation strategies
- **Calendar Documentation**: Extensive documentation of Persian calendar integration
- **Development Guidelines**: Updated project instructions and development workflows

## [October 03, 2025] - Foundation & Authentication System

### Added
- **Initial Laravel 12 Application Setup**
  - TALL Stack (Tailwind CSS 4, Alpine.js, Livewire 3, Laravel 12) foundation
  - Laravel Sail Docker development environment
  - Vite build system with Tailwind CSS integration
- **Complete Authentication System**
  - Laravel Fortify integration for authentication logic
  - Two-factor authentication with TOTP and recovery codes
  - Email verification requirement for new accounts
  - Password reset functionality
  - Livewire Volt components for all authentication forms
- **User Management Features**
  - User registration and login
  - Profile management
  - Password update functionality
  - Account deletion with confirmation
  - Settings interface with multiple sections
- **UI/UX Foundation**
  - Flux UI component library integration
  - Responsive design system
  - Multiple layout variants (app, auth, public)
  - Modern component architecture

### Infrastructure
- **Database Setup**
  - MySQL 8.0 via Docker configuration
  - User table with two-factor authentication columns
  - Migration system for schema management
  - Factory patterns for testing data
- **Testing Infrastructure**
  - PestPHP testing framework setup
  - Laravel Dusk for browser testing
  - Comprehensive test suite for authentication flows
  - Separate testing database configuration
- **Development Tools**
  - Laravel Pint for code formatting
  - Laravel Pail for log monitoring
  - Docker Compose configuration for development
  - Custom Composer scripts for development workflow

---

## Future Releases

### Planned Features
- **Enhanced Calendar Support**: Additional calendar systems beyond Gregorian and Jalali
- **API Endpoints**: RESTful API for programmatic date conversion access
- **Bulk Conversion**: Upload and convert multiple dates simultaneously
- **Historical Date Accuracy**: Enhanced historical date conversion precision
- **User Preferences**: Customizable default calendar systems and formats
- **Export Functionality**: Download conversion results in various formats (CSV, JSON, XML)

### Technical Roadmap
- **Performance Optimization**: Caching layer for frequently converted dates
- **Internationalization**: Multi-language support for UI elements
- **Mobile App**: Native mobile applications for iOS and Android
- **Third-party Integrations**: Calendar application plugins and integrations
- **Advanced Analytics**: Usage statistics and conversion pattern analysis