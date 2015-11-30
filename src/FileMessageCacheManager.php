<?php
namespace Boekkooi\CS;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\CS\ToolInfo;

class FileMessageCacheManager
{
    private $cacheFile;
    private $isEnabled;
    private $checkers;
    private $messages = array();

    /**
     * Create instance.
     *
     * @param bool   $isEnabled is cache enabled
     * @param string $cacheFile cache file
     * @param array  $checkers array defining checker
     */
    public function __construct($isEnabled, $cacheFile, array $checkers)
    {
        $this->isEnabled = $isEnabled;
        $this->cacheFile = $cacheFile;
        $this->checkers = $checkers;

        $this->readFromFile();
    }

    public function __destruct()
    {
        $this->saveToFile();
    }

    /**
     * @param $file
     *
     * @return bool
     */
    public function hasMessage($file)
    {
        return isset($this->messages[$file]);
    }

    /**
     * @param $file
     *
     * @return array
     */
    public function getMessage($file)
    {
        return $this->messages[$file];
    }

    public function setMessages($file, array $messages)
    {
        if (empty($messages)) {
            unset($this->messages[$file]);
        } else {
            $this->messages[$file] = $messages;
        }
    }

    private function isCacheAvailable()
    {
        static $result;

        if (null === $result) {
            $result = $this->isEnabled && (ToolInfo::isInstalledAsPhar() || ToolInfo::isInstalledByComposer());
        }

        return $result;
    }

    private function readFromFile()
    {
        if (!$this->isCacheAvailable()) {
            return;
        }

        if (!file_exists($this->cacheFile)) {
            return;
        }

        $content = file_get_contents($this->cacheFile);
        $data = unserialize($content);

        if (!isset($data['version'], $data['checkers'])) {
            return;
        }

        $this->messages = $data['messages'];
    }

    private function saveToFile()
    {
        if (!$this->isCacheAvailable()) {
            return;
        }

        $data = serialize(
            array(
                'version' => ToolInfo::getVersion(),
                'checkers' => $this->checkers,
                'messages' => $this->messages,
            )
        );

        if (false === @file_put_contents($this->cacheFile, $data, LOCK_EX)) {
            throw new IOException(sprintf('Failed to write file "%s".', $this->cacheFile), 0, null, $this->cacheFile);
        }
    }
}
