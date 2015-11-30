<?php
namespace Boekkooi\CS;

use Boekkooi\CS\Iterator\LineNumberIterator;
use Boekkooi\CS\Iterator\ReportedTokenIterator;
use Boekkooi\CS\Tokenizer\Tokens;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;
use Symfony\CS\ConfigInterface as CSConfigInterface;
use Symfony\CS\Error\Error;
use Symfony\CS\FileCacheManager;
use Symfony\CS\FixerFileProcessedEvent;
use Symfony\CS\Linter\LintingException;

/**
 * This is mostly a copy of Fixed.
 *
 * @see \Symfony\CS\Fixer
 */
class Fixer extends \Symfony\CS\Fixer
{
    const VERSION = '0.1-DEV';

    /**
     * Fixes all files for the given finder.
     *
     * @param \Symfony\CS\ConfigInterface $config A ConfigInterface instance
     * @param bool            $dryRun Whether to simulate the changes or not
     * @param bool            $diff   Whether to provide diff
     *
     * @return array
     */
    public function fix(CSConfigInterface $config, $dryRun = false, $diff = false)
    {
        $changed = array();
        $fixers = $config->getFixers();

        $this->stopwatch->openSection();

        $fileCacheManager = new FileCacheManager(
            $config->usingCache(),
            $config->getCacheFile(),
            $config->getRules()
        );

        $checkers = [];
        $messageCacheManager = null;
        if ($config instanceof ConfigInterface) {
            $checkers = $config->getCheckers();
            $messageCacheManager = new FileMessageCacheManager(
                $config->usingCache(),
                $config->getCheckerCacheFile(),
                array_map('get_class', $config->getCheckers())
            );
        }

        /** @var \SplFileInfo $file */
        foreach ($config->getFinder() as $file) {
            if ($file->isDir() || $file->isLink()) {
                continue;
            }

            $this->stopwatch->start($this->getFileRelativePathname($file));

            $relativeFile = $this->getFileRelativePathname($file);
            if ($fixInfo = $this->fixFile($file, $fixers, $dryRun, $diff, $fileCacheManager, $checkers, $messageCacheManager)) {
                $changed[$relativeFile] = $fixInfo;
            } elseif ($messageCacheManager->hasMessage($this->getFileRelativePathname($file))) {
                $changed[$relativeFile] = array(
                    'checkMessages' => $messageCacheManager->getMessage($relativeFile),
                );
            }

            $this->stopwatch->stop($this->getFileRelativePathname($file));
        }

        $this->stopwatch->stopSection('fixFile');

        return $changed;
    }

