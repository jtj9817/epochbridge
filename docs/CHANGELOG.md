# Changelog

All notable changes to EpochBridge will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [September 5-8, 2026] - Security Hardening, Livewire Removal & API Robustness

### Added
- **Stardew pixel design system**: custom `.sd-*` design foundation with pixel fonts and original pixel-art assets, restyling the landing converter, modals, toasts, auth, settings, and dashboard.
- **Seasonal city backdrops**: spring/summer/autumn/winter artwork rotating on the landing page.

### Changed
- **Email verification now required**: new users must verify their address (dashboard is gated on `verified`); existing users were grandfathered as verified.
- **Public API throttling**: `/frontend/conversions`, `/frontend/on-this-day`, and `/frontend/quick-action` are limited to 60 requests/minute per IP.
- **Auth throttling**: registration limited to 10/hour per IP; password-reset-link requests to 5/minute per IP.
- **Guest migration hardening**: server-side validation (max 3 items, ≤128 keys and ≤64 KiB per payload) and dedupe by original input.
- **Note length aligned** at 2048 characters across frontend and backend.
- **Test suite ported** from Livewire component tests to HTTP feature tests plus React component tests (Vitest).

### Fixed
- **Security headers on all web responses**: Content-Security-Policy, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, and HSTS over TLS.
- Google OAuth no longer reflects the provider's `error_description` on the login page (attacker-controllable text).
- Two-factor attributes no longer appear in user serialization.
- Conversion caching skipped for time-precise inputs (Unix, ISO 8601), which previously grew the cache-key space without bound.
- React app fixes: functional logout and account deletion, Fortify profile/password endpoints, results-grid save button wiring, auth routes loaded once.

### Removed
- **Livewire and Volt runtime** removed in favor of the React frontend; the unused `livewire/volt` dependency was dropped (`livewire/flux` remains an unused leftover).

### Security
- **OAuth account-linking guard**: linking a Google identity into an existing account with an unverified email is refused (account pre-hijacking prevention); OAuth uses PKCE throughout.

## [July 10, 2026] - Saved Conversions Display

### Fixed
- Saved conversions display romanized Jalali day names; calendar card rendering hardened.

## [April 14-15, 2026] - React/Inertia Frontend Migration

### Added
- **React 19 + Inertia + TypeScript frontend** replacing Blade/Livewire/Volt/Flux/Alpine: landing converter, authentication pages, settings, dashboard, modals, toasts, saved conversions with notes, guest saves, and the On This Day UX.
- **Session-backed JSON API** under `/frontend/*` for the React app: conversions, saved conversions (store/list/delete/notes/guest migration), quick actions, and On This Day.
- **Vitest + Testing Library** infrastructure with component tests colocated in `resources/js/`.

### Changed
- Development port consolidated from 8080/8086 to **8081** (Google OAuth redirect URIs and Docker port mappings updated).

## [January 13-29, 2026] - On This Day Enhancements & Security Remediation

### Added
- Wikimedia On This Day enhancements: granular per-section updates, section loading indicator, cache helper, performance timing and payload-size logging.

### Fixed
- **Security remediation plan implemented**: stateful OAuth with state validation (PKCE), HTTPS enforcement groundwork, secure cookies, input hardening.

### Changed
- Custom favicon with bridge design.

## [December 10, 2025] - Docker Containerization & UI Refinements

### Added
- **Docker Containerization Support**: Production-ready Docker deployment infrastructure
  - Multi-stage `Dockerfile` with optimized build layers (Composer, Node.js, PHP-FPM)
  - `docker/nginx.conf` - Nginx reverse proxy configuration for Laravel
  - `docker/supervisord.conf` - Process management for nginx and PHP-FPM
  - `docker/entrypoint.sh` - Container startup and initialization script
  - `.dockerignore` - Build context optimization (71 exclusion rules)
  - PHP 8.2-FPM Alpine-based production image for minimal footprint

- **Calendars Info Modal Component**: New modal for calendar system documentation
  - `calendars-info-modal.blade.php` - Comprehensive calendar descriptions
  - Static content approach for simplicity and maintainability
  - Accessible modal with proper open/close lifecycle
  - Zinc color scheme for visual consistency

- **Calendar Quick Actions Enhancement**: Parent-child date synchronization
  - Today/Yesterday/Tomorrow buttons now properly sync with date input
  - Improved state management for quick action button selection
  - Better UX with immediate visual feedback

### Changed
- **UI Theme Overhaul**: Semantic CSS system with Berkeley Mono typography
  - Comprehensive CSS variable system in `resources/css/app.css` (589+ lines added)
  - Zinc color scheme standardization across all components
  - Berkeley Mono font integration for code and data displays
  - Improved visual hierarchy and spacing consistency

- **Calendar Card Components Standardization**: Unified styling across all 20 calendar cards
  - Consolidated Julian card from `card/julian-card.blade.php` to `calendars/julian-card.blade.php`
  - Consistent typography classes across all calendar display components
  - Standardized icon and label styling patterns
  - Improved accessibility with better color contrast

- **Saved Conversion Item Layout**: Enhanced display and accessibility
  - Clickable note preview in saved conversion headers
  - Improved action button layout and visibility
  - Better visual hierarchy for conversion data display
  - Enhanced responsive behavior for mobile devices

### Refactored
- **Calendar Info Modal Architecture**: Simplified from dynamic to static content
  - Removed complex computed properties in favor of inline static content
  - Improved modal centering and backdrop handling
  - Better separation of modal display logic from content
  - Reduced JavaScript complexity for modal interactions

- **Calendar Card Component Structure**: Streamlined component organization
  - Removed duplicate Julian card component from legacy location
  - Updated CSS source directives for proper Tailwind content scanning
  - Consolidated styling patterns across modern, historical, and ancient calendars

### Fixed
- **Calendar Modal Display Issues**: Corrected centering and backdrop behavior
  - Fixed modal positioning for consistent centered display
  - Improved backdrop click handling for proper modal dismissal
  - Enhanced transition animations for smooth open/close

- **Test Suite Stability**: Updated tests for calendar system changes
  - Fixed `CalendarQuickActionsTest.php` for new synchronization behavior
  - Updated browser and feature tests for UI consistency changes
  - Corrected test assertions for renamed/moved components

