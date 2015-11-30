<?php
namespace Boekkooi\CS\Checker\Contrib;

use Boekkooi\CS\AbstractChecker;
use Boekkooi\CS\Message;
use Boekkooi\CS\Tokenizer\Tokens;
use Symfony\CS\Tokenizer\Token;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
final class NoMethodWithArgumentsChecker extends AbstractChecker
{
    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'Check that there are not methods in a class that accept arguments';
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound(Token::getClassyTokenKinds()) && $tokens->isTokenKindFound(T_FUNCTION);
    }

    /**
     * Checks a file.
     *
     * @param \SplFileInfo $file A \SplFileInfo instance
     * @param Tokens $tokens Tokens collection
     */
    public function check(\SplFileInfo $file, Tokens $tokens)
    {
        $classes = array_keys($tokens->findGivenKind(T_CLASS));
        $numClasses = count($classes);

        for ($i = 0; $i < $numClasses; ++$i) {
            $index = $classes[$i];

            // figure out where the classy starts
            $classStart = $tokens->getNextTokenOfKind($index, array('{'));

            // figure out where the classy ends
            $classEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classStart);

            $this->checkMethods($tokens, $classStart, $classEnd);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $classStart
     * @param int    $classEnd
     */
    private function checkMethods(Tokens $tokens, $classStart, $classEnd)
    {
        for ($index = $classStart; $index < $classEnd; ++$index) {
            $token = $tokens[$index];
            if (!$token->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $nameIndex = $tokens->getNextTokenOfKind($index, [[T_STRING]]);
            $name = $tokens[$nameIndex]->getContent();

            $this->checkMethodSignature($tokens, $name, $index);

            // Skip method body
            $startBraceIndex = $tokens->getNextTokenOfKind($index, array(';', '{'));
            $startBraceToken = $tokens[$startBraceIndex];

            if ($startBraceToken->equals('{')) {
                $endBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startBraceIndex);

                $this->checkMethodBody($tokens, $name, $startBraceIndex, $endBraceIndex);

                $index = $endBraceIndex;
            } else {
                $index = $startBraceIndex;
            }
        }
    }

    private function checkMethodSignature(Tokens $tokens, $methodName, $methodFunctionIndex)
    {
        $startParenthesisIndex = $tokens->getNextTokenOfKind($methodFunctionIndex, array('('));
        $endParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startParenthesisIndex);

        for ($index = $startParenthesisIndex; $index < $endParenthesisIndex; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_VARIABLE)) {
                continue;
            }

            $tokens->reportAt(
                $index,
                new Message(
                    E_ERROR,
                    'check_method_arguments_not_allowed',
                    ['method' => $methodName]
                )
            );
            break;
        }
    }

    private function checkMethodBody(Tokens $tokens, $methodName, $methodStart, $methodEnd)
    {
        for ($index = $methodStart; $index < $methodEnd; ++$index) {
            if (
                !$tokens[$index]->isGivenKind(T_STRING) ||
                !in_array($tokens[$index]->getContent(), ['func_get_args', 'func_get_arg', 'func_num_args'], true)
            ) {
                continue;
            }

            $tokens->reportAt(
                $index,
                new Message(
                    E_ERROR,
                    'check_method_arguments_called_not_allowed',
                    [
                        'method' => $methodName,
                        'function' => $tokens[$index]->getContent(),
                    ]
                )
            );
            break;
        }
    }
}
