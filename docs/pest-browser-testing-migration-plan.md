# Pest Browser Testing Migration Plan

> **⚠️ Historical document.** This migration was **not executed** — Laravel Dusk remains the browser test driver (`tests/Browser/`), although `pestphp/pest-plugin-browser` is installed. The Livewire/Alpine context below was also retired in favor of the React frontend (April–September 2026). Kept as a record.

## GOAL

Migrate all browser tests from Laravel Dusk to Pest 4's native browser testing using Playwright, eliminating the need for Selenium/ChromeDriver infrastructure while maintaining comprehensive test coverage for Alpine.js and Livewire interactions.

## CONTEXT

### Current State

The project currently has **19 browser tests** across 4 test files using Laravel Dusk:

**Pest-style Dusk Tests (5 tests):**
- `tests/Browser/ToastNotificationTest.php` - 4 tests for toast notification interactions
- `tests/Browser/ExampleTest.php` - 1 basic test

**PHPUnit-style Dusk Tests (14 tests):**
- `tests/Browser/LandingPageTest.php` - 4 tests for landing page functionality
- `tests/Browser/SavedConversionsTest.php` - 10 tests for saved conversions feature

### Problems with Current Approach

1. **Infrastructure Complexity**: Requires Selenium/ChromeDriver Docker service
2. **Environment Issues**: Tests fail with "Could not resolve host: selenium"
3. **Setup Overhead**: Dusk requires separate ChromeDriver management
4. **Performance**: Dusk is slower than Playwright-based solutions
5. **Mixed Test Styles**: Combination of Pest and PHPUnit syntax

### Benefits of Pest 4 Browser Testing

1. **Simplified Infrastructure**: No Selenium/ChromeDriver required
2. **Native Playwright Integration**: Modern, fast browser automation
3. **Unified Test Style**: Pure Pest syntax across all tests
4. **Better Performance**: Playwright is faster than Selenium WebDriver
5. **Enhanced Features**: Built-in mobile device testing, dark mode support
6. **Parallel Execution**: Better parallel test support out of the box
7. **Cross-Browser Testing**: Easier Chrome, Firefox, Safari testing

## TASK

Migrate all 19 browser tests from Laravel Dusk to Pest 4 native browser testing while:
- Maintaining identical test coverage
- Preserving all assertions and test logic
- Improving test readability and maintainability
- Eliminating infrastructure dependencies

## REQUIREMENTS

### Dependencies

**Install Required Packages:**
```bash
composer require pestphp/pest-plugin-browser --dev
npm install playwright@latest
npx playwright install
```

**Remove Deprecated Dependencies:**
```bash
composer remove laravel/dusk --dev
```

**Update `.gitignore`:**
```
tests/Browser/Screenshots
```

### Configuration Changes

1. **Remove Dusk Configuration:**
   - Delete `tests/DuskTestCase.php`
   - Remove Dusk-specific environment variables from `.env`
   - Remove Selenium service references (if any in `docker-compose.yml`)

2. **Update Test Structure:**
   - Convert all PHPUnit class-based tests to Pest function-based tests
   - Remove `DuskTestCase` inheritance
   - Remove `$this->browse()` wrappers

### Test Coverage Requirements

All existing test scenarios must be preserved:

**ToastNotificationTest.php (4 tests):**
- ✅ Authenticated user save toast
- ✅ Guest user save toast
- ✅ Already saved conversion toast
- ✅ Guest limit reached toast

**LandingPageTest.php (4 tests):**
- ✅ Landing page conversion flow
- ✅ Quick actions functionality
- ✅ Search and filter functionality
- ✅ Guest save and delete conversions

**SavedConversionsTest.php (10 tests):**
- ✅ Display saved conversions for authenticated user
- ✅ Save and display conversion for authenticated user
- ✅ Delete saved conversion
- ✅ Guest save to localStorage
- ✅ Guest delete from localStorage
- ✅ Guest 3-conversion limit
- ✅ Display all 19 calendar systems
- ✅ Backward compatibility with old format
- ✅ Responsive mobile display

**ExampleTest.php (1 test):**
- ✅ Basic example test

## GUIDELINES

### Step 1: Install Pest Browser Plugin

**File:** `composer.json` (modified via command)

**Action:**
```bash
composer require pestphp/pest-plugin-browser --dev
npm install playwright@latest
npx playwright install
```

**Expected Outcome:**
- Pest Browser plugin installed
- Playwright binaries downloaded
- Chrome, Firefox, Safari browsers available for testing

