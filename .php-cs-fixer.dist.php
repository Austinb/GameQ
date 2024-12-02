<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,

        /* Imports / use */
        'no_unused_imports' => true,
        'no_unneeded_import_alias' => true,
        'no_leading_import_slash' => true,
        'ordered_imports' => true,

        /* Comments */
        'single_line_comment_style' => true,

        /* Blank lines */
        'no_extra_blank_lines' => [
            'tokens' => ['curly_brace_block']
        ]
    ])
    ->setFinder($finder)
;