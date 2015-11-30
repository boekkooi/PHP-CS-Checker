<?php
// Configure finder
$finder = new Symfony\Component\Finder\Finder();
$finder
    ->files()
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->in([ 'src/' ])
;

// Setup fixers and checkers
$config = Boekkooi\CS\Config::create()
    ->finder($finder)
    ->checkers([
        new Boekkooi\CS\Checker\Psr4Checker(
            array( 'src' => 'Boekkooi\CS' ),
            array( 'src/Resources/phar-stub.php' )
        )
    ])
    ->setRules([
        '@Symfony' => true,

        'blankline_after_open_tag' => false,
        'single_blank_line_before_namespace' => false,
        'empty_return' => false,
        'phpdoc_align' => false,
        'phpdoc_inline_tag' => false,

        'ordered_use' => true,
        'no_blank_lines_before_namespace' => true,
        'multiline_spaces_before_semicolon' => true
    ])
;

return $config;