---

### Step 2: Update `.gitignore`

**File:** `.gitignore`

**Changes:**
```diff
# Add Pest browser screenshots directory
+tests/Browser/Screenshots

# Can optionally remove Dusk-specific entries if present
-tests/Browser/console
-tests/Browser/screenshots
-tests/Browser/source
```

**Rationale:** Pest uses a different screenshot directory structure.

---

### Step 3: Migrate `ToastNotificationTest.php`

**File:** `tests/Browser/ToastNotificationTest.php`

**Current Structure (Pest + Dusk):**
```php
<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('shows toast notification when authenticated user saves conversion', function () {
    $this->browse(function (Browser $browser) {
        $user = User::factory()->create();
        
        $browser->loginAs($user)
            ->visit('/')
            ->waitForLivewireToLoad()
            ->type('input[name="dateInput"]', '2024-01-01')
            // ... rest of test
    });
});
```

**New Structure (Pure Pest Browser):**
```php
<?php

use App\Models\User;

test('shows toast notification when authenticated user saves conversion', function () {
    $user = User::factory()->create();
    
    $page = visit('/')
        ->actingAs($user)
        ->type('input[name="dateInput"]', '2024-01-01')
        ->click('button:contains("Convert Date")')
        ->waitForText('Conversion Results', timeout: 10000)
        ->click('button:contains("Save")')
        ->waitForText('Conversion saved successfully!', timeout: 5000)
        ->assertVisible('.fixed.top-4.right-4')
        ->assertSee('Conversion saved successfully!')
        ->pause(4000)
        ->assertDontSee('Conversion saved successfully!');
});
```

**Key Changes:**
1. Remove `use Laravel\Dusk\Browser`
2. Remove `$this->browse()` wrapper
3. Replace `$browser->loginAs()` with `->actingAs()` chained after `visit()`
4. Use `visit()` directly instead of `$browser->visit()`
5. Update `waitForText()` to use named parameter `timeout:` instead of positional
6. Replace `->type()` with new Pest syntax if needed
7. Keep existing assertions (most translate directly)

**Apply to all 4 tests in file.**

---

### Step 4: Migrate `LandingPageTest.php`

**File:** `tests/Browser/LandingPageTest.php`

**Current Structure (PHPUnit + Dusk):**
```php
<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LandingPageTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_landing_page_conversion_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('EpochBridge')
                // ... rest
        });
    }
}
```

**New Structure (Pure Pest):**
```php
<?php

use function Pest\Laravel\{get};

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

test('landing page conversion flow works correctly', function () {
    $page = visit('/')
        ->assertSee('EpochBridge')
        ->type('date-input-gregorian', '2024-10-27')
        ->click('Convert Date')
        ->waitForText('Conversion Results')
        ->assertSee('Gregorian')
        ->assertSee('2024-10-27')
        ->assertSee('Jalali')
        ->assertSee('1403/08/06');
});

test('quick actions work correctly', function () {
    $today = now()->format('Y-m-d');
    
    $page = visit('/')
        ->click('TODAY')
        ->click('Convert Date')
        ->waitForText('Conversion Results')
        ->assertSee($today);
});

test('search and filter works correctly', function () {
    $page = visit('/')
        ->type('date-input-gregorian', '2024-01-01')
        ->click('Convert Date')
        ->waitForText('Conversion Results')
        ->type('input[placeholder="Search calendars..."]', 'Jalali')
        ->assertSee('Jalali')
        ->assertDontSee('Gregorian')
        ->clear('input[placeholder="Search calendars..."]')
        ->click('Show Filters')
        ->check('selectedTypes[]', 'lunar')
        ->assertSee('Islamic')
        ->assertDontSee('Gregorian');
});

test('guest can save and delete conversions', function () {
    $page = visit('/')
        ->type('date-input-gregorian', '2024-01-01')
        ->click('Convert Date')
        ->waitForText('Conversion Results')
        ->click('Save')
        ->waitForText('Your Saved Conversions')
        ->assertSee('2024-01-01')
        ->within('#saved-conversions-guest', function ($section) {
            $section->click('Delete');
        })
        ->waitUntilMissing('2024-01-01');
});
```

