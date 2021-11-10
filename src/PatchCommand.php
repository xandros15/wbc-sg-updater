<?php

namespace WBCUpdater;

use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use throwable;
use WBCUpdater\Downloaders\MegaFileLink;
use WBCUpdater\Downloaders\MegaPatchDownloader;
use WBCUpdater\Options\OptionType;

final class PatchCommand extends Command
{
    const NAME = 'patch';
    const DESCRIPTION = 'command using to patch your WBC game';
    /** @var Config */
    private Config $config;
    /** @var Logger */
    private Logger $logger;

    const TYPE_FULL = 'full';
    const TYPE_EN = 'en';
    const TYPE_PL = 'pl';
    const TYPE_BGM2 = 'bgm2';
    const TYPE_BGM3 = 'bgm3';
    const TYPE_CHLOG = 'chlog';

    const PATCH_MAP = [
        self::TYPE_FULL => 'full',
        self::TYPE_EN => 'en',
        self::TYPE_PL => 'pl',
        self::TYPE_BGM2 => 'bgm2',
        self::TYPE_BGM3 => 'bgm3',
    ];

    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct(self::NAME);
        $this->setDescription(self::DESCRIPTION);
        $this->config = $config;
        $this->logger = $logger;
    }

    public function configure()
    {
        $this->addOption('simple', 's', InputOption::VALUE_NONE, 'Use only if you are stupid, simple?');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Use for dry run, nothing will be updated.');
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
        /** @var $helper QuestionHelper */
        $helper = $this->getHelper('question');
        $game = new Game($this->config['game_directory']);
        $repository = new Repository($this->config['repository']);
        $repository->register(MegaFileLink::class);

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

        $patcher = new GamePatcher($game, $this->logger);
        if ($input->getOption('simple')) {
            $link = $repository->create(self::TYPE_FULL);
            $output->writeln(sprintf('Starting download patch from %s', $link->getLink()));
            $patch = $downloader->download($link);
            $patcher->add($patch);
        } else {
            $question = new ChoiceQuestion(
                'Do you want to:', [
                1 => new OptionType('Install Full Patch', self::TYPE_FULL),
                2 => new OptionType('Polish Language Pack', self::TYPE_PL),
                3 => new OptionType('English Language Pack', self::TYPE_EN),
                4 => new OptionType('WBC2 Music Pack', self::TYPE_BGM2),
                5 => new OptionType('WBC3 Music Pack', self::TYPE_BGM3),
                6 => new OptionType('Show Changelog', self::TYPE_CHLOG),
            ]);

            /** @var $option OptionType */
            $option = $helper->ask($input, $output, $question);

            if ($option->any([
                self::TYPE_FULL,
                self::TYPE_EN,
                self::TYPE_PL,
                self::TYPE_BGM2,
                self::TYPE_BGM3,
            ])) {
                $link = $repository->create(self::PATCH_MAP[$option->getValue()]);
                $output->writeln(sprintf('Starting download patch from %s', $link->getLink()));
                $patch = $downloader->download($link);
                $patcher->add($patch);
            }

            $overrider = new GameOverrider();
            $patcher->setOverrider($overrider);

            if ($option->any([
                self::TYPE_FULL,
                self::TYPE_EN,
                self::TYPE_PL,
            ])) {
                $overrideKeybinds = $helper->ask($input, $output, new ConfirmationQuestion(
                    'Do you want to override KeyBinds? Warning: tooltip will be not updated. (y/N): ',
                    false
                ));
                $overrider->add(Override::create('help', '/^English\\\Help\.cfg$/', $overrideKeybinds));
                $overrider->add(Override::create('keymap', '/^English\\\KeyMap\.txt$/', $overrideKeybinds));
            }

            if ($option->is(self::TYPE_FULL)) {
                $overrideSound = $helper->ask($input, $output, new ConfirmationQuestion(
                    'Do you want to override sounds? (y/N): ', false
                ));
                $overrideText = $helper->ask($input, $output,
                    new ConfirmationQuestion('Do you want to override texts? (y/N): ', false));
                $overrider->add(Override::create('voices', '/^Assets\\\Sides\\\\\w+VoicesEn.xcr$/', $overrideSound));
                $overrider->add(Override::create('speech', '/^English\\\GameSpeech\.xcr$/', $overrideSound));
                $overrider->add(Override::create('game', '/^English\\\Game\.txt$/', $overrideText));
                $overrider->add(Override::create('screen help', '/^English\\\ScreenHelp\.txt$/', $overrideText));
                $overrider->add(Override::create('spells', '/^English\\\Spells\.txt$/', $overrideText));
                $overrider->add(Override::create('quest', '/^English\\\Quest\.cfg$/', $overrideText));
                $overrider->add(Override::create('names', '/^English\\\Names\.cfg$/', $overrideText));
                $overrider->add(Override::create('tutorial', '/^English\\\Tutorial\.cfg$/', $overrideText));
                $overrider->add(Override::create('victory', '/^English\\\Victory\.cfg$/', $overrideText));
                $overrider->add(Override::create('history', '/^English\\\History\.xml$/', $overrideText));
                $overrider->add(Override::create('journal', '/^English\\\Journal\.xml$/', $overrideText));
                $overrider->add(Override::create('hero selection',
                    '/^English\\\WBC3HeroSelectionText\.txt$/',
                    $overrideText
                ));
                $overrider->add(Override::create('xci strings', '/^English\\\XCIStrings\.txt$/', $overrideText));
                $overrider->add(Override::create('campaign',
                    '/^English\\\Campaign\\\\\w+\.(?:wav|xml)$/',
                    $overrideText
                ));
            }

            if ($option->is(self::TYPE_CHLOG)) {
                $process = new Process([
                    'explorer',
                    $this->config['patch_notes'],
                ]);
                $process->run();
            } else {
                $patcher->patch($input->getOption('dry-run'));
            }
            if ($game->removePatchXcr()) {
                $output->writeln('Removed PatchData.xcr.');
            }
        }

        return Command::SUCCESS;
    }
}
