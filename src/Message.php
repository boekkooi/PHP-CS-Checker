<?php
namespace Boekkooi\CS;

class Message
{
    private $severity;
    private $id;
    private $parameters;

    /**
     * Constructor.
     *
     * @param int $severity The severity of the message (E_ERROR, etc.)
     * @param string $id Message id
     * @param array $parameters A array of message parameters
     */
    public function __construct($severity, $id, array $parameters = array())
    {
        $this->severity = $severity;
        $this->id = $id;
        $this->parameters = $parameters;
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    public function toArray()
    {
        return [
            $this->severity,
            $this->id,
            $this->parameters
        ];
    }
}