**Key Changes:**
1. Remove `namespace` declaration (Pest convention)
2. Remove class declaration and `extends DuskTestCase`
3. Replace `use DatabaseMigrations` trait with `beforeEach()` hook
4. Convert `public function test_*()` to `test('*', function())`
5. Remove `$this->browse()` wrapper
6. Replace `->press()` with `->click()` (Pest convention)
7. Replace `->with()` selector with `->within()` if needed
8. Update assertions to Pest syntax where applicable

---

### Step 5: Migrate `SavedConversionsTest.php`

**File:** `tests/Browser/SavedConversionsTest.php`

**Current Structure (PHPUnit + Dusk):**
```php
<?php

namespace Tests\Browser;

use App\Models\SavedConversion;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SavedConversionsTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->script('localStorage.clear();');
        });
    }

    /** @test */
    public function it_displays_saved_conversions_for_authenticated_user()
    {
        $user = User::factory()->create();
        // ... test logic
    }
}
```

**New Structure (Pure Pest):**
```php
<?php

use App\Models\SavedConversion;
use App\Models\User;

beforeEach(function () {
    // Clear localStorage before each test
    visit('/')->evaluate('localStorage.clear()');
});

test('displays saved conversions for authenticated user', function () {
    $user = User::factory()->create();
    $conversionData = [
        'gregorian' => [
            'full_date' => '2024-01-15',
            'name' => 'Gregorian',
            'tags' => ['modern', 'solar'],
        ],
        // ... rest of data
    ];

    SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'conversion_data' => $conversionData,
        'gregorian_date' => '2024-01-15',
        // ... rest
    ]);

    $page = visit('/')
        ->actingAs($user)
        ->waitForText('Your Saved Conversions')
        ->assertSee('2024-01-15')
        ->assertSee('Gregorian')
        ->assertSee('Jalali')
        ->assertSee('Hebrew')
        ->assertSee('Islamic')
        ->assertSee('1402/10/24')
        ->assertSee('Input:')
        ->assertSee('Saved:');
});

test('can save and display conversion for authenticated user', function () {
    $user = User::factory()->create();

    $page = visit('/')
        ->actingAs($user)
        ->waitForText('Convert Date')
        ->type('dateInput', '2024-01-15')
        ->click('#btn-convert-date')
        ->waitForText('Gregorian', timeout: 10000)
        ->waitForText('Jalali', timeout: 10000)
        ->click('button[wire\\:click*="saveConversion"]')
        ->waitForText('conversion-saved')
        ->waitForText('Your Saved Conversions')
        ->assertSee('2024-01-15')
        ->assertSee('Gregorian')
        ->assertSee('Jalali');

    expect($user->savedConversions()->where('original_input', '2024-01-15')->exists())
        ->toBeTrue();
});

test('can delete saved conversion', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'conversion_data' => [
            'gregorian' => ['full_date' => '2024-01-15', 'name' => 'Gregorian', 'tags' => ['modern']],
            'jalali' => ['full_date' => '1402/10/24', 'name' => 'Jalali', 'tags' => ['modern']],
            'unix' => 1705276800,
            'iso8601' => '2024-01-15T00:00:00Z',
        ],
    ]);

    $page = visit('/')
        ->actingAs($user)
        ->waitForText('Your Saved Conversions')
        ->assertSee('2024-01-15')
        ->click('button[wire\\:click*="deleteConversion"]')
        ->waitForDialog()
        ->acceptDialog()
        ->waitUntilMissing('2024-01-15');

    expect(SavedConversion::find($conversion->id))->toBeNull();
});

test('guest user can save conversion to localStorage', function () {
    $page = visit('/')
        ->waitForText('Convert Date')
        ->type('dateInput', '2024-01-15')
        ->click('#btn-convert-date')
        ->waitForText('Gregorian', timeout: 10000)
        ->waitForText('Jalali', timeout: 10000)
        ->click('button[onclick*="save-guest-conversion"]')
        ->waitForText('Your Saved Conversions')
        ->assertSee('2024-01-15')
        ->assertSee('Gregorian')
        ->assertSee('Jalali');
});

test('guest user can delete conversion from localStorage', function () {
    // First save a conversion
    $page = visit('/')
        ->waitForText('Convert Date')
        ->type('dateInput', '2024-01-15')
        ->click('#btn-convert-date')
        ->waitForText('Gregorian', timeout: 10000)
        ->click('button[onclick*="save-guest-conversion"]')
        ->waitForText('Your Saved Conversions')
        ->assertSee('2024-01-15')
        // Then delete it
        ->click('button[onclick*="deleteSaved"]')
        ->waitUntilMissing('2024-01-15');
});

test('guest user is limited to three conversions', function () {
    $page = visit('/')
        ->waitForText('Convert Date');

    // Save 3 conversions
    for ($i = 1; $i <= 3; $i++) {
        $page->type('dateInput', "2024-01-{$i}")
            ->click('#btn-convert-date')
            ->waitForText('Gregorian', timeout: 10000)
            ->click('button[onclick*="save-guest-conversion"]')
            ->pause(500);
    }

    // Try to save 4th conversion
    $page->type('dateInput', '2024-01-04')
        ->click('#btn-convert-date')
        ->waitForText('Gregorian', timeout: 10000)
        ->click('button[onclick*="save-guest-conversion"]')
        ->waitForText('You\'ve reached the guest limit of 3 saved conversions')
        ->assertSee('Create an account');
});

test('displays all calendar systems in saved conversion', function () {
    $user = User::factory()->create();
    $conversionData = [
        'gregorian' => ['full_date' => '2024-01-15', 'name' => 'Gregorian', 'tags' => ['modern']],
        'jalali' => ['full_date' => '1402/10/24', 'name' => 'Jalali', 'tags' => ['modern']],
        'unix' => 1705276800,
        'iso8601' => '2024-01-15T00:00:00Z',
        'hebrew' => ['full_date' => '5784/11/04', 'name' => 'Hebrew', 'tags' => ['historical']],
        'islamic' => ['full_date' => '1445/07/04', 'name' => 'Islamic', 'tags' => ['historical']],
        'french_republican' => ['full_date' => '25 Nivôse 232', 'name' => 'French Republican', 'tags' => ['historical']],
        'chinese' => ['full_date' => '2023/12/05', 'name' => 'Chinese', 'tags' => ['historical']],
        'japanese' => ['full_date' => 'Reiwa 6/01/15', 'name' => 'Japanese', 'tags' => ['modern']],
    ];

    SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'conversion_data' => $conversionData,
    ]);

    $page = visit('/')
        ->actingAs($user)
        ->waitForText('Your Saved Conversions')
        ->assertSee('Gregorian')
        ->assertSee('Jalali')
        ->assertSee('Hebrew')
        ->assertSee('Islamic')
        ->assertSee('French Republican')
        ->assertSee('Chinese')
        ->assertSee('Japanese');
});

test('handles old format conversions with backward compatibility', function () {
    $user = User::factory()->create();

    // Create old format conversion without conversion_data
    SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/24',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00Z',
    ]);

    $page = visit('/')
        ->actingAs($user)
        ->waitForText('Your Saved Conversions')
        ->assertSee('2024-01-15')
        ->assertSee('1402/10/24')
        ->assertSee('Gregorian:')
        ->assertSee('Jalali:')
        ->assertSee('Unix:');
});

test('displays conversions responsively on mobile', function () {
    $user = User::factory()->create();
    $conversionData = [
        'gregorian' => ['full_date' => '2024-01-15', 'name' => 'Gregorian', 'tags' => ['modern']],
        'jalali' => ['full_date' => '1402/10/24', 'name' => 'Jalali', 'tags' => ['modern']],
        'unix' => 1705276800,
        'iso8601' => '2024-01-15T00:00:00Z',
    ];

    SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'conversion_data' => $conversionData,
    ]);

    $page = visit('/')
        ->on()->mobile() // Pest's mobile device simulation
        ->actingAs($user)
        ->waitForText('Your Saved Conversions')
        ->assertSee('2024-01-15')
        ->assertSee('Gregorian')
        ->assertSee('Jalali');
});
```

