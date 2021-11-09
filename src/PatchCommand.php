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
use WBCUpdater\Commands\LocalizationPatch;
use WBCUpdater\Commands\MusicPatch;
use WBCUpdater\Commands\ShowChangelog;

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
                new LocalizationPatch(
                    $game,
                    new RepositoryGroup([
                        LocalizationPatch::LANG_PL => new Repository($this->config['patch_pl']),
                        LocalizationPatch::LANG_EN => new Repository($this->config['patch_en']),
                    ]),
                    $downloader,
                    $this->logger
                ),
                new MusicPatch(
                    $game,
                    new RepositoryGroup([
                        'Warlords Battlecry 2' => new Repository($this->config['patch_music_2']),
                        'Warlords Battlecry 3' => new Repository($this->config['patch_music_3']),
                    ]),
                    $downloader,
                    $this->logger
                ),
                new ShowChangelog($this->config['patch_notes']),
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
