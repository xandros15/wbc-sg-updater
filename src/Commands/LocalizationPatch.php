<?php

declare(strict_types=1);

namespace WBCUpdater\Commands;

use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use WBCUpdater\Exceptions\FileExistException;
use WBCUpdater\Exceptions\InputException;
use WBCUpdater\Exceptions\RuntimeException;
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
use WBCUpdater\RepositoryGroup;

final class LocalizationPatch implements CommandInterface
{
    /** @var Game */
    private Game $game;
    /** @var RepositoryGroup */
    private RepositoryGroup $repositories;
    /** @var PatchDownloader */
    private PatchDownloader $downloader;
    /** @var Logger */
    private Logger $logger;
    /** @var int */
    private int $type;
    /** @var int */
    private int $language;
    /** @var bool */
    private bool $keyBinds;

    public const LANG_PL = 0;
    public const LANG_EN = 1;
    private const TYPE_FULL = 0;
    private const TYPE_VC = 1;
    private const TYPE_TXT = 2;

    /**
     * FullPatch constructor.
     *
     * @param Game $game
     * @param RepositoryGroup $repositories
     * @param PatchDownloader $downloader
     * @param Logger $logger
     */
    public function __construct(
        Game $game,
        RepositoryGroup $repositories,
        PatchDownloader $downloader,
        Logger $logger
    ) {
        $this->game = $game;
        $this->repositories = $repositories;
        $this->downloader = $downloader;
        $this->logger = $logger;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Command $command
     */
    public function execute(InputInterface $input, OutputInterface $output, Command $command): void
    {
        /** @var $helper QuestionHelper */
        $helper = $command->getHelper('question');

        $repository = $this->repositories->getRepository((string) $this->language);
        if (!$repository) {
            throw new RuntimeException("Invalid repository name.");
        }
        $link = $repository->getPatchLink(MegaFileLink::class);

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
        $builder = new PatchFactory(RarPatch::class);
        $patch = $builder->create($this->downloader->getDownloadedFile());
        $patcher = new GamePatcher(
            $patch,
            $this->game,
            $overrider,
            $this->logger
        );
        $patcher->patch();
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOption(string $name, $value): void
    {
        if ($name === 'language') {
            $this->language = (int) $value;
        }

        if ($name === 'type') {
            $this->type = (int) $value;
        }

        if ($name === 'keyBinds') {
            $this->keyBinds = (bool) $value;
        }
    }

    /**
     * @return array
     */
    public function getQuestions(): array
    {
        return [
            'language' => new ChoiceQuestion(
                'Language: ', [
                    self::LANG_PL => 'Polish',
                    self::LANG_EN => 'English',
                ]
            ),
            'type' => new ChoiceQuestion(
                'Type: ', [
                    self::TYPE_FULL => 'Full',
                    self::TYPE_VC => 'Only Voices',
                    self::TYPE_TXT => 'Only Text',
                ]
            ),
            'keyBinds' => new ConfirmationQuestion('Do you want to override KeyBinds? Warning: tooltip will be not updated. (y/N): ',
                false),
        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return 'Change Localization';
    }
}