    /**
     * @param \SplFileInfo $file
     * @param \Symfony\CS\FixerInterface[] $fixers
     * @param CheckerInterface[] $checkers
     * @param bool $dryRun
     * @param bool $diff
     * @param FileCacheManager $fileCacheManager
     * @param FileMessageCacheManager $messageCacheManager
     *
     * @return array|null|void
     */
    public function fixFile(\SplFileInfo $file, array $fixers, $dryRun, $diff, FileCacheManager $fileCacheManager, array $checkers = array(), FileMessageCacheManager $messageCacheManager = null)
    {
        $new = $old = file_get_contents($file->getRealpath());

        if (
            '' === $old
            || !$fileCacheManager->needFixing($this->getFileRelativePathname($file), $old)
            // PHP 5.3 has a broken implementation of token_get_all when the file uses __halt_compiler() starting in 5.3.6
            || (PHP_VERSION_ID >= 50306 && PHP_VERSION_ID < 50400 && false !== stripos($old, '__halt_compiler()'))
        ) {
            $this->dispatchEvent(
                FixerFileProcessedEvent::NAME,
                FixerFileProcessedEvent::create()->setStatus(FixerFileProcessedEvent::STATUS_SKIPPED)
            );

            return false;
        }

        try {
            $this->linter->lintFile($file->getRealpath());
        } catch (LintingException $e) {
            $this->dispatchEvent(
                FixerFileProcessedEvent::NAME,
                FixerFileProcessedEvent::create()->setStatus(FixerFileProcessedEvent::STATUS_INVALID)
            );

            $this->errorsManager->report(new Error(
                Error::TYPE_INVALID,
                $this->getFileRelativePathname($file)
            ));

            return false;
        }

        $old = file_get_contents($file->getRealpath());

        // we do not need Tokens to still caching previously fixed file - so clear the cache
        Tokens::clearCache();

        $tokens = Tokens::fromCode($old);
        $newHash = $oldHash = $tokens->getCodeHash();

        $checkMessages = [];

        try {
            if ($dryRun) {
                $checkMessages = $this->runCheckers($file, $checkers, $tokens);
            }

            $appliedFixers = $this->runFixers($file, $fixers, $tokens);

            if (!$dryRun) {
                $checkMessages = $this->runCheckers($file, $checkers, $tokens);
            }
        } catch (\Exception $e) {
            $this->dispatchEvent(
                FixerFileProcessedEvent::NAME,
                FixerFileProcessedEvent::create()->setStatus(FixerFileProcessedEvent::STATUS_EXCEPTION)
            );

            $this->errorsManager->report(new Error(
                Error::TYPE_EXCEPTION,
                $this->getFileRelativePathname($file)
            ));

            return false;
        }

        $fixInfo = null;

        if (!empty($appliedFixers)) {
            $new = $tokens->generateCode();
            $newHash = $tokens->getCodeHash();
        }

        // We need to check if content was changed and then applied changes.
        // But we can't simple check $appliedFixers, because one fixer may revert
        // work of other and both of them will mark collection as changed.
        // Therefore we need to check if code hashes changed.
        if ($oldHash !== $newHash) {
            try {
                $this->linter->lintSource($new);
            } catch (LintingException $e) {
                $this->dispatchEvent(
                    FixerFileProcessedEvent::NAME,
                    FixerFileProcessedEvent::create()->setStatus(FixerFileProcessedEvent::STATUS_LINT)
                );

                $this->errorsManager->report(new Error(
                    Error::TYPE_LINT,
                    $this->getFileRelativePathname($file)
                ));

                return false;
            }

            if (!$dryRun && false === @file_put_contents($file->getRealpath(), $new)) {
                $error = error_get_last();
                if ($error) {
                    throw new IOException(sprintf('Failed to write file "%s", "%s".', $file->getRealpath(), $error['message']), 0, null, $file->getRealpath());
                }
                throw new IOException(sprintf('Failed to write file "%s".', $file->getRealpath()), 0, null, $file->getRealpath());
            }

            $fixInfo = array(
                'appliedFixers' => $appliedFixers,
                'checkMessages' => $checkMessages,
            );
            if ($diff) {
                $fixInfo['diff'] = $this->stringDiff($old, $new);
            }
        } elseif (!empty($checkMessages)) {
            $fixInfo = array(
                'checkMessages' => $checkMessages,
            );
        }

        $fileCacheManager->setFile($this->getFileRelativePathname($file), $new);
        if ($messageCacheManager !== null) {
            $messageCacheManager->setMessages(
                $this->getFileRelativePathname($file),
                isset($fixInfo['checkMessages']) ? $fixInfo['checkMessages'] : []
            );
        }

        $this->dispatchEvent(
            FixerFileProcessedEvent::NAME,
            FixerFileProcessedEvent::create()->setStatus($fixInfo ? FixerFileProcessedEvent::STATUS_FIXED : FixerFileProcessedEvent::STATUS_NO_CHANGES)
        );

        return $fixInfo;
    }

    private function getFileRelativePathname(\SplFileInfo $file)
    {
        if ($file instanceof FinderSplFileInfo) {
            return $file->getRelativePathname();
        }

        return $file->getPathname();
    }

    /**
     * Dispatch event.
     *
     * @param string $name
     * @param Event  $event
     */
    private function dispatchEvent($name, Event $event)
    {
        if (null === $this->eventDispatcher) {
            return;
        }

        $this->eventDispatcher->dispatch($name, $event);
    }

    /**
     * @param \SplFileInfo $file
     * @param CheckerInterface[] $checkers
     * @param Tokens $tokens
     *
     * @return array
     */
    protected function runCheckers(\SplFileInfo $file, array $checkers, Tokens $tokens)
    {
        foreach ($checkers as $checker) {
            if (!$checker->supports($file) || !$checker->isCandidate($tokens)) {
                continue;
            }

            $checker->check($file, $tokens);
        }

        $lineMessages = iterator_to_array(
            new ReportedTokenIterator(
                new LineNumberIterator($tokens)
            )
        );

        if ($tokens->isChanged()) {
            $tokens->clearEmptyTokens();
            $tokens->clearChanged();
        }

        return $lineMessages;
    }

    /**
     * @param \SplFileInfo $file
     * @param \Symfony\CS\FixerInterface[] $fixers
     * @param Tokens $tokens
     *
     * @return array
     */
    protected function runFixers(\SplFileInfo $file, array $fixers, $tokens)
    {
        $appliedFixers = array();

        foreach ($fixers as $fixer) {
            if (!$fixer->supports($file) || !$fixer->isCandidate($tokens)) {
                continue;
            }

            $fixer->fix($file, $tokens);

            if ($tokens->isChanged()) {
                $tokens->clearEmptyTokens();
                $tokens->clearChanged();
                $appliedFixers[] = $fixer->getName();
            }
        }

        return $appliedFixers;
    }
}
