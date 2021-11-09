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
use WBCUpdater\PatchDownloader;
use WBCUpdater\PatchFactory;
use WBCUpdater\RarPatch;
use WBCUpdater\Repository;

final class SimplePatch implements CommandInterface
{
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

        $builder = new PatchFactory(RarPatch::class);
        $patch = $builder->create($this->downloader->getDownloadedFile());
        $patcher = new GamePatcher(
            $patch,
            $this->game,
            new GameOverrider(),
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
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return 'Simple Full Patch';
    }

    /**
     * @return array
     */
    public function getQuestions(): array
    {
        return [];
    }
}
