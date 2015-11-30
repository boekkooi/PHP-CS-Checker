<?php
namespace Boekkooi\CS\Checker\Contrib;

use Boekkooi\CS\AbstractChecker;
use Boekkooi\CS\Message;
use Boekkooi\CS\Tokenizer\Tokens;

class FinalClassChecker extends AbstractChecker
{
    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'All classes should be final except abstract ones.';
    }

    /**
     * @inheritdoc
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_CLASS);
    }

    /**
     * @inheritdoc
     */
    public function check(\SplFileInfo $file, Tokens $tokens)
    {
        $tokenCount = $tokens->count();
        for ($index = 0; $index < $tokenCount; ++$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_CLASS)) {
                continue;
            }

            $prevToken = $tokens[$tokens->getPrevMeaningfulToken($index)];

            $classStart = $tokens->getNextTokenOfKind($index, array('{'));
            $classEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classStart);

            // ignore class if it is abstract or already final
            if ($prevToken->isGivenKind(array(T_ABSTRACT, T_FINAL))) {
                $index = $classEnd;
                continue;
            }

            $classNameIndex = $tokens->getNextTokenOfKind($index, [[T_STRING]]);
            $className = $tokens[$classNameIndex]->getContent();

            $tokens->reportAt(
                $index,
                new Message(
                    E_ERROR,
                    'check_class_should_be_final',
                    [
                        'class' => $className,
                    ]
                )
            );

            $index = $classEnd;
        }
    }
}