### Technical Improvements
- **Docker Production Architecture**
  - Multi-stage builds reduce final image size significantly
  - Composer dependencies cached separately for faster rebuilds
  - Frontend assets built in isolated Node.js stage
  - Nginx handles static assets directly without PHP overhead
  - Supervisord manages process lifecycle in single container

- **CSS Architecture Enhancement**
  - Semantic CSS variables for consistent theming
  - Better Tailwind CSS 4 integration with `@source` directives
  - Improved dark mode support preparation
  - Reduced CSS specificity conflicts

## [October 30, 2025] - Saved Conversion Notes Feature

### Added
- **Note Support for Saved Conversions**: Complete note annotation system for conversion history
  - Database schema update with nullable `note` TEXT column in `saved_conversions` table
  - Support for notes up to 2048 characters (increased from initial 500 character limit)
  - `SavedConversion` model enhancement with note field in fillable attributes
  - Factory support for generating test notes with realistic sample data
  
- **Save Conversion Modal Component**: New modal interface for saving conversions with notes
  - `save-conversion-modal.blade.php` Livewire component with Alpine.js integration
  - Character counter showing remaining characters (2048 limit)
  - Optional note field with textarea input and clear visual feedback
  - Toast notification integration for save success/error states
  - Proper event handling for modal open/close lifecycle
  
- **Note Editing Functionality**: Complete CRUD operations for conversion notes
  - Shared `edit-note-modal.blade.php` Livewire component for note editing
  - Edit button on saved conversion items with pencil icon
  - Modal-based editing interface with character limit validation
  - Real-time character counter during editing
  - Server-side validation and error handling
  - Toast notifications for successful updates and errors
  
- **Guest Note Support**: localStorage-based note persistence for unauthenticated users
  - Guest conversions now include note field in localStorage structure
  - Seamless note migration when guest users authenticate
  - Client-side note rendering via Alpine.js for fast display
  - Consistent note display between guest and authenticated modes

- **Comprehensive Test Coverage**: Full testing suite for notes functionality
  - **Browser Tests**: `ConversionNoteManagementTest.php` (250 lines) - Full user interaction testing
  - **Component Tests**: 
    - `EditNoteModalTest.php` (235 lines) - Modal component behavior testing
    - `SaveNoteUpdateEventTest.php` (192 lines) - Event handling and communication
  - **Feature Tests**:
    - `SaveConversionWithNoteTest.php` (228 lines) - Save operations with notes
    - `UpdateConversionNoteTest.php` (218 lines) - Note update workflows
  - **Unit Tests**: `SavedConversionNoteTest.php` (313 lines) - Model behavior and validation
  - Total test coverage: 1,436 lines of comprehensive test code

### Changed
- **Landing Page Save Logic**: Refactored to integrate note modal
  - Removed inline save button in favor of modal-based workflow
  - Enhanced save conversion method to accept note parameter
  - Improved duplicate detection with note preservation
  - Better separation of concerns between display and save operations
  
- **Saved Conversions Section**: Enhanced UI for note display and editing
  - Note display area added to saved conversion items
  - Truncated note preview with "Read more" expansion for long notes
  - Edit and delete actions consolidated in action area
  - Improved visual hierarchy with note styling
  
- **Calendar Results Grid**: Integrated save modal trigger
  - Save button now opens modal instead of direct save operation
  - Enhanced user experience with explicit note capture opportunity
  - Better feedback during save process with loading states

- **SavedConversion Factory**: Enhanced test data generation
  - Added realistic note generation with 60% probability
  - Variety of sample notes for different use cases
  - Character limit testing support with boundary cases
  - Improved factory flexibility for comprehensive testing

### Refactored
- **Note Modal Component Architecture**: Extracted shared editing component
  - Created reusable `edit-note-modal.blade.php` for note editing across contexts
  - Reduced code duplication between different note editing locations
  - Centralized note update logic in `saved-conversions-section.blade.php`
  - Improved maintainability with single source of truth for note editing UI
  
- **Save Conversion Flow**: Streamlined save operation architecture
  - Moved note editing from calendar results grid to saved conversions section
  - Consolidated save logic in landing page component
  - Better event-driven communication between components
  - Cleaner separation between save and edit operations

### Fixed
- **Modal Interaction Issues**: Resolved backdrop and event propagation problems
  - Fixed backdrop click handling to properly close modals
  - Removed event propagation blocking from copy button
  - Improved Alpine.js event handling for nested interactive elements
  - Enhanced modal lifecycle management for better UX
  
- **UI Consistency**: Improved layout and interaction patterns
  - Simplified edit note modal layout for better visual clarity
  - Consistent button styling across save and edit modals
  - Proper spacing and alignment for note display areas
  - Enhanced mobile responsiveness for modal interfaces

### Technical Improvements
- **Character Limit Management**: Consistent 2048 character limit enforcement
  - Database column supports TEXT datatype (65,535 chars) for future flexibility
  - Application-level validation enforces 2048 character user-facing limit
  - Real-time character counter provides clear feedback
  - Server-side validation prevents exceeding limits
  
- **Event-Driven Architecture**: Enhanced component communication
  - `save-note-updated` event for note update notifications
  - `open-save-modal` event for triggering save modal from results grid
  - Proper Livewire `$dispatch()` to Alpine.js `@window` listener patterns
  - Clean separation of concerns with event-based integration
  
- **Guest-Authenticated Parity**: Consistent feature experience
  - Notes fully supported in both guest localStorage and authenticated database
  - Automatic note migration preserves user data on authentication
  - Identical UI/UX patterns for both user types
  - Seamless transition between guest and authenticated states

### Documentation
- **Implementation Complete**: Saved conversion notes feature fully implemented
  - All 9 phases from feature plan completed
  - Database schema, models, UI components, and tests all in place
  - Guest and authenticated user workflows implemented
  - Comprehensive test coverage across all layers

## [October 29, 2025] - Toast Notifications, Browser Testing & Duplicate Prevention

### Added
- **Toast Notification System**: Real-time user feedback for all CRUD operations
  - Positioned in top-right corner with slide-in animations
  - 3-second auto-dismiss with manual close option
  - Implemented for authenticated and guest save operations
  - Delete operation notifications with success/error states
  - Livewire-Alpine event bridge for seamless communication
  - `toast-notification.blade.php` component with Alpine.js reactivity
  
