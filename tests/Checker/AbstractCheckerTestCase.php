<?php
namespace Tests\Boekkooi\CS\Checker;

use Boekkooi\CS\Tests\AbstractCheckerTestCase as TestCase;

abstract class AbstractCheckerTestCase extends TestCase
{
    protected function getChecker()
    {
        $name = 'Boekkooi\CS\Checker'.substr(get_called_class(), strlen(__NAMESPACE__), -strlen('Test'));

        /** @var \Boekkooi\CS\CheckerInterface $checker */
        $checker = new $name();
        $checker->configure($this->getCheckerConfiguration());

        return $checker;
    }

    protected function getCheckerConfiguration()
    {
        return null;
    }
}
