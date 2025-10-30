# Guest Conversions Guide

## Overview

EpochBridge provides full conversion storage functionality for both authenticated and guest users through a seamless localStorage-based persistence system. Guest users can save, view, and manage their conversion history without creating an account, with automatic migration to the database upon authentication.

## Architecture

### Data Flow

```
┌─────────────────────────────────────────────────┐
│              Guest User Journey                  │
├─────────────────────────────────────────────────┤
│                                                  │
│  1. User performs date conversion               │
│           ↓                                      │
│  2. Clicks "Save" button                        │
│           ↓                                      │
│  3. Data saved to browser localStorage          │
│           ↓                                      │
│  4. Client-side render in saved conversions     │
│           ↓                                      │
│  5. Persists across browser sessions            │
│                                                  │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│         Authentication Migration                 │
├─────────────────────────────────────────────────┤
│                                                  │
│  1. Guest user registers/logs in                │
│           ↓                                      │
│  2. Landing component detects guest data        │
│           ↓                                      │
│  3. Automatic migration to database             │
│           ↓                                      │
│  4. user_id field populated                     │
│           ↓                                      │
│  5. localStorage cleared                        │
│           ↓                                      │
│  6. User sees complete history                  │
│                                                  │
└─────────────────────────────────────────────────┘
```

## Implementation Details

### Frontend Storage (Alpine.js)

#### localStorage Structure

```javascript
// Key: 'guestConversions'
// Value: JSON array of conversion objects
[
  {
    "conversion_data": {
      "gregorian": { /* full gregorian data */ },
      "jalali": { /* full jalali data */ },
      "islamic": { /* full islamic data */ },
      // ... all 19 calendar systems
    },
    "note": "Important historical date for project milestone",  // Optional note (up to 2048 chars)
    "input_date": "2024-01-15",
    "created_at": "2024-01-15T10:30:00.000000Z"
  },
  // ... more conversions
]
```

#### Alpine.js Component State

Located in `resources/views/livewire/landing.blade.php`:

```javascript
x-data="{
    guestConversions: [],
    
    init() {
        // Load guest conversions from localStorage on component mount
        this.loadGuestConversions();
    },
    
    loadGuestConversions() {
        const stored = localStorage.getItem('guestConversions');
        this.guestConversions = stored ? JSON.parse(stored) : [];
    },
    
    saveGuestConversion(conversionData, inputDate, note = null) {
        const conversion = {
            conversion_data: conversionData,
            note: note,  // Optional note field
            input_date: inputDate,
            created_at: new Date().toISOString()
        };
        
        this.guestConversions.unshift(conversion);
        localStorage.setItem('guestConversions', JSON.stringify(this.guestConversions));
        
        // Trigger UI update
        this.$dispatch('conversion-saved');
    },
    
    deleteGuestConversion(index) {
        this.guestConversions.splice(index, 1);
        localStorage.setItem('guestConversions', JSON.stringify(this.guestConversions));
    }
}"
```

### Backend Migration (Livewire)

#### Migration Method

Located in `resources/views/livewire/landing.blade.php`:

```php
public function migrateGuestData(array $guestConversions): void
{
    if (!auth()->check()) {
        return;
    }
    
    try {
        foreach ($guestConversions as $conversion) {
            SavedConversion::create([
                'user_id' => auth()->id(),
                'conversion_data' => $conversion['conversion_data'],
                'input_date' => $conversion['input_date'] ?? null,
                'created_at' => $conversion['created_at'] ?? now(),
            ]);
        }
        
        // Notify user of successful migration
        $this->dispatch('guest-data-migrated', count: count($guestConversions));
        
    } catch (\Exception $e) {
        Log::error('Guest data migration failed', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'conversion_count' => count($guestConversions)
        ]);
        
        $this->dispatch('migration-error');
    }
}
```

#### Automatic Migration Trigger

```javascript
// In Alpine.js component
document.addEventListener('livewire:navigated', () => {
    if (@js(auth()->check())) {
        const guestData = localStorage.getItem('guestConversions');
        
        if (guestData) {
            const conversions = JSON.parse(guestData);
            
            if (conversions.length > 0) {
                // Call Livewire method to migrate data
                @this.call('migrateGuestData', conversions);
                
                // Clear localStorage after migration
                localStorage.removeItem('guestConversions');
            }
        }
    }
});
```

## Database Schema

### SavedConversion Model

```php
// app/Models/SavedConversion.php
class SavedConversion extends Model
{
    protected $fillable = [
        'user_id',
        'conversion_data',
        'input_date',
    ];
    
    protected $casts = [
        'conversion_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // Relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Accessor for legacy field support
    public function getConversionDataAttribute($value)
    {
        $data = json_decode($value, true);
        
        // Hydrate legacy fields if needed
        return $this->hydrateLegacyFields($data);
    }
    
    protected function hydrateLegacyFields(array $data): array
    {
        // Support for old data formats
        if (!isset($data['input_date']) && $this->input_date) {
            $data['input_date'] = $this->input_date;
        }
        
        return $data;
    }
}
```

### Migration File

