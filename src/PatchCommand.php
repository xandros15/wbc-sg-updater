<?php

namespace WBCUpdater;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

final class PatchCommand extends Command
{
    const NAME = 'patch';
    const DESCRIPTION = 'command using to patch your WBC game';
    /** @var Config */
    private Config $config;

    public function __construct(Config $config)
    {
        parent::__construct(self::NAME);
        $this->setDescription(self::DESCRIPTION);
        $this->config = $config;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
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
        $megatools = $this->config['megatools_exe'];
        if (substr($megatools, 1, 1) !== ':') {
            $megatools = $this->config['megatools_exe'];
        }

        $link = $this->config['patch_link'];

        if (!$link) {
            $question = new Question('Please, provide mega.nz link to patch: ');
            $question->setValidator(function ($answer) {
                if (!preg_match('~^https://mega.nz/file/.+~', $answer)) {
                    throw new RuntimeException('Link should be valid mega link to file.');
                }

                return $answer;
            });
            $link = $helper->ask($input, $output, $question);
            if (!$link) {
                $output->writeln('Missing patch link, exit.');

                return Command::INVALID;
            }
            $this->config['patch_link'] = $link;
            $this->config->save();
        }

        //downloader
        $downloader = new MegaPatchDownloader($link, $megatools);

        try {
            $output->writeln(sprintf('Starting download patch from %s', $link));
            $downloader->download();
        } catch (FileExistException $exception) {
            $output->writeln($exception->getMessage());
            $question = new ConfirmationQuestion('Do you want to override patch file? (y/N): ', false);
            $answer = $helper->ask($input, $output, $question);
            if ($answer) {
                unlink($downloader->getDownloadedFile()->getRealPath());
                $output->writeln(sprintf('Starting download patch from %s', $link));
                $downloader->download();
            }
        }

        if (!$downloader->getDownloadedFile()) {
            throw new MissingPatchFileException('Cannot find patch file.');
        }

        $gameOverrider = new GameOverrider();

        $question = new ConfirmationQuestion(
            'Do you want to override KeyBinds? Warning: tooltip will be not updated. (y/N): ',
            false
        );
        $override = new Override('keybinds', '/^English\\\Help\.cfg$/');
        $gameOverrider->add($override);
        if ($helper->ask($input, $output, $question)) {
            $override->enable();
        }

        $question = new ConfirmationQuestion('Do you want to override Army Sounds? (y/N): ', false);
        $override = new Override('sounds', '/^Assets\\\Sides\\\\\w+VoicesEn.xcr$/');
        $gameOverrider->add($override);
        if ($helper->ask($input, $output, $question)) {
            $override->enable();
        }

        $patcher = new GamePatcher(
            $downloader,
            $game,
            $gameOverrider
        );
        $patcher->patch();

        if ($game->removePatchXcr()) {
            $output->writeln('Removed PatchData.xcr.');
        }

        return Command::SUCCESS;
    }
}
