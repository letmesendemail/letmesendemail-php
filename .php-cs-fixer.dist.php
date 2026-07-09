<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('tests/Fixtures');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'single_quote' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder);
