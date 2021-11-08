<?php

namespace WBCUpdater;

use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use throwable;
use WBCUpdater\Commands\CommandInterface;
use WBCUpdater\Commands\FullPatch;

final class PatchCommand extends Command
{
    const NAME = 'patch';
    const DESCRIPTION = 'command using to patch your WBC game';
    /** @var Config */
    private Config $config;
    /** @var Logger */
    private Logger $logger;
    /** @var CommandInterface */
    private CommandInterface $command;

    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct(self::NAME);
        $this->setDescription(self::DESCRIPTION);
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws throwable
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var $helper QuestionHelper */
            $helper = $this->getHelper('question');
            $game = new Game($this->config['game_directory']);

            while (!$game->checkDirectory()) {
                $output->writeln("Cannot find game in {$game->getDirectory()}");
                $question = new Question('Type correct directory in command line:');
                $answer = $helper->ask($input, $output, $question);
                $game->changeDirectory($answer);
            }
            //update config
            $this->config['game_directory'] = $game->getDirectory();
            $this->config->save();

            //downloader
            $downloader = new MegaPatchDownloader(
                Normalizer::path($this->config['megatools_exe']),
                $this->config['tmp_dir'],
                $this->logger
            );

            $question = new ChoiceQuestion(
                'Do you want to:', [
                new FullPatch(
                    $game,
                    new Repository($this->config['patch_full']),
                    $downloader,
                    $this->logger
                ),
            ]);

            $this->command = $helper->ask($input, $output, $question);
            foreach ($this->command->getQuestions() as $name => $question) {
                $this->command->setOption($name, $helper->ask($input, $output, $question));
            }
        } catch (throwable $exception) {
            $this->logger->error($exception);
            throw $exception;
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws throwable
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->command->execute($input, $output, $this);
        } catch (throwable $e) {
            $this->logger->error($e);
            throw $e;
        }

        return Command::SUCCESS;
    }
}
