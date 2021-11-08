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
     * @return int
     * @throws throwable
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $game = new Game($this->config['game_directory']);

            /** @var $helper QuestionHelper */
            $helper = $this->getHelper('question');
            while (!$game->checkDirectory()) {
                $output->writeln("Cannot find game in {$game->getDirectory()}");
                $question = new Question('Type correct directory in command line:');
                $answer = $helper->ask($input, $output, $question);
                $game->changeDirectory($answer);
            }
            //update config
            $this->config['game_directory'] = $game->getDirectory();
            $this->config->save();

            //normalize megatools
            $megatools = Normalizer::path($this->config['megatools_exe']);

            $link = (string) file_get_contents($this->config['patch_server']);
            $link = new MegaFileLink($link);

            if (!$link->isValid()) {
                $question = new Question('Please, provide mega.nz link to patch: ');
                $question->setValidator(fn ($answer) => LinkValidator::assert(MegaFileLink::createFromString($answer)));
                $link = MegaFileLink::createFromString((string) $helper->ask($input, $output, $question));
            }

            //downloader
            $downloader = new MegaPatchDownloader($link, $megatools, $this->config, $this->logger);


            try {
                $output->writeln(sprintf('Starting download patch from %s', $link->getLink()));
                $downloader->download();
            } catch (FileExistException $exception) {
                $output->writeln($exception->getMessage());
                $question = new ConfirmationQuestion('Do you want to override patch file? (y/N): ', false);
                $answer = $helper->ask($input, $output, $question);
                if ($answer) {
                    unlink($downloader->getDownloadedFile()->getRealPath());
                    $output->writeln(sprintf('Starting download patch from %s', $link->getLink()));
                    $downloader->download();
                }
            }

            if (!$downloader->getDownloadedFile()) {
                throw new InputException('Cannot find patch file.');
            }

            $gameOverrider = new GameOverrider();

            $question = new ConfirmationQuestion(
                'Do you want to override KeyBinds? Warning: tooltip will be not updated. (y/N): ',
                false
            );
            $answer = $helper->ask($input, $output, $question);
            $gameOverrider->add(Override::create('help', '/^English\\\Help\.cfg$/', $answer));
            $gameOverrider->add(Override::create('keymap', '/^English\\\KeyMap\.txt$/', $answer));

            $question = new ConfirmationQuestion('Do you want to override sounds? (y/N): ', false);
            $answer = $helper->ask($input, $output, $question);
            $gameOverrider->add(Override::create('voices', '/^Assets\\\Sides\\\\\w+VoicesEn.xcr$/', $answer));
            $gameOverrider->add(Override::create('speech', '/^English\\\GameSpeech\.xcr$/', $answer));

            $question = new ConfirmationQuestion('Do you want to override texts? (y/N): ', false);
            $answer = $helper->ask($input, $output, $question);
            $gameOverrider->add(Override::create('game', '/^English\\\Game\.txt$/', $answer));
            $gameOverrider->add(Override::create('screen help', '/^English\\\ScreenHelp\.txt$/', $answer));
            $gameOverrider->add(Override::create('spells', '/^English\\\Spells\.txt$/', $answer));
            $gameOverrider->add(Override::create('quest', '/^English\\\Quest\.cfg$/', $answer));
            $gameOverrider->add(Override::create('names', '/^English\\\Names\.cfg$/', $answer));
            $gameOverrider->add(Override::create('tutorial', '/^English\\\Tutorial\.cfg$/', $answer));
            $gameOverrider->add(Override::create('victory', '/^English\\\Victory\.cfg$/', $answer));
            $gameOverrider->add(Override::create('history', '/^English\\\History\.xml$/', $answer));
            $gameOverrider->add(Override::create('journal', '/^English\\\Journal\.xml$/', $answer));
            $gameOverrider->add(Override::create('hero selection', '/^English\\\WBC3HeroSelectionText\.txt$/',
                $answer));
            $gameOverrider->add(Override::create('xci strings', '/^English\\\XCIStrings\.txt$/', $answer));
            $gameOverrider->add(Override::create('campaign', '/^English\\\Campaign\\\\\w+\.(?:wav|xml)$/', $answer));

            if ($downloader->getDownloadedFile()->getExtension() !== 'rar') {
                throw new UnsupportedArchiveException("{$downloader->getDownloadedFile()->getFilename()} isn't supported file.");
            }

            $builder = new PatchFactory(RarPatch::class);
            $patch = $builder->create($downloader->getDownloadedFile());
            $patcher = new GamePatcher(
                $patch,
                $game,
                $gameOverrider,
                $this->logger
            );
            $patcher->patch();

            if ($game->removePatchXcr()) {
                $output->writeln('Removed PatchData.xcr.');
            }
        } catch (throwable $e) {
            $this->logger->error($e);
            throw $e;
        }

        return Command::SUCCESS;
    }
}
