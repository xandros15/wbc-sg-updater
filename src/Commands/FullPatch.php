<?php

declare(strict_types=1);

namespace WBCUpdater\Commands;

use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use WBCUpdater\Exceptions\FileExistException;
use WBCUpdater\Exceptions\InputException;
use WBCUpdater\Exceptions\UnsupportedArchiveException;
use WBCUpdater\Game;
use WBCUpdater\GameOverrider;
use WBCUpdater\GamePatcher;
use WBCUpdater\LinkValidator;
use WBCUpdater\MegaFileLink;
use WBCUpdater\Override;
use WBCUpdater\PatchDownloader;
use WBCUpdater\PatchFactory;
use WBCUpdater\RarPatch;
use WBCUpdater\Repository;

final class FullPatch implements CommandInterface
{

    private bool $keyBinds = false;
    private bool $sounds = false;
    private bool $texts = false;
    /** @var Repository */
    private Repository $repository;
    /** @var Logger */
    private Logger $logger;
    /** @var PatchDownloader */
    private PatchDownloader $downloader;
    /** @var Game */
    private Game $game;

    /**
     * FullPatch constructor.
     *
     * @param Game $game
     * @param Repository $repository
     * @param PatchDownloader $downloader
     * @param Logger $logger
     */
    public function __construct(
        Game $game,
        Repository $repository,
        PatchDownloader $downloader,
        Logger $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->downloader = $downloader;
        $this->game = $game;
    }

    public function execute(InputInterface $input, OutputInterface $output, Command $command): void
    {
        /** @var $helper QuestionHelper */
        $helper = $command->getHelper('question');
        $link = $this->repository->getPatchLink(MegaFileLink::class);

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

        $overrider = new GameOverrider();

        $overrider->add(Override::create('help', '/^English\\\Help\.cfg$/', $this->keyBinds));
        $overrider->add(Override::create('keymap', '/^English\\\KeyMap\.txt$/', $this->keyBinds));
        $overrider->add(Override::create('voices', '/^Assets\\\Sides\\\\\w+VoicesEn.xcr$/', $this->sounds));
        $overrider->add(Override::create('speech', '/^English\\\GameSpeech\.xcr$/', $this->sounds));
        $overrider->add(Override::create('game', '/^English\\\Game\.txt$/', $this->texts));
        $overrider->add(Override::create('screen help', '/^English\\\ScreenHelp\.txt$/', $this->texts));
        $overrider->add(Override::create('spells', '/^English\\\Spells\.txt$/', $this->texts));
        $overrider->add(Override::create('quest', '/^English\\\Quest\.cfg$/', $this->texts));
        $overrider->add(Override::create('names', '/^English\\\Names\.cfg$/', $this->texts));
        $overrider->add(Override::create('tutorial', '/^English\\\Tutorial\.cfg$/', $this->texts));
        $overrider->add(Override::create('victory', '/^English\\\Victory\.cfg$/', $this->texts));
        $overrider->add(Override::create('history', '/^English\\\History\.xml$/', $this->texts));
        $overrider->add(Override::create('journal', '/^English\\\Journal\.xml$/', $this->texts));
        $overrider->add(Override::create('hero selection', '/^English\\\WBC3HeroSelectionText\.txt$/', $this->texts));
        $overrider->add(Override::create('xci strings', '/^English\\\XCIStrings\.txt$/', $this->texts));
        $overrider->add(Override::create('campaign', '/^English\\\Campaign\\\\\w+\.(?:wav|xml)$/', $this->texts));

        $builder = new PatchFactory(RarPatch::class);
        $patch = $builder->create($this->downloader->getDownloadedFile());
        $patcher = new GamePatcher(
            $patch,
            $this->game,
            $overrider,
            $this->logger
        );
        $patcher->patch();

        if ($this->game->removePatchXcr()) {
            $output->writeln('Removed PatchData.xcr.');
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return mixed
     */
    public function setOption(string $name, $value): void
    {
        $this->{$name} = $value;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return 'Install Full Patch';
    }

    /**
     * @return array
     */
    public function getQuestions(): array
    {
        return [
            'keyBinds' => new ConfirmationQuestion('Do you want to override KeyBinds? Warning: tooltip will be not updated. (y/N): ',
                false),
            'sounds' => new ConfirmationQuestion('Do you want to override sounds? (y/N): ', false),
            'texts' => new ConfirmationQuestion('Do you want to override texts? (y/N): ', false),
        ];
    }
}
