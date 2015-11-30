<?php
namespace Boekkooi\CS;

interface ConfigInterface extends \Symfony\CS\ConfigInterface
{
    /**
     * Returns the checkers to run.
     *
     * @return CheckerInterface[]
     */
    public function getCheckers();

    /**
     * Returns the path to the checker cache file.
     *
     * @return string
     */
    public function getCheckerCacheFile();
}
