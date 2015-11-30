<?php
namespace Boekkooi\CS\Tokenizer;

use Boekkooi\CS\Message;
use Symfony\CS\Tokenizer\Token as BaseToken;

class Token extends BaseToken
{
    private $messages = array();

    public static function fromBaseToken(BaseToken $token)
    {
        $extended = new self($token->getPrototype());

        if ($token->isChanged()) {
            $extended->setContent('__CHANGE_HOLDER__'.$token->getContent());
            $extended->setContent($token->getContent());
        }

        return $extended;
    }

    public function report(Message $message)
    {
        $this->messages[] = $message;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function override($other)
    {
        $prototype = $other instanceof self ? $other->getPrototype() : $other;

        if (!$this->equals($prototype)) {
            $this->messages = array();
        }

        parent::override($other);
    }

    public function clear()
    {
        $this->messages = array();
        parent::clear();
    }

    public function setContent($content)
    {
        $currentContent = $this->getContent();

        parent::setContent($content);

        if ($currentContent !== $content) {
            $this->messages = array();
        }
    }
}
