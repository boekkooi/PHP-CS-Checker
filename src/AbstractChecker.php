<?php
namespace Boekkooi\CS;

use Symfony\CS\Utils;

abstract class AbstractChecker implements CheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        $nameParts = explode('\\', get_called_class());
        $name = substr(end($nameParts), 0, -strlen('Checker'));

        return Utils::camelCaseToUnderscore($name);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(\SplFileInfo $file)
    {
        return true;
    }
}
