<?php

declare(strict_types=1);

namespace LaravelJutsu\Bazooka\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Expression;
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
                $injectedCount += $this->injectChaosIntoFile($file);
                $processedCount++;
            }
        }

        return [$processedCount, $injectedCount];
    }

    private function injectChaosIntoFile(SplFileInfo $file): int
    {
        try {
            $parser = (new ParserFactory)->createForNewestSupportedVersion();
            $traverser = new NodeTraverser;
            $traverser->addVisitor(new ParentConnectingVisitor);

            $ast = $traverser->traverse($parser->parse($file->getContents()));
            $injectedCount = 0;
            $modified = false;

            foreach ($ast as $node) {
                if ($node instanceof Namespace_) {
                    $modified = $this->processNamespaceNode($node, $injectedCount);
                }
            }

            if ($modified) {
                $this->saveModifiedFile($file, $ast);
                $this->info("Injected chaos into {$file->getRelativePathname()}");
            }

            return $injectedCount;
        } catch (\Exception $e) {
            $this->error("Error processing {$file->getRelativePathname()}: {$e->getMessage()}");

            return 0;
        }
    }

    private function processNamespaceNode(Namespace_ $namespace, int &$injectedCount): bool
    {
        $modified = false;
        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Class_) {
                foreach ($stmt->stmts as $method) {
                    if ($method instanceof Node\Stmt\ClassMethod
                        && ! $this->methodHasChaos($method)
                        && mt_rand() / mt_getrandmax() < $this->probability
                    ) {
                        $this->injectChaosIntoMethod($method);
                        $modified = true;
                        $injectedCount++;
                    }
                }
            }
        }

        return $modified;
    }

    private function saveModifiedFile(SplFileInfo $file, array $ast): void
    {
        $printer = new PrettyPrinter\Standard([
            'newline_at_end_of_file' => true,
        ]);

        $newCode = $printer->prettyPrintFile($ast);
        $newCode = preg_replace('/}\n\s*\n\s*(?=    (?:public|private|protected|\/\*\*|\}))/m', "}\n", $newCode);
        $newCode = preg_replace('/}\n(?=    (?:public|private|protected|\/\*\*|\}))/m', "}\n\n", $newCode);
        $newCode = rtrim($newCode)."\n";

        File::put($file->getPathname(), $newCode);
    }

    private function injectChaosIntoMethod(Node\Stmt\ClassMethod $method): void
    {
        if (! isset($method->stmts)) {
            $method->stmts = [];
        }

        $chaosCall = new Expression(
            new StaticCall(
                new FullyQualified('LaravelJutsu\\Bazooka\\Facades\\Bazooka'),
                'chaos'
            )
        );

        array_unshift(
            $method->stmts,
            $chaosCall,
            new Node\Stmt\Nop
        );
    }

    private function methodHasChaos(Node\Stmt\ClassMethod $method): bool
    {
        foreach ($method->stmts ?? [] as $stmt) {
            if ($stmt instanceof Expression
                && $stmt->expr instanceof StaticCall
                && $stmt->expr->class instanceof Node\Name
                && $stmt->expr->class->toString() === 'LaravelJutsu\\Bazooka\\Facades\\Bazooka'
                && $stmt->expr->name->toString() === 'chaos'
            ) {
                return true;
            }
        }

        return false;
    }
}
