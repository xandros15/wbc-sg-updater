<?php

namespace WBCUpdater;

use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use throwable;
use WBCUpdater\Exceptions\FileExistException;
use WBCUpdater\Exceptions\InputException;
use WBCUpdater\Exceptions\UnsupportedArchiveException;

final class PatchCommand extends Command
{
    const NAME = 'patch';
    const DESCRIPTION = 'command using to patch your WBC game';
    /** @var Config */
    private Config $config;
    /** @var Logger */
    private Logger $logger;
    /** @var Game */
    private Game $game;
    /** @var MegaPatchDownloader */
    private MegaPatchDownloader $downloader;
    /** @var GameOverrider */
    private GameOverrider $overrider;

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
        /** @var $helper QuestionHelper */
        $helper = $this->getHelper('question');

        $this->game = new Game($this->config['game_directory']);

        while (!$this->game->checkDirectory()) {
            $output->writeln("Cannot find game in {$this->game->getDirectory()}");
            $question = new Question('Type correct directory in command line:');
            $answer = $helper->ask($input, $output, $question);
            $this->game->changeDirectory($answer);
        }
        //update config
        $this->config['game_directory'] = $this->game->getDirectory();

        try {
            $this->config->save();
        } catch (\throwable $exception) {
            $this->logger->error($exception);
            throw $exception;
        }

        //downloader
        $this->downloader = new MegaPatchDownloader(
            Normalizer::path($this->config['megatools_exe']),
            $this->config['tmp_dir'],
            $this->logger
        );

        $this->overrider = new GameOverrider();

        $question = new ConfirmationQuestion(
            'Do you want to override KeyBinds? Warning: tooltip will be not updated. (y/N): ',
            false
        );
        $answer = $helper->ask($input, $output, $question);
        $this->overrider->add(Override::create('help', '/^English\\\Help\.cfg$/', $answer));
        $this->overrider->add(Override::create('keymap', '/^English\\\KeyMap\.txt$/', $answer));

        $question = new ConfirmationQuestion('Do you want to override sounds? (y/N): ', false);
        $answer = $helper->ask($input, $output, $question);
        $this->overrider->add(Override::create('voices', '/^Assets\\\Sides\\\\\w+VoicesEn.xcr$/', $answer));
        $this->overrider->add(Override::create('speech', '/^English\\\GameSpeech\.xcr$/', $answer));

        $question = new ConfirmationQuestion('Do you want to override texts? (y/N): ', false);
        $answer = $helper->ask($input, $output, $question);
        $this->overrider->add(Override::create('game', '/^English\\\Game\.txt$/', $answer));
        $this->overrider->add(Override::create('screen help', '/^English\\\ScreenHelp\.txt$/', $answer));
        $this->overrider->add(Override::create('spells', '/^English\\\Spells\.txt$/', $answer));
        $this->overrider->add(Override::create('quest', '/^English\\\Quest\.cfg$/', $answer));
        $this->overrider->add(Override::create('names', '/^English\\\Names\.cfg$/', $answer));
        $this->overrider->add(Override::create('tutorial', '/^English\\\Tutorial\.cfg$/', $answer));
        $this->overrider->add(Override::create('victory', '/^English\\\Victory\.cfg$/', $answer));
        $this->overrider->add(Override::create('history', '/^English\\\History\.xml$/', $answer));
        $this->overrider->add(Override::create('journal', '/^English\\\Journal\.xml$/', $answer));
        $this->overrider->add(Override::create('hero selection', '/^English\\\WBC3HeroSelectionText\.txt$/', $answer));
        $this->overrider->add(Override::create('xci strings', '/^English\\\XCIStrings\.txt$/', $answer));
        $this->overrider->add(Override::create('campaign', '/^English\\\Campaign\\\\\w+\.(?:wav|xml)$/', $answer));
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
            /** @var $helper QuestionHelper */
            $helper = $this->getHelper('question');

            $link = (string) file_get_contents($this->config['patch_server']);
            $link = new MegaFileLink($link);

            if (!$link->isValid()) {
                $question = new Question('Please, provide mega.nz link to patch: ');
                $question->setValidator(fn ($answer) => LinkValidator::assert(MegaFileLink::createFromString($answer)));
                $link = MegaFileLink::createFromString((string) $helper->ask($input, $output, $question));
            }

            try {
                $output->writeln(sprintf('Starting download patch from %s', $link->getLink()));
                $this->downloader->download($link);
            } catch (FileExistException $exception) {
                $output->writeln($exception->getMessage());
                $question = new ConfirmationQuestion('Do you want to override patch file? (y/N): ', false);
                $answer = $helper->ask($input, $output, $question);
                if ($answer) {
                    unlink($this->downloader->getDownloadedFile()->getRealPath());
                    $output->writeln(sprintf('Starting download patch from %s', $link->getLink()));
                    $this->downloader->download($link);
                }
            }

            if (!$this->downloader->getDownloadedFile()) {
                throw new InputException('Cannot find patch file.');
            }

            if ($this->downloader->getDownloadedFile()->getExtension() !== 'rar') {
                throw new UnsupportedArchiveException("{$this->downloader->getDownloadedFile()->getFilename()} isn't supported file.");
            }

            $builder = new PatchFactory(RarPatch::class);
            $patch = $builder->create($this->downloader->getDownloadedFile());
            $patcher = new GamePatcher(
                $patch,
                $this->game,
                $this->overrider,
                $this->logger
            );
            $patcher->patch();

            if ($this->game->removePatchXcr()) {
                $output->writeln('Removed PatchData.xcr.');
            }
        } catch (throwable $e) {
            $this->logger->error($e);
            throw $e;
        }

        return Command::SUCCESS;
    }
}
