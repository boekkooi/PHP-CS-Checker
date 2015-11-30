<?php
namespace Boekkooi\CS\Console\Output;

use Boekkooi\CS\Message;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class FixResultTxtOutputHelper
{
    private $verbosity;
    private $showDiff;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(OutputInterface $output, TranslatorInterface $translator, $verbosity, $showDiff)
    {
        $this->output = $output;
        $this->translator = $translator;
        $this->verbosity = $verbosity;
        $this->showDiff = $showDiff;
    }

    public function write(array $changes, Stopwatch $stopwatch)
    {
        $output = $this->output;

        $i = 1;
        foreach ($changes as $file => $fixResult) {
            $output->write(sprintf('%4d) %s', $i++, $file));

            if (isset($fixResult['appliedFixers'])) {
                if (OutputInterface::VERBOSITY_VERBOSE <= $this->verbosity) {
                    $output->write(sprintf(' (<comment>%s</comment>)', implode(', ', $fixResult['appliedFixers'])));
                }
            }

            $output->writeln('');

            if (isset($fixResult['checkMessages'])) {
                $this->outputFileMessages($output, $fixResult['checkMessages']);
            }

            if (isset($fixResult['diff'])) {
                $this->outputFileDiff($output, $fixResult['diff']);
            }
        }

        $this->outputPerformance($stopwatch, $output);
    }

    /**
     * @param Stopwatch $stopwatch
     * @param OutputInterface $output
     */
    protected function outputPerformance(Stopwatch $stopwatch, OutputInterface $output)
    {
        if (OutputInterface::VERBOSITY_DEBUG <= $this->verbosity) {
            $output->writeln('Fixing time per file:');

            foreach ($stopwatch->getSectionEvents('fixFile') as $file => $event) {
                if ('__section__' === $file) {
                    continue;
                }

                $output->writeln(sprintf('[%.3f s] %s', $event->getDuration() / 1000, $file));
            }

            $output->writeln('');
        }

        $fixEvent = $stopwatch->getEvent('fixFiles');
        $output->writeln(sprintf('Fixed all files in %.3f seconds, %.3f MB memory used', $fixEvent->getDuration() / 1000, $fixEvent->getMemory() / 1024 / 1024));
    }

    /**
     * @param OutputInterface $output
     * @param string $fixResult
     */
    protected function outputFileDiff(OutputInterface $output, $diff)
    {
        if ($this->showDiff) {
            $output->writeln('');
            $output->writeln('<comment>      ---------- begin diff ----------</comment>');
            $output->writeln($diff);
            $output->writeln('<comment>      ---------- end diff ----------</comment>');
        }
    }

    /**
     * @param OutputInterface $output
     * @param array $fileMessages
     */
    protected function outputFileMessages(OutputInterface $output, array $fileMessages)
    {
        foreach ($fileMessages as $k => $messages) {
            list($line, $col) = explode(':', $k);
            $messages = array_map(array($this, 'formatMessage'), $messages);

            $output->write('      - ');
            if (count($messages) === 1) {
                $output->writeln(implode($messages).sprintf(' (line %d column %d)', $line, $col));
            } else {
                $output->writeln(sprintf('Check failed on line %d column %d', $line, $col));
                $output->writeln(str_repeat(' ', 8).implode("\n".str_repeat(' ', 8), $messages));
            }
        }
    }

    protected function formatMessage(Message $message)
    {
        $parameters = [];
        foreach ($message->getParameters() as $key => $value) {
            $parameters['%'.$key.'%'] = $value;
        }

        return $this->translator->trans($message->getId(), $parameters);
    }
}
