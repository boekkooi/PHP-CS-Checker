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
}
