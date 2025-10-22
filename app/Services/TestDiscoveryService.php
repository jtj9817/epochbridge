<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class TestDiscoveryService
{
    /**
     * Cache key for discovered tests
     */
    private const CACHE_KEY = 'test_runner_discovered_tests';

    /**
     * Cache TTL in seconds (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * Test directory structure
     */
    private array $paths = [
        'unit' => 'tests/Unit',
        'feature' => 'tests/Feature',
        'livewire' => 'tests/Feature/Livewire',
        'browser' => 'tests/Browser',
    ];

    /**
     * Pattern overrides for test discovery.
     *
     * @var array<string, string>
     */
    private array $patterns = [
        'unit' => 'tests/Unit/*Test.php',
        'feature' => 'tests/Feature/*Test.php',
        'livewire' => 'tests/Feature/Livewire/*Test.php',
        'browser' => 'tests/Browser/*Test.php',
    ];

    /**
     * Apply discovery configuration provided by service provider.
     *
     * @param  array<string, mixed>  $configuration
     */
    public function setDiscoveryConfiguration(array $configuration): void
    {
        if (! empty($configuration['paths']) && is_array($configuration['paths'])) {
            foreach ($configuration['paths'] as $key => $path) {
                if (isset($this->paths[$key]) && is_string($path) && $path !== '') {
                    $this->paths[$key] = $path;
                }
            }
        }

        if (! empty($configuration['patterns']) && is_array($configuration['patterns'])) {
            foreach ($configuration['patterns'] as $key => $pattern) {
                if (is_string($pattern) && $pattern !== '') {
                    $this->patterns[$key] = $pattern;
                }
            }
        }

        $this->clearCache();
    }

    /**
     * Discover all test files in the project
     *
     * @param  bool  $useCache  Whether to use cached results
     * @return array<string, array> Categorized test files
     */
    public function discoverAllTests(bool $useCache = true): array
    {
        if ($useCache) {
            try {
                if (Cache::has(self::CACHE_KEY)) {
                    return Cache::get(self::CACHE_KEY);
                }
            } catch (\Exception $e) {
                // Cache not available, continue without caching
                $useCache = false;
            }
        }

        $discovered = [
            'unit' => $this->getUnitTests(),
            'feature' => $this->getFeatureTests(),
            'livewire' => $this->getLivewireTests(),
            'browser' => $this->getBrowserTests(),
            'all' => [],
        ];

        // Combine all tests for 'all' category
        $discovered['all'] = array_merge(
            $discovered['unit'],
            $discovered['feature'],
            $discovered['livewire'],
            $discovered['browser']
        );

        // Cache the results if cache is available
        if ($useCache) {
            try {
                Cache::put(self::CACHE_KEY, $discovered, self::CACHE_TTL);
            } catch (\Exception $e) {
                // Silently fail if caching is not available
            }
        }

        return $discovered;
    }

    /**
     * Get all unit test files
     *
     * @return array<int, array> Array of test file information
     */
    public function getUnitTests(): array
    {
        return $this->scanDirectory($this->paths['unit'], 'unit');
    }

    /**
     * Get all feature test files (excluding Livewire)
     *
     * @return array<int, array> Array of test file information
     */
    public function getFeatureTests(): array
    {
        $featurePath = $this->paths['feature'];
        $allFeatureTests = $this->scanDirectory($featurePath, 'feature');

        // Filter out Livewire tests from feature tests when configured
        $livewireBase = rtrim(base_path($this->paths['livewire']), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if ($this->paths['livewire'] === '' || ! File::isDirectory(base_path($this->paths['livewire']))) {
            return $allFeatureTests;
        }

        return array_filter($allFeatureTests, function ($test) use ($livewireBase) {
            return ! str_starts_with($test['file_path'], $livewireBase);
        });
    }

    /**
     * Get all Livewire test files
     *
     * @return array<int, array> Array of test file information
     */
    public function getLivewireTests(): array
    {
        $livewireDirectory = $this->paths['livewire'];

        if ($livewireDirectory === '' || ! File::isDirectory(base_path($livewireDirectory))) {
            return [];
        }

        return $this->scanDirectory($livewireDirectory, 'livewire');
    }

    /**
     * Get all browser test files
     *
     * @return array<int, array> Array of test file information
     */
    public function getBrowserTests(): array
    {
        return $this->scanDirectory($this->paths['browser'], 'browser');
    }

    /**
     * Scan directory for test files recursively
     *
     * @param  string  $directory  Directory to scan (relative to base path)
     * @param  string  $category  Test category
     * @return array<int, array> Array of test file information
     */
    private function scanDirectory(string $directory, string $category): array
    {
        $fullPath = base_path($directory);

        if (! File::isDirectory($fullPath)) {
            return [];
        }

        $testFiles = [];
        $files = File::allFiles($fullPath);

        $pattern = $this->patterns[$category] ?? null;

        foreach ($files as $file) {
            if ($this->validateTestFile($file->getPathname())) {
                if ($pattern) {
                    $relativeToBase = ltrim(str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()), DIRECTORY_SEPARATOR);
                    if (! fnmatch($pattern, $relativeToBase)) {
                        continue;
                    }
                }

                $testInfo = $this->categorizeTestFile($file, $directory, $category);
                if ($testInfo !== null) {
                    $testFiles[] = $testInfo;
                }
            }
        }

        // Sort by relative path for consistent ordering
        usort($testFiles, fn ($a, $b) => strcmp($a['relative_path'], $b['relative_path']));

        return $testFiles;
    }

    /**
     * Categorize a test file and extract metadata
     *
     * @param  \SplFileInfo  $file  File information
     * @param  string  $baseDirectory  Base directory for relative path calculation
     * @param  string  $category  Test category
     * @return array|null Test file information or null if invalid
     */
    private function categorizeTestFile(\SplFileInfo $file, string $baseDirectory, string $category): ?array
    {
        $base = rtrim(base_path($baseDirectory), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $relativePath = ltrim(str_replace($base, '', $file->getPathname()), DIRECTORY_SEPARATOR);

        // Skip if not a PHP file
        if ($file->getExtension() !== 'php') {
            return null;
        }

        // Skip base test case files and page objects
        if (str_contains($relativePath, 'TestCase.php') ||
            str_contains($relativePath, 'Pages/') ||
            str_contains($relativePath, 'console/') ||
            str_contains($relativePath, 'screenshots/') ||
            str_contains($relativePath, 'source/')) {
            return null;
        }

        $className = $this->extractClassName($file->getPathname());
        $namespace = $this->extractNamespace($file->getPathname());

        return [
            'file_path' => $file->getPathname(),
            'relative_path' => $relativePath,
            'directory' => dirname($relativePath),
            'filename' => $file->getFilename(),
            'class_name' => $className,
            'namespace' => $namespace,
            'full_class_name' => $namespace ? $namespace.'\\'.$className : $className,
            'category' => $category,
            'display_name' => $this->generateDisplayName($relativePath, $className),
            'file_size' => $file->getSize(),
            'modified_time' => $file->getMTime(),
        ];
    }

    /**
     * Validate that a file is an executable test file
     *
     * @param  string  $filePath  Path to the file
     * @return bool True if file is a valid test file
     */
    private function validateTestFile(string $filePath): bool
    {
        // Must be a PHP file
        if (! str_ends_with($filePath, '.php')) {
            return false;
        }

        // Must be readable
        if (! File::isReadable($filePath)) {
            return false;
        }

        try {
            $content = File::get($filePath);

            // Check for PestPHP test patterns
            if (preg_match('/\b(it|test|describe|beforeEach|afterEach)\s*\(/', $content)) {
                return true;
            }

            // Check for traditional PHPUnit class-based tests
            if (preg_match('/class\s+\w+/', $content)) {
                return str_contains($content, 'extends TestCase') ||
                       str_contains($content, 'extends DuskTestCase') ||
                       preg_match('/function\s+test\w+/', $content);
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract class name from PHP file
     *
     * @param  string  $filePath  Path to the PHP file
     * @return string Class name or filename if no class found
     */
    private function extractClassName(string $filePath): string
    {
        try {
            $content = File::get($filePath);

            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                return $matches[1];
            }
        } catch (\Exception $e) {
            // Fall back to filename
        }

        // For PestPHP files or files without classes, use filename
        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Extract namespace from PHP file
     *
     * @param  string  $filePath  Path to the PHP file
     * @return string|null Namespace or null if not found
     */
    private function extractNamespace(string $filePath): ?string
    {
        try {
            $content = File::get($filePath);

            if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                return trim($matches[1]);
            }
        } catch (\Exception $e) {
            // Namespace is optional
        }

        return null;
    }

    /**
     * Generate a human-readable display name for the test
     *
     * @param  string  $relativePath  Relative path to the test file
     * @param  string  $className  Class name
     * @return string Display name
     */
    private function generateDisplayName(string $relativePath, string $className): string
    {
        // Remove .php extension and convert path separators
        $displayPath = str_replace(['/', '\\'], ' › ', dirname($relativePath));

        // Clean up class name (remove Test suffix)
        $cleanClassName = preg_replace('/Test$/', '', $className);

        // Add spacing to camelCase
        $cleanClassName = preg_replace('/([a-z])([A-Z])/', '$1 $2', $cleanClassName);

        if ($displayPath === '.') {
            return $cleanClassName;
        }

        return $displayPath.' › '.$cleanClassName;
    }

    /**
     * Get test statistics
     *
     * @return array<string, int> Test count statistics
     */
    public function getTestStatistics(): array
    {
        $discovered = $this->discoverAllTests();

        return [
            'total' => count($discovered['all']),
            'unit' => count($discovered['unit']),
            'feature' => count($discovered['feature']),
            'livewire' => count($discovered['livewire']),
            'browser' => count($discovered['browser']),
        ];
    }

    /**
     * Clear the test discovery cache
     */
    public function clearCache(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Exception $e) {
            // Cache not available, nothing to clear
        }
    }

    /**
     * Search test files by name or path
     *
     * @param  string  $query  Search query
     * @param  array|null  $categories  Categories to search in (null for all)
     * @return array<int, array> Matching test files
     */
    public function searchTests(string $query, ?array $categories = null): array
    {
        $discovered = $this->discoverAllTests();
        $searchCategories = $categories ?? ['unit', 'feature', 'livewire', 'browser'];

        $results = [];

        foreach ($searchCategories as $category) {
            if (! isset($discovered[$category])) {
                continue;
            }

            foreach ($discovered[$category] as $test) {
                if (str_contains(strtolower($test['display_name']), strtolower($query)) ||
                    str_contains(strtolower($test['relative_path']), strtolower($query)) ||
                    str_contains(strtolower($test['class_name']), strtolower($query))) {
                    $results[] = $test;
                }
            }
        }

        return $results;
    }
}
