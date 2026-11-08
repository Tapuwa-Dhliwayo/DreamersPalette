<?php

$loader = require dirname(__DIR__).'/vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

// Parse source and resolve imports without booting Laravel or touching application data.
$parser = (new ParserFactory)->createForHostVersion();
$errors = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($argv[1] ?? dirname(__DIR__).'/app'));
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    try {
        $nodes = $parser->parse(file_get_contents($path));
        $references = new class extends NodeVisitorAbstract
        {
            public array $names = [];

            public function enterNode(Node $node): null
            {
                $candidates = match (true) {
                    $node instanceof Node\Expr\New_, $node instanceof Node\Expr\StaticCall,
                    $node instanceof Node\Expr\StaticPropertyFetch, $node instanceof Node\Expr\ClassConstFetch,
                    $node instanceof Node\Expr\Instanceof_ => [$node->class],
                    $node instanceof Node\Stmt\Class_ => [$node->extends, ...$node->implements],
                    $node instanceof Node\Stmt\Interface_ => $node->extends,
                    $node instanceof Node\Stmt\TraitUse => $node->traits,
                    $node instanceof Node\Stmt\Catch_ => $node->types,
                    $node instanceof Node\Attribute => [$node->name],
                    $node instanceof Node\Param, $node instanceof Node\Stmt\Property => [$node->type],
                    $node instanceof Node\FunctionLike => [$node->getReturnType()],
                    default => [],
                };
                foreach ($candidates as $candidate) {
                    $this->collect($candidate);
                }

                return null;
            }

            private function collect(mixed $type): void
            {
                if ($type instanceof Node\Name && ! $type->isSpecialClassName()) {
                    $this->names[] = [$type->toString(), $type->getStartLine()];
                }
                if ($type instanceof Node\NullableType) {
                    $this->collect($type->type);
                }
                if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
                    foreach ($type->types as $part) {
                        $this->collect($part);
                    }
                }
            }
        };
        $traverser = new NodeTraverser(new NameResolver, $references);
        $traverser->traverse($nodes);
        foreach ($references->names as [$class, $line]) {
            $resolves = str_starts_with($class, 'App\\')
                ? $loader->findFile($class) !== false
                : class_exists($class) || interface_exists($class) || trait_exists($class);
            if (! $resolves) {
                $errors[] = "$path:$line — unresolved class $class";
            }
        }
    } catch (Throwable $exception) {
        $errors[] = "$path — {$exception->getMessage()}";
    }
}
foreach (array_unique($errors) as $error) {
    fwrite(STDERR, $error.PHP_EOL);
}
if ($errors) {
    exit(1);
}
echo 'PHP application class references resolve successfully.'.PHP_EOL;
