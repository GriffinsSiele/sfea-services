<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules(
        [
            '@Symfony' => true,
            '@Symfony:risky' => true,
            'array_syntax' => ['syntax' => 'short'],
            'no_unused_imports' => true,
            'no_unreachable_default_argument_value' => false,
            'braces' => ['allow_single_line_closure' => true],
            'heredoc_to_nowdoc' => false,
            'phpdoc_annotation_without_dot' => false,
            'void_return' => true,
            'return_type_declaration' => true,
            'cast_spaces' => ['space' => 'single'],
            'native_function_invocation' => [
                'exclude' => [],
                'include' => ['@internal'],
                'scope' => 'all',
                'strict' => true,
            ],
        ]
    )
    ->setRiskyAllowed(true);
