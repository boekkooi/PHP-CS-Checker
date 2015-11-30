<?php
namespace Boekkooi\CS\Checker;

use Boekkooi\CS\AbstractChecker;
use Boekkooi\CS\Message;
use Boekkooi\CS\Tokenizer\Tokens;

class Psr4Checker extends AbstractChecker
{
    /**
     * @inheritdoc
     */
    public function isCandidate(Tokens $tokens)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'Check that a file contains valid PSR-4 content (http://www.php-fig.org/psr/psr-4/)';
    }

    /**
     * @inheritdoc
     */
    public function check(\SplFileInfo $file, Tokens $tokens)
    {
        $namespaces = [];
        $classes = [];

        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $namespaceIndex = $tokens->getNextNonWhitespace($index);
                $namespaceEndIndex = $tokens->getNextTokenOfKind($index, array(';'));

                $namespaces[$index] = trim($tokens->generatePartialCode($namespaceIndex, $namespaceEndIndex - 1));
            } elseif ($token->isClassy()) {
                $classyIndex = $tokens->getNextNonWhitespace($index);
                $classes[$classyIndex] = $tokens[$classyIndex]->getContent();
            }
        }

        list($namespace, $namespaceIndex) = $this->resolveNamespace($tokens, $namespaces);
        list($className, $classNameIndex) = $this->resolveClassName($tokens, $classes);

        // TODO check namespace directory

        // File basename must match class name
        $fileBasename = $file->getBasename('.'.$file->getExtension());
        if ($className !== null && $fileBasename !== $className) {
            $tokens->reportAt(
                $classNameIndex,
                new Message(
                    E_ERROR,
                    'check_psr4_filename_must_match_classname',
                    [
                        'expected' => $fileBasename,
                        'class' => $className,
                    ]
                )
            );
        }
    }

    protected function resolveNamespace(Tokens $tokens, array $namespaces)
    {
        $namespace = null;

        // A namespace is required
        if (count($namespaces) === 0) {
            $tokens->reportAt(
                0,
                new Message(
                    E_ERROR,
                    'check_psr4_no_namespace_declaration'
                )
            );

            return null;
        }

        reset($namespaces);
        $namespaceIndex = key($namespaces);
        $namespace = array_shift($namespaces);

        // Only a single namespace per file
        if (count($namespaces) >= 1) {
            foreach ($namespaces as $index => $ns) {
                $tokens->reportAt(
                    $index,
                    new Message(
                        E_ERROR,
                        'check_psr4_multiple_namespace_declarations',
                        [
                            'namespace' => $namespace,
                            'additional' => $ns,
                        ]
                    )
                );
            }
        }

        // Namespace must have a vendor part
        if ($namespace === '') {
            $tokens->reportAt(
                $namespaceIndex,
                new Message(
                    E_ERROR,
                    'check_psr4_namespace_must_have_vendor_namespace'
                )
            );
        }

        return [$namespace, $namespaceIndex];
    }

    private function resolveClassName(Tokens $tokens, $classes)
    {
        if (count($classes) === 0) {
            $tokens->reportAt(
                0,
                new Message(
                    E_ERROR,
                    'check_psr4_must_have_a_class'
                )
            );

            return null;
        }

        reset($classes);
        $classNameIndex = key($classes);
        $className = array_shift($classes);

        // Only a single class is allowed
        if (count($classes) >= 1) {
            foreach ($classes as $index => $cls) {
                $tokens->reportAt(
                    $index,
                    new Message(
                        E_ERROR,
                        'check_psr4_multiple_classes',
                        [
                            'class' => $className,
                            'additional' => $cls,
                        ]
                    )
                );
            }
        }

        return [$className, $classNameIndex];
    }
}
