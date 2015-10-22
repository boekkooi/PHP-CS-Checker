<?php
namespace Boekkooi\CS\Iterator;

use Boekkooi\CS\Tokenizer\Token;

class ReportedTokenIterator extends \FilterIterator
{
    /**
     * @inheritdoc
     */
    public function accept()
    {
        return parent::current() instanceof Token;
    }

    public function current()
    {
        $token = parent::current();

        return $token->getMessages();
    }
}
