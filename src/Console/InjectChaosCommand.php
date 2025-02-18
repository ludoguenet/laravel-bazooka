<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class InjectChaosCommand extends Command
{
    protected $signature = 'bazooka:inject {--controller=* : Specific controllers to target}';

    protected $description = 'Inject chaos points into controller methods based on configuration probability';

    private float $probability;

    public function __construct()
    {
        parent::__construct();
        $this->probability = Config::get('bazooka.probability', 0.2);
    }

    public function handle(): int
    {
        if (! Config::get('bazooka.enabled', false)) {
            $this->error('Bazooka is currently disabled in configuration.');

            return 1;
        }

        $files = $this->getControllerFiles(app_path('Http/Controllers'));

        if (empty($files)) {
            $this->error('No controllers found to process.');

            return 1;
        }

        [$processedCount, $injectedCount] = $this->processFiles($files);

        $this->info(sprintf(
            'Processed %d controller%s.',
            $processedCount,
            $processedCount === 1 ? '' : 's'
        ));

        $this->info(sprintf(
            'Injected chaos into %d method%s.',
            $injectedCount,
            $injectedCount === 1 ? '' : 's'
        ));

        return 0;
    }

    private function getControllerFiles(string $path): array
    {
        $controllers = $this->option('controller');

        return empty($controllers) ? $this->getAllControllers($path) : $this->getSpecificControllers($path, $controllers);
    }

    private function getAllControllers(string $path): array
    {
        return File::isDirectory($path) ? File::allFiles($path) : [];
    }

    private function getSpecificControllers(string $path, array $controllerNames): array
    {
        $files = [];
        foreach ($controllerNames as $name) {
            $fullPath = $path.'/'.str_replace('\\', '/', $name).'.php';
            if (File::exists($fullPath)) {
                $files[] = new SplFileInfo($fullPath, basename($fullPath), $fullPath);
            } else {
                $this->warn("Warning: Controller not found: {$name}");
            }
        }

        return $files;
    }

    private function processFiles(array $files): array
    {
        $processedCount = $injectedCount = 0;

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $injectedInFile = $this->injectChaosIntoFile($file);
                $injectedCount += $injectedInFile;
                if ($injectedInFile > 0) {
                    $this->info("Injected chaos into {$file->getRelativePathname()}");
                }
                $processedCount++;
            }
        }

        return [$processedCount, $injectedCount];
    }

    private function injectChaosIntoFile(SplFileInfo $file): int
    {
        try {
            $content = $file->getContents();
            $injectedCount = 0;

            // Use regex to find all method definitions and inject chaos
            $pattern = '/public\s+function\s+(\w+)\s*\([^)]*\)\s*(?::\s*[^{]+)?\s*\{/';

            // Find all method positions
            preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

            // Process matches in reverse order to maintain offsets
            $positions = array_reverse($matches[0]);

            foreach ($positions as $match) {
                $methodStart = $match[1];
                $methodBody = substr($content, $methodStart);

                // Find opening brace position
                $bracePos = strpos($methodBody, '{') + 1;
                $insertPosition = $methodStart + $bracePos;

                // Check if method already has chaos
                $nextChunk = substr($content, $insertPosition, 100);
                if (str_contains($nextChunk, 'LaravelJutsu\\Bazooka\\Facades\\Bazooka::chaos()')) {
                    continue;
                }

                // Insert chaos only by random chance
                if (mt_rand() / mt_getrandmax() < $this->probability) {
                    // Get the indentation from the next line
                    if (preg_match('/\n(\s+)/', $nextChunk, $indentMatch)) {
                        $indent = $indentMatch[1];

                        // Create the chaos line with proper indentation
                        $chaosLine = "\n{$indent}\\LaravelJutsu\\Bazooka\\Facades\\Bazooka::chaos();";

                        // Insert the chaos at the beginning of the method body, after {
                        $content = substr_replace($content, $chaosLine, $insertPosition, 0);
                        $injectedCount++;
                    }
                }
            }

            if ($injectedCount > 0) {
                File::put($file->getPathname(), $content);
            }

            return $injectedCount;
        } catch (\Exception $e) {
            $this->error("Error processing {$file->getRelativePathname()}: {$e->getMessage()}");

            return 0;
        }
    }
}