```php
// database/migrations/*_create_saved_conversions_table.php
Schema::create('saved_conversions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
    $table->json('conversion_data');
    $table->string('input_date')->nullable();
    $table->timestamps();
    
    // Indexes for performance
    $table->index(['user_id', 'created_at']);
});
```

## Component Structure

### Saved Conversions Section

Located in `resources/views/livewire/components/saved-conversions-section.blade.php`:

```php
<?php
use function Livewire\Volt\{state, computed, on};

// Component state
state(['showCount' => 10]);

// Computed property for authenticated users
computed(fn () => auth()->check() 
    ? auth()->user()
        ->savedConversions()
        ->orderBy('created_at', 'desc')
        ->limit($this->showCount)
        ->get()
    : collect()
)->name('savedConversions');

// Load more functionality
$loadMore = function () {
    $this->showCount += 10;
};

?>

<div x-data="{
    guestConversions: [],
    
    init() {
        if (!@js(auth()->check())) {
            this.loadGuestConversions();
        }
    },
    
    loadGuestConversions() {
        const stored = localStorage.getItem('guestConversions');
        this.guestConversions = stored ? JSON.parse(stored) : [];
    }
}">
    <flux:card>
        <flux:heading size="lg">Saved Conversions</flux:heading>
        
        @auth
            <!-- Authenticated user conversions from database -->
            @foreach($this->savedConversions as $conversion)
                <livewire:components.saved-conversion-item 
                    :conversion="$conversion" 
                    :key="'conversion-'.$conversion->id" 
                />
            @endforeach
            
            @if($this->savedConversions->count() >= $showCount)
                <flux:button wire:click="loadMore">Load More</flux:button>
            @endif
        @else
            <!-- Guest conversions from localStorage -->
            <template x-for="(conversion, index) in guestConversions" :key="index">
                <livewire:components.saved-conversion-item 
                    :conversion="conversion" 
                    :is-guest="true"
                    :index="index"
                />
            </template>
            
            <template x-if="guestConversions.length === 0">
                <flux:text>No saved conversions yet.</flux:text>
            </template>
        @endauth
    </flux:card>
</div>
```

### Saved Conversion Item

Located in `resources/views/livewire/components/saved-conversion-item.blade.php`:

```php
<?php
use function Livewire\Volt\{state, mount};

state(['conversion', 'isGuest' => false, 'index' => null]);

mount(function () {
    // Component initialization
});

$delete = function () {
    if ($this->isGuest) {
        $this->dispatch('delete-guest-conversion', index: $this->index);
    } else {
        $this->conversion->delete();
        $this->dispatch('conversion-deleted');
    }
};

?>

<div class="border rounded-lg p-4 mb-4">
    <!-- Table layout for conversion data -->
    <div class="mb-2">
        <flux:heading size="sm">
            {{ $isGuest 
                ? $conversion['input_date'] 
                : $conversion->input_date }}
        </flux:heading>
        <flux:text size="sm" class="text-zinc-500">
            {{ $isGuest 
                ? \Carbon\Carbon::parse($conversion['created_at'])->diffForHumans() 
                : $conversion->created_at->diffForHumans() }}
        </flux:text>
    </div>
    
    <table class="w-full text-sm">
        <tbody>
            @php
                $data = $isGuest 
                    ? $conversion['conversion_data'] 
                    : $conversion->conversion_data;
            @endphp
            
            @foreach(['gregorian', 'jalali', 'islamic', 'hebrew', 'chinese', 'japanese'] as $calendar)
                @if(isset($data[$calendar]))
                    <tr>
                        <td class="font-semibold py-1">{{ ucfirst($calendar) }}</td>
                        <td class="py-1">{{ $data[$calendar]['full_date'] ?? 'N/A' }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
    
    <div class="mt-4 flex gap-2">
        <flux:button size="sm" @click="copyToClipboard">Copy</flux:button>
        <flux:button size="sm" variant="danger" wire:click="delete">Delete</flux:button>
    </div>
</div>
```

## User Experience Flow

### Guest User Experience

1. **Initial Visit**
   - User lands on conversion page
   - No account required
   - Full functionality available

2. **Performing Conversions**
   - Enter date in any supported format
   - View results across all 19 calendar systems
   - Click "Save" to store conversion

3. **Viewing Saved Conversions**
   - Saved conversions appear immediately
   - Stored in browser localStorage
   - Persists across sessions
   - Client-side rendering for fast display

4. **Managing Conversions**
   - Delete unwanted conversions
   - Copy conversion data to clipboard
   - No server requests needed

### Authenticated User Experience

1. **New User Registration**
   - Guest data automatically detected
   - Transparent migration to database
   - No data loss
   - Complete history immediately available

2. **Existing User Login**
   - Any guest conversions migrated
   - Database-backed storage
   - Synced across devices
   - Full management features

3. **Enhanced Features**
   - Search and filter conversions
   - Pagination for large histories
   - Server-side persistence
   - Data backup and recovery

## Edge Cases and Error Handling

### localStorage Quota Exceeded

