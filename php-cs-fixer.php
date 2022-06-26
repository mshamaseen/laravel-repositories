<?php

$finder = PhpCsFixer\Finder::create()
    // This will be overridden
    ->path('.php-cs-fixer.php')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@Symfony' => true,
    'phpdoc_no_alias_tag' => false,
    'phpdoc_to_comment' => ['ignored_tags' => ['todo', 'var']],
])
    ->setFinder($finder);