- **PestPHP Browser Testing Infrastructure**: Migration from Dusk to Pest Browser Plugin
  - Added `pestphp/pest-plugin-browser` dependency
  - Comprehensive migration plan documented in `docs/pest-browser-testing-migration-plan.md`
  - Phase-based migration strategy (3 phases: Browser, Feature, Unit)
  - Configuration templates for Playwright/Selenium setup
  - Test suite organization and parallel execution patterns
  
- **Duplicate Conversion Prevention**: Database-level constraint enforcement
  - Migration `2025_10_20_120000_add_unique_constraint_to_saved_conversions.php`
  - Unique constraint on `user_id` + `original_input` combination
  - Automatic duplicate cleanup before constraint application
  - Enhanced save logic with duplicate detection and user feedback
  - Test coverage for duplicate save attempt scenarios

### Changed
- **Landing Page Save Logic**: Enhanced duplicate handling and toast integration
  - Save button now checks for existing conversions before database insert
  - Toast notifications replace silent failures for better UX
  - Proper error handling with QueryException catching
  - Improved guest-to-authenticated migration with duplicate prevention
  
- **Toast Notification Tests**: Migrated from Feature to Browser test suite
  - Moved `ToastNotificationTest.php` to `tests/Browser/` directory
  - Updated test assertions for browser-based interactions
  - Enhanced test coverage for Alpine.js event handling
  - Component attribute testing for Alpine.js initialization

- **Saved Conversions Section**: Toast notifications for delete operations
  - Delete confirmation with Alpine.js modal integration
  - Success toast on successful deletion
  - Error toast with detailed messages on failure
  - Improved user feedback throughout deletion workflow

### Fixed
- **Alpine.js Initialization Timing**: Resolved race condition in saved conversion items
  - Fixed `x-data` initialization order in `saved-conversion-item.blade.php`
  - Proper Alpine.js component mounting before event listeners
  - Corrected syntax for Alpine attribute binding
  - Improved component reactivity and event propagation
  
- **Guest Conversion Data Display**: Fixed missing data and deletion bugs
  - Proper fallback for legacy conversion data structure
  - Enhanced null/undefined handling in Alpine.js templates
  - Fixed deletion logic for guest conversions in localStorage
  - Improved data consistency across guest and authenticated modes
  
- **Toast Event Dispatch**: Corrected Livewire-Alpine bridge communication
  - Fixed event name consistency (`show-toast` standardization)
  - Proper event payload structure for Alpine.js consumers
  - Enhanced event debugging and error handling
  - Improved cross-component event propagation

### Technical Improvements
- **Livewire-Alpine Event Bridge Pattern**: Documented architecture for event communication
  - Comprehensive documentation in `docs/toast-notification-consistency-plan.md`
  - Event naming conventions and payload structure standardization
  - Best practices for Livewire `$dispatch()` to Alpine.js `@window` listeners
  - Cross-component communication patterns for TALL stack apps
  
- **Database Integrity**: Enhanced data consistency with constraints
  - Unique constraint prevents duplicate saved conversions per user
  - Migration handles existing duplicates gracefully (keeps oldest record)
  - Improved query performance with indexed constraint columns
  
- **Test Migration Strategy**: Comprehensive browser testing roadmap
  - 901-line migration plan with phase-by-phase approach
  - Configuration examples for Playwright and Selenium
  - Test organization patterns for browser, feature, and unit tests
  - Parallel execution strategies for improved test performance

### Documentation
- **Toast Notification Architecture**: Complete implementation guide
  - Livewire-Alpine event bridge pattern documentation
  - Toast component API and usage examples
  - Event flow diagrams and debugging strategies
  - Integration patterns for authenticated and guest operations
  
- **Pest Browser Testing Migration Plan**: Comprehensive migration guide
  - Phase 1: Browser tests migration (Playwright/Selenium)
  - Phase 2: Feature tests migration (HTTP testing)
  - Phase 3: Unit tests migration (pure PHP testing)
  - Configuration templates and best practices
  - CI/CD integration strategies
  
- **Saved Conversion Notes Feature Plan**: Enhanced implementation details
  - Phase 9 refinements for save modal integration
  - Toast notification integration for notes CRUD operations
  - UI/UX improvements for notes display and editing
  - Database schema updates for notes storage

### Security
- **Duplicate Prevention**: Protection against data redundancy and storage abuse
  - Database-level constraint prevents duplicate saves
  - User-specific constraint prevents cross-user data issues
  - Proper error handling prevents information disclosure
  - Enhanced validation for save operations

## [October 24, 2025] - Google OAuth Integration

### Added
- **Google OAuth 2.0 Authentication**: Complete social login integration with Google Sign-In
  - Laravel Socialite dependency installed and configured
  - OAuth routes for redirect and callback flows (`/auth/google/redirect`, `/auth/google/callback`)
  - `SocialAuthController` with comprehensive error handling and logging
  - Support for OAuth-only accounts (password-less authentication)
  - Automatic email verification for OAuth users
  
- **SocialAccount Model**: New model for managing OAuth provider credentials
  - Encrypted storage for access tokens and refresh tokens
  - Token expiration tracking with `isTokenExpired()` helper method
  - One-to-many relationship with User model
  - Unique constraint on provider and provider_id combination
  
- **Database Schema Extensions**:
  - `social_accounts` table migration with encrypted token fields
  - Modified `users` table to support nullable passwords and avatar URLs
  - Cascade delete for social accounts when users are deleted
  - Indexed fields for optimal query performance

- **User Model OAuth Extensions**:
  - `socialAccounts()` relationship method
  - `hasLinkedProvider()` helper method for checking linked providers
  - `getLinkedProvider()` method for retrieving specific provider accounts
  - Avatar field for storing OAuth profile images

- **Google Sign-In UI**: OAuth button added to login page
  - Material Design Google button with SVG logo
  - "Or continue with" divider for visual separation
  - Responsive button styling matching application theme

- **Comprehensive Testing**: Feature tests for OAuth flow
  - OAuth redirect flow testing
  - New user creation via OAuth
  - Account linking for existing users by email
  - Token update logic for returning OAuth users
  - Error handling and validation tests

### Changed
- **User Table Schema**: Password field now nullable to support OAuth-only accounts
  - Allows users to authenticate exclusively via OAuth providers
  - Maintains backward compatibility with traditional authentication
  