```javascript
saveGuestConversion(data) {
    try {
        localStorage.setItem('guestConversions', JSON.stringify(this.guestConversions));
    } catch (e) {
        if (e.name === 'QuotaExceededError') {
            // Remove oldest conversions to make space
            this.guestConversions = this.guestConversions.slice(0, 50);
            localStorage.setItem('guestConversions', JSON.stringify(this.guestConversions));
            
            alert('Storage limit reached. Oldest conversions have been removed.');
        }
    }
}
```

### Migration Failure Recovery

```php
public function migrateGuestData(array $guestConversions): void
{
    DB::beginTransaction();
    
    try {
        foreach ($guestConversions as $conversion) {
            // Validate data structure
            if (!isset($conversion['conversion_data']) || !isset($conversion['input_date'])) {
                Log::warning('Invalid guest conversion data', ['data' => $conversion]);
                continue;
            }
            
            SavedConversion::create([
                'user_id' => auth()->id(),
                'conversion_data' => $conversion['conversion_data'],
                'input_date' => $conversion['input_date'],
                'created_at' => $conversion['created_at'] ?? now(),
            ]);
        }
        
        DB::commit();
        $this->dispatch('guest-data-migrated', count: count($guestConversions));
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Guest data migration failed', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
        ]);
        
        // Keep guest data in localStorage for retry
        $this->dispatch('migration-error', message: 'Failed to migrate saved conversions. Please try again.');
    }
}
```

### Browser Compatibility

```javascript
// Check localStorage availability
function isLocalStorageAvailable() {
    try {
        const test = '__storage_test__';
        localStorage.setItem(test, test);
        localStorage.removeItem(test);
        return true;
    } catch (e) {
        return false;
    }
}

// Fallback to in-memory storage
if (!isLocalStorageAvailable()) {
    console.warn('localStorage not available, using in-memory storage');
    window.guestConversionsMemory = [];
}
```

## Testing

### Feature Tests

```php
// tests/Feature/Livewire/GuestConversionsTest.php
test('guest can save conversions to localStorage', function () {
    Livewire::test('landing')
        ->set('dateInput', '2024-01-15')
        ->call('convertDate')
        ->call('saveConversion')
        ->assertDispatched('conversion-saved');
});

test('guest data migrates on authentication', function () {
    $guestData = [
        [
            'conversion_data' => ['gregorian' => ['full_date' => '2024-01-15']],
            'input_date' => '2024-01-15',
            'created_at' => now()->toISOString(),
        ]
    ];
    
    $user = User::factory()->create();
    
    actingAs($user);
    
    Livewire::test('landing')
        ->call('migrateGuestData', $guestData)
        ->assertDispatched('guest-data-migrated');
    
    expect(SavedConversion::where('user_id', $user->id)->count())->toBe(1);
});
```

### Browser Tests

```php
// tests/Browser/GuestConversionsTest.php
test('guest conversions persist across page reloads', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->type('@date-input', '2024-01-15')
            ->click('@convert-button')
            ->click('@save-button')
            ->refresh()
            ->assertSee('2024-01-15');
    });
});
```

## Performance Considerations

### localStorage Optimization

- **Data Compression**: Consider LZ-string for large datasets
- **Selective Storage**: Only store essential fields
- **Pagination**: Limit displayed conversions
- **Lazy Loading**: Load conversion details on demand

### Migration Performance

- **Batch Processing**: Migrate conversions in chunks
- **Background Jobs**: Use queues for large migrations
- **Progress Feedback**: Show migration progress to user
- **Error Recovery**: Implement retry logic for failed migrations

## Security Considerations

### Data Privacy

- **No Sensitive Data**: Don't store PII in localStorage
- **Client-Side Only**: Guest conversions are client-side only
- **HTTPS Required**: Ensure secure transmission
- **XSS Protection**: Sanitize all user inputs

### Authentication

- **CSRF Protection**: All migration requests include CSRF token
- **User Verification**: Verify user authentication before migration
- **Rate Limiting**: Prevent abuse of migration endpoint

## Future Enhancements

### Planned Features

1. **Export Functionality**
   - Export guest conversions to JSON/CSV
   - Import previously exported data
   - Backup and restore capabilities

2. **Sync Across Devices**
   - Optional cloud sync for guest users
   - Anonymous sync tokens
   - Conflict resolution

3. **Advanced Storage**
   - IndexedDB for larger datasets
   - Service worker for offline support
   - Progressive Web App capabilities

4. **Enhanced Migration**
   - Duplicate detection
   - Merge strategies
   - Selective migration options

## Troubleshooting

### Common Issues

**Issue**: Saved conversions not appearing
- **Solution**: Check browser console for localStorage errors
- **Solution**: Verify localStorage is enabled in browser settings

**Issue**: Migration not triggered on login
- **Solution**: Check Livewire event listeners are properly registered
- **Solution**: Verify user authentication state

**Issue**: localStorage quota exceeded
- **Solution**: Reduce number of saved conversions
- **Solution**: Clear old conversions manually

## Conclusion

The guest conversions system provides a seamless experience for both authenticated and unauthenticated users, encouraging user engagement while maintaining data integrity and privacy. The automatic migration system ensures no data loss when guests create accounts, creating a frictionless path to user registration.