**Key Changes:**
1. Remove namespace and class structure
2. Convert `setUp()` to `beforeEach()` hook
3. Replace `$this->browse()` with direct `visit()` calls
4. Replace `$browser->script()` with `->evaluate()` for JavaScript execution
5. Replace `$this->assertDatabaseHas()` with Pest's `expect()->toBeTrue()` pattern
6. Replace `$this->assertDatabaseMissing()` with `expect()->toBeNull()`
7. Use `->on()->mobile()` for mobile viewport testing instead of `->resize()`
8. Update all test names to descriptive strings
9. Remove `/** @test */` annotations (not needed in Pest)

---

### Step 6: Migrate `ExampleTest.php`

**File:** `tests/Browser/ExampleTest.php`

**Current Structure:**
```php
<?php

use Laravel\Dusk\Browser;

test('basic example', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee('Laravel');
    });
});
```

**New Structure:**
```php
<?php

test('basic example', function () {
    $page = visit('/')
        ->assertSee('EpochBridge');
});
```

**Key Changes:**
1. Remove `use Laravel\Dusk\Browser`
2. Remove `$this->browse()` wrapper
3. Update assertion text to match actual content
4. Use `visit()` directly

---

### Step 7: Remove Dusk Infrastructure

**Files to Delete:**
- `tests/DuskTestCase.php`
- `tests/Browser/console/` (directory)
- `tests/Browser/screenshots/` (directory, will be replaced by `Screenshots/`)
- `tests/Browser/source/` (directory)
- `tests/Browser/Pages/` (directory - if not used for page objects in new setup)