- **Authentication Routes**: Expanded to include OAuth endpoints
  - Added `social.redirect` route for initiating OAuth flow
  - Added `social.callback` route for handling OAuth provider callbacks
  - Provider validation via route constraints (currently limited to 'google')

### Security
- **Token Encryption**: All OAuth tokens encrypted at rest using Laravel's encrypted casting
  - Access tokens encrypted in database
  - Refresh tokens encrypted in database
  - Tokens hidden from JSON serialization
  
- **Provider Validation**: Whitelist-based provider checking
  - Only allowed providers ('google') can be used
  - Invalid provider requests return 404
  
- **Transaction Safety**: Database transactions for user creation and account linking
  - Rollback on errors to prevent partial data commits
  - Ensures data consistency across users and social_accounts tables
  
- **Comprehensive Error Logging**: OAuth errors logged with context
  - IP address tracking for security auditing
  - Error messages without token exposure
  - Detailed diagnostics for debugging

- **CSRF Protection**: Socialite's built-in state parameter mechanism
  - Prevents cross-site request forgery attacks
  - Automatic state validation on callback

### Configuration
- **Google OAuth Credentials**: New environment variables required
  - `GOOGLE_CLIENT_ID`: OAuth 2.0 client ID from Google Cloud Console
  - `GOOGLE_CLIENT_SECRET`: OAuth 2.0 client secret
  - `GOOGLE_REDIRECT_URI`: Callback URL (defaults to `/auth/google/callback`)

- **Services Configuration**: Added Google OAuth config to `config/services.php`
  - Client credentials loaded from environment
  - Redirect URI with sensible default

### Documentation
- **Google OAuth Integration Guide**: Comprehensive documentation (`docs/google-oauth-integration.md`)
  - Architecture overview with component descriptions
  - Step-by-step setup instructions for Google Cloud Console
  - Authentication flow diagrams and code examples
  - Account linking scenarios (new user, existing user, returning user)
  - Security considerations and best practices
  - Testing guide with code examples
  - Troubleshooting section for common issues
  - Future enhancement suggestions

- **Architecture Documentation Updates**: Enhanced `docs/architecture-overview.md`
  - Multi-provider authentication system section
  - OAuth flow documentation with detailed diagrams
  - SocialAccount model schema and relationships
  - Security considerations for OAuth integration
  - Database schema updates with social_accounts table

- **README Updates**: Google OAuth features added to main documentation
  - OAuth listed in Tech Stack section
  - Setup instructions for Google credentials
  - Authentication system overview with OAuth support
  - Project structure updated with SocialAuthController and SocialAccount model
  - Testing section updated with OAuth test information

## [October 24, 2025] - Guest Conversion Persistence & UI Refinements

### Added
- **Guest Conversion Persistence**: Complete localStorage-backed conversion storage for unauthenticated users
  - All 19 calendar systems saved per conversion for guest users
  - Client-side rendering via Alpine.js for fast display
  - Persistent storage across browser sessions
  - Automatic data migration to database on user authentication
  - Seamless transition from guest to authenticated user experience

- **Date Input Tracking**: Smart save state management based on current date input
  - `input_date` field added to SavedConversion model
  - Real-time tracking of date input for accurate save button state
  - Enhanced user feedback for save operations

- **Complete Calendar Data Storage**: Expansion from selective to comprehensive calendar saves
  - All 19 calendar systems now saved per conversion (previously selective)
  - Full conversion data structure preserved in JSON format
  - Enhanced data integrity and retrieval capabilities

### Changed
- **Landing Page Architecture**: Major refactor for improved data handling and performance
  - Removed lazy-loading in favor of immediate display of all 19 calendars
  - Improved guest data migration logic with error handling
  - Enhanced Alpine.js integration with proper method invocation for save operations
  - Better separation of guest vs authenticated user workflows

- **Saved Conversion Item Component**: Complete redesign with table layout
  - Replaced card-based layout with compressed table format for better density
  - Custom color theme applied for improved visual hierarchy
  - Adjusted header spacing and typography for better readability
  - Client-side rendering support for guest conversions

- **SavedConversion Model**: Enhanced with legacy field support and date casting
  - Added `user_id` field for authenticated user association
  - Legacy field hydration for backward compatibility
  - Improved date handling with Carbon instances
  - Better accessor methods for conversion data retrieval

### Fixed
- **Alpine.js Method Invocation**: Resolved stuck saving button issue
  - Fixed improper method invocation syntax in Alpine.js components
  - Proper event handling for save operations
  - Improved reactivity and state management

- **Guest Conversion Save Scope**: Ensured all 19 calendar systems are saved
  - Previously only saved subset of calendars
  - Now includes all modern, historical, ancient, and technical format calendars
  - Consistent data structure across guest and authenticated saves

- **Date Field Handling**: Improved date validation and storage
  - Proper Carbon instance usage in tests and models
  - Enhanced date field assertions in unit tests
  - Better timezone and malformed date exception handling

### UI/UX Improvements
- **Visual Design Refinements**:
  - Custom color theme for saved conversion items
  - Compressed table layout for better information density
  - Improved spacing and typography in saved conversion headers
  - Simplified styling for cleaner presentation
  - Better visual feedback for save operations

### Technical Improvements
- **Guest-to-Authenticated Migration Pattern**: Production-ready data migration system
  - Transparent data transfer on user authentication
  - No data loss for guest users who create accounts
  - Maintains data integrity and proper timestamps
  - Encourages user registration without friction

- **Model Enhancement**: Robust data handling for saved conversions
  - Factory support for legacy field formats
  - Comprehensive unit tests for model behavior
  - Proper relationship definitions (User ↔ SavedConversion)
  - JSON casting for conversion_data field

### Documentation
- Updated README.md with guest conversion features and 19 calendar system count
- Enhanced architecture-overview.md with guest data migration architecture
- Added detailed documentation for localStorage integration patterns

## [October 21, 2025] - Chinese Calendar Astronomical Implementation & Performance Optimization

