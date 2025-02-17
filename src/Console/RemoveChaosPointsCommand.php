<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RemoveChaosPointsCommand extends Command
{
    protected $signature = 'bazooka:remove {--dry-run : Show what would be removed without actually removing}';

    protected $description = 'Remove all chaos points from the application';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $files = File::allFiles(app_path());
        $totalRemoved = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = $file->getContents();
            // Pattern pour capturer l'indentation, la ligne Bazooka::chaos(), et le reste du code
            $pattern = '/(\s*)\{[\s\n]*\\\\LaravelJutsu\\\\Bazooka\\\\Facades\\\\Bazooka::chaos\(\);[\s\n]*([^}]+)\}/s';
            // Remplacement pour supprimer uniquement la ligne Bazooka::chaos() et conserver le reste
            $replacement = '$1{'.PHP_EOL.'$1    $2'.PHP_EOL.'$1}';
            $newContent = preg_replace($pattern, $replacement, $content, -1, $count);

            if ($count > 0) {
                $action = $isDryRun ? 'Would remove' : 'Removed';
                $this->info("{$action} {$count} chaos points from {$file->getRelativePathname()}");

                if (! $isDryRun) {
                    File::put($file->getPathname(), $newContent);
                }

                $totalRemoved += $count;
            }
        }

        $this->info(($isDryRun ? 'Would remove' : 'Removed')." {$totalRemoved} chaos points in total.");

        return 0;
    }
}
