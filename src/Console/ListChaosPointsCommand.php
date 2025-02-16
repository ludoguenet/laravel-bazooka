<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class ListChaosPointsCommand extends Command
{
    protected $signature = 'bazooka:list';

    protected $description = 'List all chaos points in the application';

    public function handle(): int
    {
        $chaosPoints = $this->findChaosPoints();

        if (empty($chaosPoints)) {
            $this->info('No chaos points found in the application.');

            return 0;
        }

        $this->table(['File', 'Line', 'Method'], $chaosPoints);

        return 0;
    }

    private function findChaosPoints(): array
    {
        $points = [];
        $files = File::allFiles(app_path());

        foreach ($files as $file) {
            $this->processChaosPointsInFile($file, $points);
        }

        return $points;
    }

    private function processChaosPointsInFile(SplFileInfo $file, array &$points): void
    {
        if ($file->getExtension() !== 'php') {
            return;
        }

        $content = $file->getContents();
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            if (str_contains($line, 'Bazooka::chaos()')) {
                $methodName = $this->findMethodName($lines, $lineNumber);
                $points[] = [
                    'file' => $file->getRelativePathname(),
                    'line' => $lineNumber + 1,
                    'method' => $methodName ?? 'Unknown',
                ];
            }
        }
    }

    private function findMethodName(array $lines, int $lineNumber): ?string
    {
        for ($i = $lineNumber; $i >= 0; $i--) {
            if (preg_match('/function\s+(\w+)\s*\(/', $lines[$i], $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