### Added
- **Chinese Calendar Astronomical Subsystem**: Complete replacement of simplified approximations with precise astronomical calculations
  - `app/Services/Chinese/ChineseLunisolarCalendar.php` - Suì construction and lunation sequencing orchestrator
  - `app/Services/Chinese/AstronomyCalculator.php` - Meeus-based Sun/Moon ephemerides with ΔT corrections
  - `app/Services/Chinese/LunarMonth.php` - Immutable lunation data structure with month length helpers
  - **Jean Meeus Algorithms**: Chapter 49 new moon calculations with all periodic corrections
  - **ΔT Corrections**: Espenak–Meeus polynomials for 1900–2100 with fallback quadratic approximation
  - **Dìngqì Solar Term Convention**: Accurate leap month detection via first post-solstice lunation lacking major term
  - **24 Solar Terms**: All terms calculated from apparent solar longitude (15° bins, 0° = Spring Equinox)
  - **Beijing Timezone**: All astronomical events converted to UTC+8 civil days with midnight boundaries
  - **Performance Caching**: Lunation sequences cached per suì and per Chinese year
  - **Edge Case Handling**: Proper handling of anomalies like 2033 leap month 11

- **Saved Conversions Performance Optimization**: Database indexing and pagination support
  - `app/Services/PerformanceMonitoringService.php` - Performance tracking and query optimization
  - Migration `*_add_indexes_to_saved_conversions_table.php` - Composite indexes for user_id and timestamps
  - Pagination support in `SavedConversionsSection` component with configurable page size
  - Query optimization with proper eager loading and select column limiting

- **Complete Conversion Data Storage**: Full conversion results stored in JSON format
  - `conversion_data` JSON field in `saved_conversions` table stores complete calendar outputs
  - Enhanced `SavedConversion` model with JSON casting and accessor methods
  - Guest data migration functionality with error handling
  - Improved saved conversion display with complete calendar information

- **Comprehensive Test Coverage**: New test suites for saved conversions and data migration
  - `tests/Browser/SavedConversionsTest.php` - Full browser interaction tests
  - `tests/Feature/DataMigrationTest.php` - Guest to authenticated data migration tests
  - `tests/Feature/Livewire/SavedConversionsTest.php` - Component behavior tests
  - `tests/Unit/Models/SavedConversionTest.php` - Model method and relationship tests
  - Enhanced `SavedConversionsSectionTest.php` with comprehensive assertions

### Changed
- **ChineseCalendarService**: Complete architectural refactor
  - Replaced simplified approximation algorithms with precise astronomical calculations
  - Constructor injection of `ChineseLunisolarCalendar` with optional DI support
  - Solar terms reordered to start from Spring Equinox (0° apparent solar longitude)
  - Added heavenly stem and earthly branch to zodiac output
  - Improved validation using computed lunation data for self-consistency
  - Month length calculation from actual lunation boundaries

- **Chinese Calendar Tests**: Updated for astronomical accuracy
  - Test fixtures aligned with actual astronomical events (New Year 2024, leap month 2023)
  - Added solar term validation and leap month detection tests
  - Bidirectional conversion tests (Gregorian ↔ Chinese)
  - Month length and date validation tests with edge cases

- **Saved Conversions Components**: Enhanced UI and data handling
  - Reactive pagination with Alpine.js state management
  - Improved ordering by creation date (newest first)
  - Better error handling and loading states
  - Optimized database queries with selective column loading

### Fixed
- **Calendar Results Grid**: Improved scalar and null value handling
  - Proper filtering for conversion results that may be null or scalar
  - Enhanced reactivity with Livewire property updates
  - Fixed Alpine.js data binding syntax errors

- **Saved Conversion Item Display**: Improved formatting and consistency
  - Proper handling of complete conversion data structure
  - Enhanced copy functionality with full date information
  - Better visual hierarchy and spacing

### Technical Improvements
- **Multi-Class Subsystem Architecture**: Demonstrates advanced pattern for complex calendars
  - Separation of concerns: calendar logic, astronomy, data structures
  - Independent testability for each component
  - Performance optimization through dedicated caching strategies
  - Maintainability via domain-organized algorithms
  - Extensibility for alternative astronomy calculators

- **Astronomical Accuracy**: Production-ready calculations
  - True lunisolar computations using established astronomical algorithms
  - Proper handling of time corrections (ΔT) for historical accuracy
  - Newton iteration for precise solar term timing
  - Self-consistent validation from computed ephemerides

- **Database Performance**: Optimized query patterns
  - Composite indexes on frequently queried columns
  - Pagination reduces memory footprint and response times
  - Eager loading prevents N+1 query problems
  - Selective column retrieval minimizes data transfer

### Documentation
- **Architecture Documentation**: Updated for Chinese calendar subsystem
  - Added "Chinese Calendar Astronomical Architecture" section in `docs/architecture-overview.md`
  - Detailed component descriptions for ChineseLunisolarCalendar, AstronomyCalculator, LunarMonth
  - Features, configuration, and implementation notes

- **Integration Guide**: New multi-class subsystem pattern
  - "Advanced: Multi-Class Subsystem Architecture" section in `docs/calendar-integration-guide.md`
  - When to use subsystem pattern vs adapter pattern vs simple service
  - Chinese calendar implementation example with code snippets
  - Benefits analysis: separation of concerns, testability, performance, maintainability

- **Supplementary Documentation**: Implementation notes in existing docs
  - Updated `docs/chinese-calendar-system-supplement-doc.md` with EpochBridge-specific implementation notes
  - Cross-references to actual implementation classes
  - Clarification of ΔT handling, new moon calculations, and leap month logic

- **README Updates**: Reflected new Chinese calendar capabilities
  - Enhanced East Asian Calendars description with astronomical implementation details
  - Updated project structure to show `Services/Chinese/` namespace
  - Added Meeus algorithms, ΔT corrections, and dìngqì convention to feature highlights

## [October 14, 2025] - Calendar Architecture Enhancements & Test Improvements

### Added
- **Julian Calendar System**: Complete historical calendar implementation alongside Julian Day calculations
  - `app/Services/JulianCalendarService.php` - Historical Julian calendar date conversions
  - Separate from Julian Day Number astronomical calculations for clarity
  - `resources/views/components/card/julian-card.blade.php` - UI component for Julian calendar display
  - Comprehensive test coverage in feature tests
  
- **Adapter Pattern with Dependency Injection**: Extensible architecture for complex calendar systems
  - `app/Contracts/Calendar/FrenchRepublicanCalendarAdapter.php` - Adapter interface defining calendar operations
  - `app/Support/Calendars/FrenchRepublicanExtCalendarAdapter.php` - Concrete implementation with caching
  - Per-instance memoization for improved performance without global state
  - Container binding in `AppServiceProvider` for proper dependency injection
  
