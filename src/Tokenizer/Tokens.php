<?php
namespace Boekkooi\CS\Tokenizer;

use Boekkooi\CS\Message;
use Symfony\CS\Tokenizer\Tokens as BaseTokens;

class Tokens extends BaseTokens
{
    public function reportAt($index, Message $message)
    {
        if (!$this[$index] instanceof Token) {
            $this[$index] = Token::fromBaseToken($this[$index]);
        }

        $this[$index]->report($message);
    }

    /**
     * Create token collection from array.
     *
     * @param array $array       the array to import
     * @param bool  $saveIndexes save the numeric indexes used in the original array, default is yes
     *
     * @return Tokens
     */
    public static function fromArray($array, $saveIndexes = null)
    {
        /** @var Tokens $tokens */
        $tokens = new static(count($array));

        if (null === $saveIndexes || $saveIndexes) {
            foreach ($array as $key => $val) {
                $tokens[$key] = $val;
            }

            return $tokens;
        }

        $index = 0;

        foreach ($array as $val) {
            $tokens[$index++] = $val;
        }

        return $tokens;
    }

    /**
     * Create token collection directly from code.
     *
     * @param string $code PHP code
     *
     * @return Tokens
     */
    public static function fromCode($code)
    {
        $calculateCodeHashRefl = new \ReflectionMethod(BaseTokens::class, 'calculateCodeHash');
        $calculateCodeHashRefl->setAccessible(true);
        $hasCacheRefl = new \ReflectionMethod(BaseTokens::class, 'hasCache');
        $hasCacheRefl->setAccessible(true);
        $getCacheRefl = new \ReflectionMethod(BaseTokens::class, 'getCache');
        $getCacheRefl->setAccessible(true);

        $codeHash = $calculateCodeHashRefl->invoke(null, $code);

        if ($hasCacheRefl->invoke(null, $codeHash)) {
            /** @var Tokens $tokens */
            $tokens = $getCacheRefl->invoke(null, $codeHash);

            // generate the code to recalculate the hash
            $tokens->generateCode();

            if ($codeHash === $tokens->getCodeHash()) {
                $tokens->clearEmptyTokens();
                $tokens->clearChanged();

                return $tokens;
            }
        }

        $tokens = new static();
        $tokens->setCode($code);
        $tokens->clearChanged();

        return $tokens;
    }
}
