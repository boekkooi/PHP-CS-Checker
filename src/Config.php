<?php
namespace Boekkooi\CS;

class Config extends \Symfony\CS\Config\Config implements ConfigInterface
{
    protected $checkers = [];

    /**
     * Set fixers.
     *
     * @param CheckerInterface[] $checkers
     *
     * @return $this
     */
    public function checkers(array $checkers)
    {
        $this->checkers = $checkers;

        return $this;
    }

    /**
     * @return CheckerInterface[]
     */
    public function getCheckers()
    {
        return $this->checkers;
    }
}