- **French Republican Calendar Enhancements**: Complete feature implementation per enhancement plan
  - **Proleptic Calendar Support**: Mathematical extension beyond historical 1792-1805 range
  - **Paris Mean Solar Offset**: Configurable UT1 + 00:09:21 offset for historical accuracy
  - **Configuration System**: `config/calendars.php` with feature flags
    - `apply_paris_offset` (default: true) - Apply historical Paris Observatory offset
    - `allow_proleptic` (default: true) - Enable extended date range calculations
  - Historical equinox verification with test fixtures in `tests/__fixtures/french_republican_year_starts.php`
  
- **Component Test Infrastructure**: Enhanced testing for Livewire components
  - `tests/Feature/Livewire/Components/CalendarResultsGridTest.php` - Dedicated calendar grid tests
  - Proper test isolation following Livewire 3 testing patterns
  - Mock data alignment with service contracts

### Changed
- **FrenchRepublicanService Architecture**: Refactored to use dependency injection
  - Constructor injection of `FrenchRepublicanCalendarAdapter` interface
  - Resolved from container via `app()` helper in `DateTimeConverterService`
  - Enhanced testability with mockable adapter interface
  
- **DateTimeConverterService**: Updated to support new calendar services
  - Added `JulianCalendarService` to available calendars
  - French Republican service now resolved from container for DI support
  - Distinction between Julian calendar (historical) and Julian Day Number (astronomical)

- **Test Architecture**: Improved component test patterns
  - Parent component tests validate state changes rather than child output
  - Proper Livewire Volt component hierarchy handling
  - Fixed test isolation issues for reliable test execution

### Fixed
- **Livewire Component Test Isolation**: Resolved hierarchy testing issues
  - Parent tests now properly validate state rather than rendered child components
  - Follows Livewire 3 patterns where child components aren't rendered in parent tests
  - `tests/Feature/Livewire/LandingPageTest.php` refactored for proper isolation
  
- **French Republican Year Calculation**: Corrected historical data
  - Fixed Year 11 start date in test fixtures
  - Validated against known Paris equinox observations
  - Proper handling of complementary days in leap vs common years

- **Calendar Results Grid Filtering**: Enhanced null and scalar value handling
  - Proper filtering for scalar calendar values
  - Updated mock data to match service contracts
  - Improved component rendering reliability

### Technical Improvements
- **Extensible Architecture**: Adapter pattern demonstrates best practices for complex calendars
  - Easy to mock for unit tests
  - Swap calculation strategies (astronomical vs arithmetic)
  - Decouple from specific library implementations
  - Per-instance caching improves performance
  
- **Configuration Management**: Centralized calendar-specific settings
  - `config/calendars.php` provides single source of truth
  - Environment variable support for deployment flexibility
  - Feature flags enable/disable specific behaviors
  
- **Historical Accuracy**: Enhanced calendar calculations
  - Paris mean solar offset for French Republican calendar
  - Historical equinox data validation
  - Proleptic extensions with mathematical rigor

### Documentation
- **Architecture Documentation**: Comprehensive updates to reflect new patterns
  - Added "Adapter Pattern for Calendar Services" section in `docs/architecture-overview.md`
  - New ADR-005: Adapter Pattern for Calendar Services
  - Detailed explanation of Julian calendar vs Julian Day Number distinction
  
- **Integration Guide**: Extensive adapter pattern documentation
  - Step-by-step guide for implementing adapter pattern in `docs/calendar-integration-guide.md`
  - Code examples for interface definition, concrete implementation, DI setup
  - Testing patterns with mocked adapters
  - Real-world French Republican calendar example
  
- **Enhancement Plan Completion**: Marked all French Republican objectives as completed
  - Updated `docs/french-republican-service-enhancement-plan.md` with implementation details
  - Documented all completed tasks with commit references
  - Added resolved questions and future enhancement suggestions
  
- **README Updates**: Reflected new features and architecture
  - Updated project structure with `Contracts/Calendar/` and `Support/Calendars/`
  - Added configuration documentation
  - Clarified Julian calendar vs Julian Day Number
  - Mentioned proleptic support and adapter pattern

### Testing
- **Unit Tests**: Expanded French Republican calendar test coverage
  - Round-trip conversion tests across date ranges
  - Boundary condition testing (epoch start/end, month boundaries)
  - Complementary day validation (leap vs common years)
  - Display formatting assertions
  
- **Feature Tests**: Enhanced Livewire component testing
  - Calendar results grid component tests
  - Landing page refactored for proper component isolation
  - Configuration behavior validation
  
- **Test Fixtures**: Historical reference data for validation
  - `tests/__fixtures/french_republican_year_starts.php` with Years I-XIV equinox dates
  - Enables verification against known historical observations

## [October 11, 2025] - Interactive Test Runner System Implementation

### Added
- **Interactive Test Runner Command**: Comprehensive parallel test execution system
  - `app/Console/Commands/InteractiveTestRunnerCommand.php` - Interactive CLI with menu selection
  - `php artisan test:runner` command with verbose logging and custom output file support
  - Interactive test suite selection with comprehensive workflow options
  - Real-time progress tracking with ANSI-friendly progress bars and ETA calculations

- **Test Runner Service Architecture**: Modular service layer for test execution management
  - `app/Services/TestDiscoveryService.php` - Automatic test file discovery and categorization
  - `app/Services/TestSelectionService.php` - Interactive test suite selection interface
  - `app/Services/TestExecutionService.php` - Parallel test execution with process management
  - `app/Services/ProgressTrackingService.php` - Real-time progress updates and statistics
  - `app/Services/ErrorRecoveryService.php` - Test failure recovery with exponential backoff
  - `app/Services/MarkdownReportService.php` - GitHub-flavored Markdown report generation

- **Background Job System**: Queue-based test execution for scalability
  - `app/Jobs/ExecuteTestSuiteJob.php` - Background test suite execution with timeout management
  - `app/Contracts/TestProcessFactory.php` - Process factory interface for testability
  - `app/Support/TestProcessFactory.php` - Symfony process factory implementation

