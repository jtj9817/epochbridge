<?php

use App\Services\TestDiscoveryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(Tests\TestCase::class);

describe('TestDiscoveryService', function () {
    beforeEach(function () {
        Cache::flush();

        Storage::fake('test-runner');

        $this->service = app(TestDiscoveryService::class);

        $this->root = base_path('tests/tmp/test-discovery');

        if (File::exists($this->root)) {
            File::deleteDirectory($this->root);
        }

        File::makeDirectory($this->root.'/unit', 0755, true);
        File::makeDirectory($this->root.'/feature', 0755, true);
        File::makeDirectory($this->root.'/feature/Livewire', 0755, true);
        File::makeDirectory($this->root.'/browser', 0755, true);

        $relative = fn (string $suffix) => trim(str_replace(base_path().'/', '', $this->root.'/'.$suffix), '/');

        $this->createPestTest = function (string $path, string $className = 'SampleTest'): string {
            $full = $this->root.'/'.$path;
            File::ensureDirectoryExists(dirname($full));
            File::put($full, <<<PHP
<?php

test('{$className} runs', function () {
    expect(true)->toBeTrue();
});
PHP);

            return $full;
        };

        $this->createPhpunitTest = function (string $path, string $className): string {
            $full = $this->root.'/'.$path;
            File::ensureDirectoryExists(dirname($full));
            File::put($full, <<<PHP
<?php

use Tests\TestCase;

class {$className} extends TestCase
{
    public function test_example(): void
    {
        \$this->assertTrue(true);
    }
}
PHP);

            return $full;
        };

        $this->createPlainPhp = function (string $path, string $contents): string {
            $full = $this->root.'/'.$path;
            File::ensureDirectoryExists(dirname($full));
            File::put($full, $contents);

            return $full;
        };

        $this->service->setDiscoveryConfiguration([
            'paths' => [
                'unit' => $relative('unit'),
                'feature' => $relative('feature'),
                'livewire' => $relative('feature/Livewire'),
                'browser' => $relative('browser'),
            ],
            'patterns' => [
                'unit' => $relative('unit').'/*Test.php',
                'feature' => $relative('feature').'/*Test.php',
                'livewire' => $relative('feature/Livewire').'/*Test.php',
                'browser' => $relative('browser').'/*Test.php',
            ],
        ]);
    });

    afterEach(function () {
        if (File::exists($this->root)) {
            File::deleteDirectory($this->root);
        }
    });

    it('discovers tests across all categories', function () {
        $unit = ($this->createPestTest)('unit/SampleUnitTest.php');
        $feature = ($this->createPestTest)('feature/ExampleFeatureTest.php');
        $livewire = ($this->createPestTest)('feature/Livewire/LivewireWidgetTest.php');
        $browser = ($this->createPhpunitTest)('browser/UserJourneyTest.php', 'DuskUserJourneyTest');

        $result = $this->service->discoverAllTests(useCache: false);

        expect($result['unit'])->toHaveCount(1)
            ->and($result['unit'][0]['file_path'])->toBe($unit);

        expect($result['feature'])->toHaveCount(1)
            ->and($result['feature'][0]['file_path'])->toBe($feature);

        expect($result['livewire'])->toHaveCount(1)
            ->and($result['livewire'][0]['file_path'])->toBe($livewire);

        expect($result['browser'])->toHaveCount(1)
            ->and($result['browser'][0]['file_path'])->toBe($browser);

        expect($result['all'])->toHaveCount(4);
    });

    it('filters out livewire tests from generic feature set', function () {
        ($this->createPestTest)('feature/FeatureOnlyTest.php');
        ($this->createPestTest)('feature/Livewire/ComponentStateTest.php');

        $features = $this->service->getFeatureTests();
        $livewire = $this->service->getLivewireTests();

        expect($features)->toHaveCount(1)
            ->and($features[0]['filename'])->toBe('FeatureOnlyTest.php');

        expect($livewire)->toHaveCount(1)
            ->and($livewire[0]['filename'])->toBe('ComponentStateTest.php');
    });

    it('ignores non test php files and unreadable files', function () {
        ($this->createPlainPhp)('feature/helpers.php', '<?php echo "Hello";');

        $nonReadable = ($this->createPestTest)('unit/HiddenUnitTest.php');
        chmod($nonReadable, 0000);

        $discovered = $this->service->discoverAllTests(useCache: false);

        expect(collect($discovered['feature'])->pluck('filename'))
            ->not()->toContain('helpers.php');

        if (! File::isReadable($nonReadable)) {
            expect(collect($discovered['unit'])->pluck('filename'))
                ->not()->toContain('HiddenUnitTest.php');
        } else {
            $this->markTestSkipped('Unable to simulate unreadable file on this filesystem');
        }
    })->after(function () {
        if (File::exists($this->root.'/unit/HiddenUnitTest.php')) {
            chmod($this->root.'/unit/HiddenUnitTest.php', 0644);
        }
    });

    it('categorizes test files with expected metadata', function () {
        $path = ($this->createPestTest)('unit/App/Services/ComplexAggregationTest.php', 'ComplexAggregationTest');

        $method = new \ReflectionMethod(TestDiscoveryService::class, 'categorizeTestFile');
        $method->setAccessible(true);

        $file = new \SplFileInfo($path);

        $relativeBase = trim(str_replace(base_path().'/', '', $this->root.'/unit'), '/');

        $data = $method->invoke($this->service, $file, $relativeBase, 'unit');

        expect($data)->toBeArray()
            ->and($data['category'])->toBe('unit')
            ->and($data['relative_path'])->toBe('App/Services/ComplexAggregationTest.php')
            ->and($data['display_name'])->toBe('App › Services › Complex Aggregation');
    });

    it('validates Pest and PHPUnit test files correctly', function () {
        $validPest = ($this->createPestTest)('unit/ValidPestTest.php');
        $validPhpunit = ($this->createPhpunitTest)('feature/DomainTest.php', 'DomainFeatureTest');
        $invalid = ($this->createPlainPhp)('unit/Utilities.php', '<?php class Utilities {}');

        $method = new ReflectionMethod(TestDiscoveryService::class, 'validateTestFile');
        $method->setAccessible(true);

        expect($method->invoke($this->service, $validPest))->toBeTrue()
            ->and($method->invoke($this->service, $validPhpunit))->toBeTrue()
            ->and($method->invoke($this->service, $invalid))->toBeFalse();
    });

    it('handles empty directories and deeply nested structures', function () {
        File::makeDirectory($this->root.'/unit/Nested/Deep', 0755, true);
        $nested = ($this->createPestTest)('unit/Nested/Deep/DeeplyNestedTest.php');

        $unitTests = $this->service->getUnitTests();
        expect($unitTests)->toHaveCount(1)
            ->and($unitTests[0]['file_path'])->toBe($nested);

        File::deleteDirectory($this->root.'/livewire');

        $livewire = $this->service->getLivewireTests();
        expect($livewire)->toBeEmpty();
    });

    it('skips duplicate discovery via symlinks', function () {
        $target = ($this->createPestTest)('unit/OriginalTest.php');
        $symlink = $this->root.'/unit/SymlinkedTest.php';

        if (! function_exists('symlink') || @symlink($target, $symlink) === false) {
            $this->markTestSkipped('Symlinks are not supported on this platform.');
        }

        $result = collect($this->service->getUnitTests());

        $paths = $result->pluck('file_path');

        expect($paths)->toContain($target)
            ->and($paths)->toContain($symlink)
            ->and($paths->unique()->count())->toBe($paths->count());
    });

    it('discovers large test suites within acceptable thresholds', function () {
        $files = 1050;

        for ($i = 0; $i < $files; $i++) {
            $name = 'BulkTest'.Str::padLeft((string) $i, 4, '0').'Test.php';
            ($this->createPestTest)('unit/Large/'.$name, 'BulkTest'.$i);
        }

        $start = microtime(true);
        $unit = $this->service->getUnitTests();
        $duration = microtime(true) - $start;

        expect($unit)->toHaveCount($files)
            ->and($duration)->toBeLessThan(3.0);
    });
});
