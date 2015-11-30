<?php
namespace Boekkooi\CS\Tests;

use Boekkooi\CS\CheckerInterface;
use Boekkooi\CS\Iterator\LineNumberIterator;
use Boekkooi\CS\Iterator\ReportedTokenIterator;
use Boekkooi\CS\Message;
use Boekkooi\CS\Tokenizer\Tokens;

abstract class AbstractCheckerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Get the check that is being tested.
     *
     * @return CheckerInterface
     */
    abstract protected function getChecker();

    /**
     * Get the SplFileInfo for a given filename.
     *
     * @param string $filename
     *
     * @return \SplFileInfo
     */
    protected function getTestFile($filename = __FILE__)
    {
        static $files = array();

        if (!isset($files[$filename])) {
            $files[$filename] = new \SplFileInfo($filename);
        }

        return $files[$filename];
    }

    /**
     * @param array $expectedMessages The expected list of messages
     * @param string $input The input that is tokenized
     * @param \SplFileInfo|null $file
     * @param CheckerInterface|null $checker
     */
    protected function makeTest(array $expectedMessages, $input, \SplFileInfo $file = null, CheckerInterface $checker = null)
    {
        $checker = $checker ?: $this->getChecker();
        $file = $file ?: $this->getTestFile();
        $fileIsSupported = $checker->supports($file);

        Tokens::clearCache();
        $tokens = Tokens::fromCode($input);

        if ($fileIsSupported) {
            self::assertTrue($checker->isCandidate($tokens), 'Fixer must be a candidate for input code.');
            $checker->check($file, $tokens);
        }

        $iterator = new ReportedTokenIterator(new LineNumberIterator($tokens));
        $lineMessages = [];
        foreach ($iterator as $k => $messages) {
            $lineMessages[$k] = array_map(function (Message $message) {
                return $message->toArray();
            }, $messages);
        }

        self::assertEquals($expectedMessages, $lineMessages);
    }
}
