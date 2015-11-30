<?php
namespace Boekkooi\CS\Iterator;

use Boekkooi\CS\Tokenizer\Tokens;

class LineNumberIterator extends \IteratorIterator
{
    /**
     * @var int
     */
    private $lineNumber = 0;
    /**
     * @var int
     */
    private $columnNumber = 0;

    public function __construct(Tokens $iterator)
    {
        parent::__construct($iterator);
    }

    public function rewind()
    {
        parent::rewind();

        $this->lineNumber = 1;
        $this->columnNumber = 1;
    }

    public function key()
    {
        return $this->valid() ? $this->lineNumber.':'.$this->columnNumber : null;
    }

    public function next()
    {
        if ($this->valid()) {
            $token = $this->current();
            if (
                ($token->isGivenKind([T_WHITESPACE, T_OPEN_TAG, T_COMMENT, T_DOC_COMMENT])) &&
                ($n = strpos($token->getContent(), "\n")) !== false
            ) {
                $this->lineNumber += mb_substr_count($token->getContent(), "\n");
                $this->columnNumber = mb_strlen(substr($token->getContent(), $n + 1));
                if ($this->columnNumber === 0) {
                    $this->columnNumber = 1;
                }
            } else {
                $this->columnNumber += mb_strlen($token->getContent());
            }
        }

        parent::next();
    }
}
