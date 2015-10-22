<?php
namespace Tests\Boekkooi\CS\Checker;

use Boekkooi\CS\CheckerInterface;
use Boekkooi\CS\Iterator\LineNumberIterator;
use Boekkooi\CS\Iterator\ReportedTokenIterator;
use Boekkooi\CS\Tokenizer\Tokens;

abstract class AbstractCheckerTestCase extends \PHPUnit_Framework_TestCase
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

    protected function getTestFile($filename = __FILE__)
    {
        static $files = array();

        if (!isset($files[$filename])) {
            $files[$filename] = new \SplFileInfo($filename);
        }

        return $files[$filename];
    }

    protected function makeTest(array $expectedMessages, $input, \SplFileInfo $file = null, CheckerInterface $checker = null)
    {
        $checker = $checker ?: $this->getChecker();
        $file = $file ?: $this->getTestFile();
        $fileIsSupported = $checker->supports($file);

        Tokens::clearCache();
        $tokens = Tokens::fromCode($input);

        $checkResult = null;

        if ($fileIsSupported) {
            self::assertTrue($checker->isCandidate($tokens), 'Fixer must be a candidate for input code.');
            $checker->check($file, $tokens);
        }

        $iterator = new ReportedTokenIterator(new LineNumberIterator($tokens));
        $lineMessages = [];
        foreach ($iterator as $k => $messages) {
            $lineMessages[$k] = array_map(function($message) {
                return $message->toArray();
            }, $messages);
        }

        self::assertEquals($expectedMessages, $lineMessages);
    }
}
