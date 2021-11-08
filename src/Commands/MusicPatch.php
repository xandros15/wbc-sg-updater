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
use WBCUpdater\PatchDownloader;
use WBCUpdater\PatchFactory;
use WBCUpdater\RarPatch;
use WBCUpdater\RepositoryGroup;

final class MusicPatch implements CommandInterface
{

    /** @var string */
    private string $version;
    /** @var RepositoryGroup */
    private RepositoryGroup $repositories;
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
        $this->logger = $logger;
        $this->downloader = $downloader;
        $this->game = $game;
        $this->repositories = $repositories;
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

        $repository = $this->repositories->getRepository($this->version);
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

        $builder = new PatchFactory(RarPatch::class);
        $patch = $builder->create($this->downloader->getDownloadedFile());
        $patcher = new GamePatcher(
            $patch,
            $this->game,
            new GameOverrider(),
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
        if ($name === 'version') {
            $this->version = $value;
        }
    }

    /**
     * @return array
     */
    public function getQuestions(): array
    {
        return [
            'version' => new ChoiceQuestion('From what version?', [
                'Warlords Battlecry 2',
                'Warlords Battlecry 3',
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return 'Update Background Music';
    }
}
