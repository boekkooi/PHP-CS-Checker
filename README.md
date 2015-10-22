# PHP Coding Standards Fixer & Checker

*This project is currently just a proof of concept*

This is a extension of the [PHP Coding Standards Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) project.
It adds a set of checks to the fixer because some times the fixer can't fix a problem but you do want it to report that problem.

The main reason for adding this extension and not using [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) is because I can't handle writing custom checks for it.

## Example .php_cs
```PHP
<?php
return \Boekkooi\CS\Config::create()
    ->setDir(__DIR__.'/src')
    ->checkers(array(
//        new \Boekkooi\CS\Checker\Contrib\FinalClassChecker(),
//        new \Boekkooi\CS\Checker\Contrib\NoMethodWithArgumentsChecker(),
        new \Boekkooi\CS\Checker\Psr4Checker(),
    ))
    ->setRules(array(
        '@PSR2' => true
    ))
;
```