**Commands:**
```bash
rm tests/DuskTestCase.php
rm -rf tests/Browser/console
rm -rf tests/Browser/screenshots
rm -rf tests/Browser/source
# Keep Pages/ if planning to use page object pattern with Pest
```

**Remove from `composer.json`:**
```bash
composer remove laravel/dusk --dev
```

**Environment Variables to Remove (from `.env`):**
```env
# Remove these if present
DUSK_DRIVER_URL=
```

---

### Step 8: Update Test Commands in Documentation

**Files:** `CLAUDE.md`, `README.md` (if applicable)

**Update Commands:**

**Old:**
```bash
# Run browser tests
php artisan dusk
sail dusk

# Run specific browser test
php artisan dusk --filter=ToastNotificationTest
sail dusk --filter=ToastNotificationTest
```

**New:**
```bash
# Run all tests (includes browser tests)
./vendor/bin/pest

# Run browser tests specifically
./vendor/bin/pest tests/Browser

# Run specific browser test
./vendor/bin/pest tests/Browser/ToastNotificationTest.php

# Run with Sail
sail pest
sail pest tests/Browser

# Run in parallel for performance
./vendor/bin/pest --parallel
```

---

### Step 9: Verify Migration

**Create Verification Checklist:**

**Test Execution:**
```bash
# 1. Install dependencies
composer install
npm install
npx playwright install

# 2. Run all browser tests
./vendor/bin/pest tests/Browser

# 3. Run specific test files
./vendor/bin/pest tests/Browser/ToastNotificationTest.php
./vendor/bin/pest tests/Browser/LandingPageTest.php
./vendor/bin/pest tests/Browser/SavedConversionsTest.php

# 4. Run with different browsers
./vendor/bin/pest tests/Browser --browser=firefox
./vendor/bin/pest tests/Browser --browser=safari

# 5. Run in parallel
./vendor/bin/pest tests/Browser --parallel
```

**Verification Checklist:**
- [ ] All 19 tests pass
- [ ] No Dusk/Selenium dependencies remain
- [ ] Screenshots directory created on first run
- [ ] Tests run faster than Dusk equivalents
- [ ] Mobile responsive tests work with `->on()->mobile()`
- [ ] Authentication tests work with `->actingAs()`
- [ ] JavaScript execution works with `->evaluate()`
- [ ] localStorage interactions work correctly
- [ ] Dialog interactions work (accept/dismiss)
- [ ] All assertions from original tests preserved

---

### Step 10: Optional Enhancements

**Advanced Features Available with Pest Browser Testing:**

**1. Cross-Browser Testing:**
```php
test('works in Firefox', function () {
    $page = visit('/')->on()->firefox()
        ->assertSee('EpochBridge');
});

test('works in Safari', function () {
    $page = visit('/')->on()->safari()
        ->assertSee('EpochBridge');
});
```

**2. Device-Specific Testing:**
```php
test('works on iPhone 14 Pro', function () {
    $page = visit('/')->on()->iPhone14Pro()
        ->assertSee('EpochBridge');
});

test('works on iPad', function () {
    $page = visit('/')->on()->iPad()
        ->assertSee('EpochBridge');
});
```

**3. Dark Mode Testing:**
```php
test('toast notifications work in dark mode', function () {
    $page = visit('/')->inDarkMode()
        ->click('Save')
        ->waitForText('Conversion saved successfully!');
});
```

**4. Screenshot on Failure:**
```php
test('conversion flow with screenshots', function () {
    $page = visit('/')
        ->type('dateInput', '2024-01-15')
        ->screenshot('before-convert') // Save screenshot
        ->click('Convert Date')
        ->waitForText('Gregorian')
        ->screenshot('after-convert'); // Save screenshot
});
```