- **Event-Driven Architecture**: Real-time updates and reporting
  - `app/Events/TestExecutionStarted.php` - Test suite execution start events
  - `app/Events/TestCompleted.php` - Individual test completion events with results
  - `app/Events/TestExecutionCompleted.php` - Suite completion with comprehensive results
  - `app/Listeners/UpdateProgressDisplay.php` - Real-time progress display updates
  - `app/Listeners/UpdateMarkdownReport.php` - Automatic report generation on completion

- **Configuration System**: Environment-specific test runner configuration
  - `config/test-runner.php` - Comprehensive configuration with environment profiles
  - `app/Providers/TestRunnerServiceProvider.php` - Service provider for dependency injection
  - Support for parallel execution limits, timeouts, retry attempts, and report customization

- **Exception Handling**: Specialized test execution error management
  - `app/Exceptions/TestExecutionException.php` - Test runner specific exceptions
  - Detailed error context and recovery information for debugging

### Changed
- **Testing Architecture**: Enhanced test structure with comprehensive coverage
  - Reorganized test suites into Unit, Feature, Integration, and Browser categories
  - Added test runner service testing with mock implementations
  - Improved test isolation and parallel execution compatibility

- **Service Provider Registration**: Enhanced application bootstrapping
  - Added `TestRunnerServiceProvider` to service provider registration
  - Enhanced `EventServiceProvider` with test runner event listeners

### Fixed
- **Process Management**: Robust process handling and cleanup
  - Implemented proper process factory pattern for testable process management
  - Added timeout handling and graceful shutdown for parallel test execution
  - Fixed process resource cleanup and memory management

### Technical Improvements
- **Parallel Execution**: High-performance test execution with configurable process limits
- **Progress Tracking**: Real-time visual progress bars with completion statistics
- **Error Recovery**: Intelligent retry logic with exponential backoff and failure analysis
- **Report Generation**: Detailed Markdown reports with GitHub-flavored formatting
- **Configuration Management**: Environment-specific settings with override capabilities
- **Test Coverage**: Comprehensive test suite for all test runner components

### Testing
- **Feature Tests**: End-to-end test runner workflow testing
  - `tests/Feature/Console/InteractiveTestRunnerCommandTest.php` - Command interface testing
  - `tests/Feature/Integration/FullTestRunnerWorkflowTest.php` - Complete workflow testing
  - `tests/Feature/Jobs/ExecuteTestSuiteJobTest.php` - Background job testing

- **Unit Tests**: Service layer testing with mock implementations  
  - `tests/Unit/Services/TestDiscoveryServiceTest.php` - Test discovery logic testing
  - `tests/Unit/Services/ProgressTrackingServiceTest.php` - Progress tracking testing
  - `tests/Unit/Services/ErrorRecoveryServiceTest.php` - Error recovery logic testing
  - `tests/Unit/Services/MarkdownReportServiceTest.php` - Report generation testing

- **Support Utilities**: Testing infrastructure enhancements
  - `tests/Support/PromptSequence.php` - Interactive command testing utilities

## [October 09, 2025] - Exception Handling Architecture & Component Testing

### Added
- **Hierarchical Exception System**: Comprehensive calendar exception handling with PSR-4 compliance
  - `CalendarConversionException.php` - Abstract base class extending `DateException`
  - `CalendarMalformedDateException.php` - Handles malformed date string parsing errors
  - `CalendarRangeException.php` - Manages date boundary and range validation errors
  - `CalendarCalculationException.php` - Handles astronomical calculation failures
  - `CalendarValidationException.php` - Manages date component validation errors
  - Calendar system context in error messages with `[{calendarSystem}]` prefix
  - Support for date component debugging information in exception constructors

### Changed
- **Exception Architecture Refactoring**: Separated monolithic exception into specialized classes
  - Moved from single-file exception to individual files for better PSR-4 autoloading
  - Enhanced error context with calendar system identification
  - Improved debugging capabilities with date component tracking
  - Better error categorization for specific handling scenarios

### Fixed
- **PSR-4 Autoloading**: Resolved autoloading issues with properly separated exception files
- **Exception Inheritance**: Corrected exception hierarchy with proper PHP built-in exception extends

### Technical Improvements
- **Code Organization**: Clean separation of exception concerns into focused classes
- **Error Handling**: Enhanced error context and debugging information
- **Maintainability**: Individual exception files improve code maintainability
- **Documentation**: Updated architecture documentation with complete exception hierarchy

## [October 09, 2025] - Component Architecture Modernization & Testing Enhancement

### Added
- **Comprehensive Component Testing Suite**: Full browser testing coverage for modular components
  - `tests/Browser/LandingPageTest.php` - Complete landing page interaction testing
  - `tests/Browser/Components/CalendarQuickActionsTest.php` - Quick action button testing
  - `tests/Browser/Components/CalendarResultsGridTest.php` - Results grid functionality testing
  - `tests/Browser/Components/CalendarSearchFilterTest.php` - Calendar filtering testing
  - `tests/Browser/Components/SavedConversionsSectionTest.php` - User history management testing
- **Enhanced Calendar Service Tests**: Unit tests for newly implemented calendar systems
  - `tests/Unit/Services/CopticCalendarServiceTest.php` - Coptic calendar conversion testing
  - `tests/Unit/Services/EgyptianCalendarServiceTest.php` - Ancient Egyptian calendar testing
  - `tests/Unit/Services/EthiopianCalendarServiceTest.php` - Ethiopian calendar testing
- **Database Factory Expansion**: `SavedConversionFactory.php` for comprehensive test data generation
- **Development Documentation**: New comprehensive guides for component development
  - `docs/calendar-integration-guide.md` - Calendar system integration patterns
  - `docs/component-architecture.md` - Modular component design principles
  - `docs/component-testing-guide.md` - Testing strategies for components
  - `docs/cultural-accuracy-guide.md` - Cultural authenticity guidelines
  - `docs/performance-guide.md` - Performance optimization patterns

### Changed
- **Livewire Volt Event System**: Modernized event handling with new `on()` helper
  - Replaced legacy event listener syntax with modern Livewire Volt patterns
  - Enhanced component communication between modular sections
  - Improved real-time updates and user interaction responsiveness
