<?php
namespace Boekkooi\CS;

use Boekkooi\CS\Tokenizer\Tokens;

interface CheckerInterface
{
    /**
     * Set configuration.
     *
     * Some checkers may have no configuration, then - simply pass null.
     * Other ones may have configuration that will change behavior of checker.
     * Finally, some checkers need configuration to work.
     *
     * @param array|null $configuration configuration depends on Checker
     */
    public function configure(array $configuration = null);

    /**
     * Check if the checker is a candidate for given Tokens collection.
     *
     * Checker is a candidate when the collection contains tokens that may be checked.
     * This could be considered as some kind of bloom filter.
     * When this method returns true then to the Tokens collection may or may not
     * need a check, but when this method returns false then the Tokens collection
     * need no checking for sure.
     *
     * @param Tokens $tokens
     *
     * @return bool
     */
    public function isCandidate(Tokens $tokens);

    /**
     * Checks a file.
     *
     * @param \SplFileInfo $file   A \SplFileInfo instance
     * @param Tokens       $tokens Tokens collection
     */
    public function check(\SplFileInfo $file, Tokens $tokens);

    /**
     * Returns the description of the checker.
     *
     * A short one-line description of what the checker does.
     *
     * @return string The description of the checker
     */
    public function getDescription();

    /**
     * Returns the name of the checker.
     *
     * The name must be all lowercase and without any spaces.
     *
     * @return string The name of the checker
     */
    public function getName();

    /**
     * Returns true if the file is supported by this checker.
     *
     * @return bool true if the file is supported by this checker, false otherwise
     */
    public function supports(\SplFileInfo $file);
}
