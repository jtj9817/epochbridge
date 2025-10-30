<?php

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ========================================
// CREATE TESTS
// ========================================

test('note is fillable and can be created with conversion', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => ['test' => 'data'],
        'note' => 'Birthday reminder',
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/25',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00+00:00',
    ]);
    
    expect($conversion->note)->toBe('Birthday reminder')
        ->and($conversion->exists)->toBeTrue();
});

test('note is stored correctly', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => ['test' => 'data'],
        'note' => 'Project deadline - important',
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/25',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00+00:00',
    ]);
    
    $this->assertDatabaseHas('saved_conversions', [
        'id' => $conversion->id,
        'note' => 'Project deadline - important',
    ]);
});

test('empty note is handled properly', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => ['test' => 'data'],
        'note' => '',
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/25',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00+00:00',
    ]);
    
    expect($conversion->note)->toBe('')
        ->and($conversion->exists)->toBeTrue();
});

test('null note is handled properly', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => ['test' => 'data'],
        'note' => null,
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/25',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00+00:00',
    ]);
    
    expect($conversion->note)->toBeNull()
        ->and($conversion->exists)->toBeTrue();
});

test('note defaults to null when omitted', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => ['test' => 'data'],
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/25',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00+00:00',
    ]);
    
    expect($conversion->note)->toBeNull();
});

// ========================================
// READ TESTS
// ========================================

test('note is retrieved correctly from database', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => ['test' => 'data'],
        'note' => 'Anniversary celebration',
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/25',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00+00:00',
    ]);
    
    $retrieved = SavedConversion::find($conversion->id);
    
    expect($retrieved->note)->toBe('Anniversary celebration');
});

test('note accessor returns correct value', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Test note content',
    ]);
    
    expect($conversion->note)->toBe('Test note content')
        ->and($conversion->getAttribute('note'))->toBe('Test note content');
});

test('backward compatibility with null notes from legacy data', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => null,
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/25',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00+00:00',
    ]);
    
    expect($conversion->note)->toBeNull()
        ->and($conversion->hasLegacyValues())->toBeTrue();
});

// ========================================
// UPDATE TESTS
// ========================================

test('note can be updated independently of conversion', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);
    
    $conversion->update(['note' => 'Updated note']);
    
    expect($conversion->fresh()->note)->toBe('Updated note');
});

test('updating note to empty string works correctly', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);
    
    $conversion->update(['note' => '']);
    
    expect($conversion->fresh()->note)->toBe('');
});

test('note can be updated multiple times', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'First note',
    ]);
    
    $conversion->update(['note' => 'Second note']);
    expect($conversion->fresh()->note)->toBe('Second note');
    
    $conversion->update(['note' => 'Third note']);
    expect($conversion->fresh()->note)->toBe('Third note');
});

// ========================================
// DELETE TESTS
// ========================================

test('note is deleted with conversion cascade', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Will be deleted',
    ]);
    
    $conversionId = $conversion->id;
    $conversion->delete();
    
    $this->assertDatabaseMissing('saved_conversions', [
        'id' => $conversionId,
    ]);
});

test('note can be cleared by setting to empty', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);
    
    $conversion->update(['note' => '']);
    
    expect($conversion->fresh()->note)->toBe('');
    $this->assertDatabaseHas('saved_conversions', [
        'id' => $conversion->id,
        'note' => '',
    ]);
});

test('note can be set to null', function () {
    $user = User::factory()->create();
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);
    
    $conversion->update(['note' => null]);
    
    expect($conversion->fresh()->note)->toBeNull();
});

// ========================================
// VALIDATION TESTS
// ========================================

test('note handles special characters correctly', function () {
    $user = User::factory()->create();
    
    $specialNote = "Test with special chars: !@#$%^&*()_+-=[]{}|;':\",./<>?";
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => $specialNote,
    ]);
    
    expect($conversion->fresh()->note)->toBe($specialNote);
});

test('note handles unicode characters correctly', function () {
    $user = User::factory()->create();
    
    $unicodeNote = '日本語 العربية Ελληνικά 🎉🎊';
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => $unicodeNote,
    ]);
    
    expect($conversion->fresh()->note)->toBe($unicodeNote);
});

test('note preserves newlines and whitespace', function () {
    $user = User::factory()->create();
    
    $multilineNote = "Line 1\nLine 2\n  Indented Line 3";
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => $multilineNote,
    ]);
    
    expect($conversion->fresh()->note)->toBe($multilineNote);
});

test('note can store maximum length of 2048 characters', function () {
    $user = User::factory()->create();
    
    $longNote = str_repeat('a', 2048);
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => $longNote,
    ]);
    
    expect($conversion->fresh()->note)->toBe($longNote)
        ->and(strlen($conversion->fresh()->note))->toBe(2048);
});

test('note factory generates realistic test data', function () {
    $conversion = SavedConversion::factory()->create();
    
    expect($conversion->note)->toBeString()
        ->and(strlen($conversion->note))->toBeLessThanOrEqual(2048);
});
