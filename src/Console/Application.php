<?php
namespace Boekkooi\CS\Console;

use Boekkooi\CS\Console\Command\FixCommand;
use Boekkooi\CS\Fixer;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\CS\Console\Command\ReadmeCommand;

class Application extends BaseApplication
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        error_reporting(-1);

        parent::__construct('PHP CS Fixer & Checker', Fixer::VERSION);

        $this->add(new FixCommand());
        $this->add(new ReadmeCommand());
    }

    public function getLongVersion()
    {
        $version = parent::getLongVersion();
        $commit = '@git-commit@';

        if ('@'.'git-commit@' !== $commit) {
            $version .= ' ('.substr($commit, 0, 7).')';
        }

        return $version;
    }
}
