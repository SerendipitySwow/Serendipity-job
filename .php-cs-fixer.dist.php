<?php

declare(strict_types=1);

$header = <<<'EOF'
This file is part of Swow-Cloud/Job
@license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
EOF;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@PSR12' => true,
        '@Symfony' => true,
        '@DoctrineAnnotation' => true,
        '@PhpCsFixer' => true,
        'header_comment' => [
            'comment_type' => 'PHPDoc',
            'header' => $header,
            'separate' => 'bottom',
            'location' => 'after_open',
        ],
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'list_syntax' => [
            'syntax' => 'short',
        ],
        'concat_space' => [
            'spacing' => 'one',
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'declare',
                'return',
            ],
        ],
        'blank_line_after_namespace' => true,
        'braces' => [
            'allow_single_line_closure' => true,
        ],
        'general_phpdoc_annotation_remove' => [
            'annotations' => [
                'author',
            ],
        ],
        'ordered_imports' => [
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'single_line_comment_style' => [
            'comment_types' => [
            ],
        ],
        'yoda_style' => [
            'always_move_variable' => false,
            'equal' => false,
            'identical' => false,
        ],
        'phpdoc_align' => [
            'align' => 'left',
        ],
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'constant_case' => [
            'case' => 'lower',
        ],
        'operator_linebreak' => [
            'only_booleans' => true,
            'position' => 'end',
        ],
        'class_attributes_separation' => true,
        'combine_consecutive_unsets' => true,
        'declare_strict_types' => true,
        'linebreak_after_opening_tag' => true,
        'lowercase_static_reference' => true,
        'no_useless_else' => true,
        'no_unused_imports' => true,
        'no_unneeded_curly_braces' => false,
        'not_operator_with_space' => false,
        'not_operator_with_successor_space' => false,
        'ordered_class_elements' => false,
        'php_unit_strict' => false,
        'phpdoc_separation' => false,
        'phpdoc_summary' => false,
        'single_quote' => true,
        'increment_style' => false,
        'standardize_increment' => false,
        'standardize_not_equals' => true,
        'multiline_comment_opening_closing' => true,
        // NOTE: this is for anonymous functions using WaitReference, maybe there's better solution
        'lambda_not_used_import' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
                         ->exclude('vendor')
                         ->exclude('storage')
                         ->exclude('runtimes')
                         ->exclude('xr')
                         ->exclude('throwable-handler')
                         ->exclude('trace')
                         ->in(__DIR__)
    )
    ->setUsingCache(false);