- **Component Architecture**: Complete modularization of landing page functionality
  - Extracted calendar input section into `calendar-input-section.blade.php`
  - Separated quick actions into `calendar-quick-actions.blade.php`
  - Isolated results display into `calendar-results-grid.blade.php`
  - Created dedicated filtering UI in `calendar-search-filter.blade.php`
  - Organized saved conversions into `saved-conversions-section.blade.php`
  - Added individual conversion items as `saved-conversion-item.blade.php`
- **SavedConversion Model Enhancement**: Added automatic date casting for `gregorian_date` field
  - Improved data handling with proper type casting
  - Enhanced query performance with optimized attribute access
  - Better integration with calendar conversion display components
- **DateTimeConverterService Optimization**: Implemented caching and calendar categorization
  - Added conversion result caching for improved performance
  - Implemented calendar system categorization (modern, historical, ancient, technical)
  - Enhanced lazy loading support for complex calendar calculations

### Fixed
- **PHP 8.4 Compatibility**: Resolved syntax errors in Livewire Volt components
  - Fixed ParseError issues with proper state declaration syntax
  - Updated component method definitions for PHP 8.4 compatibility
  - Corrected property initialization patterns
- **Event Listener Modernization**: Updated deprecated event handling patterns
  - Replaced outdated Livewire event syntax with modern alternatives
  - Fixed component communication issues between modular sections
  - Improved event propagation and data handling

### Technical Improvements
- **Component Separation**: Clean extraction of concerns into focused, reusable components
- **Testing Coverage**: Comprehensive test suite covering all new modular components
- **Code Organization**: Consistent patterns across all newly created components
- **Performance**: Optimized component loading with lazy loading and caching strategies
- **Documentation**: Extensive component architecture and testing documentation
- **Development Workflow**: Enhanced development experience with better component isolation

## [October 08, 2025] - Massive Calendar System Expansion & User Features

### Added
- **15+ Calendar Systems**: Complete implementation of comprehensive calendar conversion platform
  - **Modern Calendars**: Hebrew, Islamic (Hijri) lunar calendar systems  
  - **Historical Calendars**: Julian, French Republican, Coptic, Ethiopian, Egyptian calendars
  - **Ancient Calendars**: Babylonian, Assyrian, Ancient Macedonian, Mayan calendar systems
  - **East Asian Calendars**: Chinese Lunisolar with zodiac, Japanese Era-based calendar
- **Calendar Service Architecture**: Specialized service classes for each calendar system
  - `HebrewCalendarService.php` - Jewish calendar with lunar-solar calculations
  - `IslamicCalendarService.php` - Hijri lunar calendar conversions  
  - `ChineseCalendarService.php` - Chinese lunisolar with 60-year cycle
  - `JapaneseCalendarService.php` - Imperial era-based calendar system
  - `FrenchRepublicanService.php` - Revolutionary calendar (1793-1805)
  - `CopticCalendarService.php`, `EthiopianCalendarService.php`, `EgyptianCalendarService.php`
  - `BabylonianCalendarService.php`, `AssyrianCalendarService.php`, `AncientMacedonianService.php`
  - `MayanCalendarService.php` - Complex Mesoamerican calendar system
  - `AncientAstronomyService.php` - Astronomical calculations for ancient calendars
- **Advanced Calendar Data Models**: Database integration for historical accuracy
  - `JapaneseEra.php` model with era names and date ranges
  - `HistoricalCalendarData.php` for ancient calendar reference data
  - `MayanCalendarData.php` for complex Mayan calendar calculations
  - `SavedConversion.php` for user conversion history management
- **User Conversion Management**: Complete save/load functionality
  - Authenticated users can save and organize conversion history
  - Guest users utilize browser local storage for temporary saves
  - Bulk save operations for multiple conversions
  - Conversion history with search and filtering capabilities
- **Enhanced Landing Page Interface**: Comprehensive multi-calendar display
  - Simultaneous display of all 15+ calendar systems
  - Unique visual styling for each calendar with cultural icons
  - Real-time conversion updates across all calendars
  - Responsive grid layout optimized for desktop and mobile
- **Clipboard Integration**: One-click copy functionality for all conversion results
- **Database Schema Extensions**: 
  - `japanese_eras` table with comprehensive era data
  - `historical_calendar_data` table for ancient calendar reference
  - `mayan_calendar_data` table for Mayan calendar calculations
  - `saved_conversions` table for user history management
- **Comprehensive Test Coverage**: Unit tests for all new calendar services
  - `ChineseCalendarServiceTest.php`, `JapaneseCalendarServiceTest.php`
  - `CopticCalendarServiceTest.php`, `EgyptianCalendarServiceTest.php`, `EthiopianCalendarServiceTest.php`
- **Julian Day Integration**: Universal calendar conversion backbone
  - `JulianDayService.php` for astronomical calculations
  - Modified Julian Day support for modern applications
  - Accurate conversion between all calendar systems

### Changed
- **Landing Page Architecture**: Complete redesign for multi-calendar display
  - Replaced simple dual-calendar interface with comprehensive 15+ calendar grid
  - Enhanced UX with loading states and error handling
  - Improved visual hierarchy with calendar-specific styling
- **DateTimeConverterService**: Expanded to orchestrate all calendar conversions
  - Universal conversion capability between any supported calendars
  - Enhanced output with Julian Day Numbers and astronomical data
  - Improved error handling across all calendar systems
- **Authentication Integration**: Better user experience for conversion management
  - Seamless transition between guest and authenticated storage
  - Enhanced user profile with saved conversion statistics
- **Database Architecture**: Expanded schema to support historical calendars
  - Migration system for new calendar data tables
  - Seeder classes for Japanese eras and Mayan calendar data

### Fixed
- **Calendar Accuracy**: Replaced undefined calendar functions with proper ext-calendar API
- **Islamic Calendar**: Improved lunar calculations with proper ext-calendar integration
- **Date Input Logic**: Consolidated calendar input fields and validation
- **Julian Day Conversion**: Corrected calculations and improved results display
- **Authentication Helper**: Replaced auth() helper with Auth facade for better IDE support

### Technical Improvements
- **Service Layer Architecture**: Clean separation of calendar-specific logic
- **Database Design**: Normalized structure for historical calendar data  
- **Code Organization**: Consistent patterns across all calendar services
- **Performance Optimization**: Efficient calculation methods for complex calendars
- **Documentation**: Comprehensive calendar systems reference documentation
- **Testing Strategy**: Robust test coverage for all calendar conversions

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