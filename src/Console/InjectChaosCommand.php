<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
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

        $this->info("Using probability: {$this->probability}");

        $controllerPath = app_path('Http/Controllers');
        $files = $this->getControllerFiles($controllerPath);

        if (empty($files)) {
            $this->error('No controllers found to process.');

            return 1;
        }

        [$processedCount, $injectedCount] = $this->processFiles($files);

        $this->info("Processed {$processedCount} controllers.");
        $this->info("Injected chaos into {$injectedCount} methods.");

        return 0;
    }

    private function getControllerFiles(string $controllerPath): array
    {
        $controllers = $this->option('controller');

        return empty($controllers)
            ? $this->getAllControllers($controllerPath)
            : $this->getSpecificControllers($controllerPath, $controllers);
    }

    private function getAllControllers(string $path): array
    {
        if (! File::isDirectory($path)) {
            $this->warn("Controllers directory not found: {$path}");

            return [];
        }

        return File::allFiles($path);
    }

    private function getSpecificControllers(string $controllerPath, array $controllerNames): array
    {
        $files = [];

        foreach ($controllerNames as $controllerName) {
            $relativePath = str_replace('\\', '/', $controllerName).'.php';
            $fullPath = $controllerPath.'/'.$relativePath;

            if (File::exists($fullPath)) {
                $files[] = new SplFileInfo($fullPath, $relativePath, $fullPath);
            } else {
                $this->warn("Controller not found: {$controllerName}");
            }
        }

        return $files;
    }

    private function processFiles(array $files): array
    {
        $processedCount = 0;
        $injectedCount = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $result = $this->injectChaosIntoFile($file);
            $processedCount++;
            $injectedCount += $result;
        }

        return [$processedCount, $injectedCount];
    }

    private function injectChaosIntoFile(SplFileInfo $file): int
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new ParentConnectingVisitor);

        $code = $file->getContents();
        $injectedCount = 0;

        try {
            $ast = $parser->parse($code);
            $ast = $traverser->traverse($ast);

            $modified = false;

            foreach ($ast as $node) {
                if ($node instanceof Namespace_) {
                    $modified = $this->processNamespaceNode($node, $modified, $injectedCount);
                } elseif ($node instanceof Node\Stmt\Class_) {
                    $modified = $this->processClassNode($node, $modified, $injectedCount);
                }
            }

            if ($modified) {
                $this->saveModifiedFile($file, $ast);
                $this->info("Injected chaos into {$file->getRelativePathname()}");
            }

            return $injectedCount;
        } catch (\Exception $e) {
            $this->error("Error processing {$file->getRelativePathname()}: {$e->getMessage()}");

            return 1;
        }
    }

    private function processNamespaceNode(Namespace_ $namespace, bool $modified, int &$injectedCount): bool
    {
        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Class_) {
                $modified = $this->processClassNode($stmt, $modified, $injectedCount);
            }
        }

        return $modified;
    }

    private function processClassNode(Node\Stmt\Class_ $class, bool $modified, int &$injectedCount): bool
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod &&
                ! $this->methodHasChaos($stmt) &&
                mt_rand() / mt_getrandmax() < $this->probability) {
                $this->injectChaosIntoMethod($stmt);
                $modified = true;
                $injectedCount++;
            }
        }

        return $modified;
    }

    private function methodHasChaos(Node\Stmt\ClassMethod $method): bool
    {
        foreach ($method->stmts ?? [] as $stmt) {
            if ($stmt instanceof Node\Stmt\Expression &&
                $stmt->expr instanceof Node\Expr\StaticCall &&
                $stmt->expr->class instanceof Node\Name &&
                $stmt->expr->name instanceof Node\Identifier &&
                $stmt->expr->class->toString() === 'LaravelJutsu\\Bazooka\\Facades\\Bazooka' &&
                $stmt->expr->name->toString() === 'chaos') {
                return true;
            }
        }

        return false;
    }

    private function injectChaosIntoMethod(Node\Stmt\ClassMethod $method): void
    {
        $chaosCall = new Node\Stmt\Expression(
            new Node\Expr\StaticCall(
                new Node\Name\FullyQualified('LaravelJutsu\\Bazooka\\Facades\\Bazooka'),
                'chaos'
            )
        );

        if (! isset($method->stmts)) {
            $method->stmts = [];
        }

        array_unshift($method->stmts, $chaosCall);
    }

    private function saveModifiedFile(SplFileInfo $file, array $ast): void
    {
        $printer = new PrettyPrinter\Standard;
        $newCode = $printer->prettyPrintFile($ast);
        File::put($file->getPathname(), $newCode);
    }
}