**5. Network Interception (if needed):**
```php
test('handles API errors gracefully', function () {
    $page = visit('/')
        ->interceptRequest('*/api/*', fn($request) => $request->abort('Failed'))
        ->type('dateInput', '2024-01-15')
        ->click('Convert Date')
        ->assertSee('Error');
});
```

---

## API Translation Reference

### Common Dusk → Pest Browser Conversions

| Dusk Method | Pest Browser Equivalent |
|-------------|------------------------|
| `$browser->visit('/')` | `visit('/')` |
| `$browser->loginAs($user)` | `visit('/')->actingAs($user)` |
| `$browser->assertSee('text')` | `->assertSee('text')` |
| `$browser->type('input', 'value')` | `->type('input', 'value')` |
| `$browser->click('button')` | `->click('button')` |
| `$browser->press('Submit')` | `->click('Submit')` |
| `$browser->waitForText('text')` | `->waitForText('text')` |
| `$browser->waitForText('text', 10)` | `->waitForText('text', timeout: 10000)` |
| `$browser->assertVisible('.class')` | `->assertVisible('.class')` |
| `$browser->assertDontSee('text')` | `->assertDontSee('text')` |
| `$browser->pause(1000)` | `->pause(1000)` |
| `$browser->script('code')` | `->evaluate('code')` |
| `$browser->resize(375, 667)` | `->on()->mobile()` or specific device |
| `$browser->with('#selector', fn)` | `->within('#selector', fn)` |
| `$browser->waitForDialog()` | `->waitForDialog()` |
| `$browser->acceptDialog()` | `->acceptDialog()` |
| `$browser->waitUntilMissingText()` | `->waitUntilMissing()` |
| `$this->assertDatabaseHas()` | `expect(...)->toBeTrue()` |
| `$this->assertDatabaseMissing()` | `expect(...)->toBeNull()` |

### Selector Syntax (Remains Similar)

- CSS Selectors: `.class`, `#id`, `input[name="field"]`
- Text Content: `button:contains("Text")`
- Attribute Selectors: `button[wire\\:click*="method"]`
- Descendant: `#parent .child`

---

## Rollback Plan

If migration encounters critical issues:

**1. Restore Dusk:**
```bash
composer require laravel/dusk --dev
git restore tests/DuskTestCase.php
git restore tests/Browser/
```

**2. Remove Pest Browser:**
```bash
composer remove pestphp/pest-plugin-browser --dev
npm uninstall playwright
```

**3. Restore Original Tests:**
```bash
git checkout HEAD -- tests/Browser/
```

---

## Success Criteria

Migration is successful when:

1. ✅ All 19 browser tests pass with Pest Browser Testing
2. ✅ No Dusk/Selenium dependencies remain in `composer.json`
3. ✅ Tests execute faster than Dusk equivalents
4. ✅ No infrastructure setup required (no Selenium service)
5. ✅ Tests run in CI/CD pipeline without Docker Selenium
6. ✅ Mobile and responsive tests work correctly
7. ✅ Authentication and authorization tests pass
8. ✅ JavaScript/Alpine.js interactions work correctly
9. ✅ localStorage operations function properly
10. ✅ All assertions match original test coverage

---

## Timeline Estimate

**Phase 1: Setup (30 minutes)**
- Install Pest Browser plugin
- Install Playwright
- Update `.gitignore`

**Phase 2: Migration (2-3 hours)**
- Migrate `ExampleTest.php` (5 minutes)
- Migrate `ToastNotificationTest.php` (30 minutes)
- Migrate `LandingPageTest.php` (30 minutes)
- Migrate `SavedConversionsTest.php` (1.5 hours)

**Phase 3: Cleanup (30 minutes)**
- Remove Dusk infrastructure
- Update documentation
- Remove unused dependencies

**Phase 4: Verification (1 hour)**
- Run all tests
- Fix any failures
- Test parallel execution
- Test different browsers

**Total Estimated Time: 4-5 hours**

---

## Additional Resources

- [Pest Browser Testing Documentation](https://pestphp.com/docs/browser-testing)
- [Playwright Documentation](https://playwright.dev/docs/intro)
- [Pest Laravel Plugin](https://pestphp.com/docs/plugins#laravel)
- [Migration Troubleshooting Guide](https://pestphp.com/docs/troubleshooting)

---

*Document Version: 1.0*  
*Created: 2025-10-29*  
*Author: Claude Code*
