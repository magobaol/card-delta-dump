<?php

namespace App\Command;

use App\CardHelper;
use App\DirectoryInfo;
use Model\CDDProcess;
use Model\FileOperation;
use App\GoodSyncCommandFactory;
use App\MIOperations;
use App\Operations;
use Model\OperationLogger;
use phpDocumentor\Reflection\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:dump',
    description: 'Dump the content of the card mirroring the file into one location and copy just the new ones into another location',
)]
class DumpCommand extends Command
{
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;
    private Filesystem $fs;
    private CardHelper $cardHelper;
    private SymfonyStyle $io;
    private $questionHelper;
    private InputInterface $input;
    private OutputInterface $output;

    public function __construct(ParameterBagInterface $parameterBag, Filesystem $filesystem, CardHelper $cardHelper)
    {
        parent::__construct();
        $this->parameterBag = $parameterBag;
        $this->fs = $filesystem;
        $this->cardHelper = $cardHelper;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('card-name', InputArgument::REQUIRED, 'The name of the card you want to mirror')
            ->addOption('mirror-base-dir', '', InputOption::VALUE_REQUIRED)
            ->addOption('import-base-dir', '', InputOption::VALUE_REQUIRED)
        ;
    }

    private function checkRequiredDir(DirectoryInfo $directoryInfo): bool
    {
        if (!$this->fs->exists($directoryInfo->getPath())) {
            $warningTitle = sprintf("%s missing", $directoryInfo->getDescription());

            $warningLine1 = 'The directory '.$directoryInfo->getPath().' does not exists.';
            $warningLine2 = $directoryInfo->getExplanation();
            $warningLine3 = $directoryInfo->getExplanationForDirectoryMissing();
            $warningText = PHP_EOL.$warningLine1.PHP_EOL.$warningLine2.PHP_EOL.$warningLine3;
            $this->io->warning($warningTitle.PHP_EOL.$warningText);

            $question = new ConfirmationQuestion(sprintf('Should I create the directory %s? (Y/n) ', $directoryInfo->getPath()), true);
            if ($this->questionHelper->ask($this->input, $this->output, $question)) {
                $this->fs->mkdir($directoryInfo->getPath());
                $this->io->writeln(sprintf('%s created', $directoryInfo->getDescription()));
                return true;
            } else {
                $this->io->error("Then there's nothing I can do here. Bye");
                return false;
            }
        }
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->questionHelper = $this->getHelper('question');
        $this->input = $input;
        $this->output = $output;

        $this->io->writeln('');
        $this->io->writeln("========= CARD DELTA DUMP ========= ");
        $this->io->writeln('');

        $cardName = $input->getArgument('card-name');

        if (!$this->cardHelper->isValidCardName($cardName)) {
            $this->io->error(sprintf('The card name is not in valid format. It should be named something like %s', $this->cardHelper->getSampleCardName()));
            return Command::INVALID;
        }

        $sourceDir = $this->cardHelper->getSourceDirFromCardName($cardName);

        //***** Check if the source dir exists (that is, the card is inserted) *****
        if (!$this->fs->exists($sourceDir)) {
            $this->io->error(sprintf("The source dir %s does not exists, so probably the card is not inserted", $sourceDir));
            return Command::FAILURE;
        }

        /*** MIRROR BASE DIR ***/
        if ($input->getOption('mirror-base-dir')) {
            $mirrorBaseDir = $input->getOption('mirror-base-dir');
        } else {
            $mirrorBaseDir = $this->parameterBag->get('app.mirror_base_dir');
        }

        $mirrorBaseDirInfo = (new DirectoryInfo($mirrorBaseDir))
            ->withDescription('Base mirror directory')
            ->withExplanation('This is the base directory used by Card Delta Dump for mirroring your cards.')
            ->withExplanationForDirectoryMissing("If it's missing it's likely that you've never run this program before with this directory as the base destination for all your mirrors.");

        if (!$this->checkRequiredDir($mirrorBaseDirInfo)) {
            return Command::FAILURE;
        }

        /*** IMPORT BASE DIR ***/
        if ($input->getOption('import-base-dir')) {
            $importBaseDir = $input->getOption('import-base-dir');
        } else {
            $importBaseDir = $this->parameterBag->get('app.import_base_dir');
        }

        $importBaseDirInfo = (new DirectoryInfo($importBaseDir))
            ->withDescription('Base import directory')
            ->withExplanation('This is the base directory used by Card Delta Dump for copying your cards files meant to be imported.')
            ->withExplanationForDirectoryMissing("If it's missing it's likely that you've never run this program before with this directory as the base destination for all your files to be imported.");

        if (!$this->checkRequiredDir($importBaseDirInfo)) {
            return Command::FAILURE;
        }

        /*** MIRROR CARD DIR ***/
        $mirrorCardDir = $this->cardHelper->getCardDirFromBaseDirAndCardName($mirrorBaseDir, $cardName);

        $mirrorCardDirInfo = (new DirectoryInfo($mirrorCardDir))
            ->withDescription('Final mirror directory')
            ->withExplanation(sprintf('This is the directory used by Card Delta Dump to mirror the files from the card %s.', $cardName))
            ->withExplanationForDirectoryMissing("If it's missing it's likely that this is the first time you have been dumping this card.");

        if (!$this->checkRequiredDir($mirrorCardDirInfo)) {
            return Command::FAILURE;
        }

        /*** IMPORT CARD DIR ***/
        $importCardDir = $this->cardHelper->getCardDirFromBaseDirAndCardName($importBaseDir, $cardName);

        $importCardDirInfo = (new DirectoryInfo($importCardDir))
            ->withDescription('Final import directory')
            ->withExplanation(sprintf('This is the directory used by Card Delta Dump to copy the files from the card %s meant to be imported.', $cardName))
            ->withExplanationForDirectoryMissing("If it's missing it's likely that this is the first time you have been dumping this card.");

        if (!$this->checkRequiredDir($importCardDirInfo)) {
            return Command::FAILURE;
        }

        $info[] = "I'm going to run an analysis with the following directories:";
        $info[] = '';
        $info[] = sprintf('Source dir: %s', $sourceDir);
        $info[] = sprintf('Mirror card dir: %s', $mirrorCardDir);
        $info[] = sprintf('Import card dir: %s', $importCardDir);
        $info[] = '';
        $this->io->writeln($info);

        $question = new ConfirmationQuestion('Should I continue with analysis? (Y/n) ', true);
        if (!$this->questionHelper->ask($input, $output, $question)) {
            $this->io->writeln("Ok, bye");
            return Command::SUCCESS;
        }

        $this->io->writeln('');
        $this->io->writeln("========= CARD DELTA DUMP - ANALYSIS ========= ");
        $this->io->writeln('');
        $this->io->writeln("SKIP: The file is already present in the mirror.");
        $this->io->writeln("MIRROR: The file is not present in the mirror so it'll be copied there. However, it has an unrecognized extension, so it won't be copied in the import directory.");
        $this->io->writeln("MIRROR_AND_IMPORT: The file is not present in the mirror and it has a recognized extension, so it'll be copied both in the mirror and the import directory.");
        $this->io->writeln('');


        $process = new CDDProcess(new Finder(), $this->fs, $sourceDir, $mirrorCardDir, $importCardDir);

        foreach ($process->analyse() as $operation) {
            $this->io->writeln($operation->toString());
        }

        $this->io->writeln('');
        $this->io->writeln("========= CARD DELTA DUMP - SUMMARY ========= ");
        $this->io->writeln('');

        $this->io->writeln(sprintf('Total files on card: %s', $process->countAll()));
        $this->io->writeln(sprintf('Total files to skip: %s', $process->countSkipOperations()));
        $this->io->writeln(sprintf('Total new files to mirror: %s', $process->countMirrorOperations()));
        $this->io->writeln(sprintf('Total new files to mirror and import: %s', $process->countMirrorAndImportOperations()));
        $this->io->writeln('');
        $this->io->writeln("========= CARD DELTA DUMP - END SUMMARY ========= ");
        $this->io->writeln('');

        if ($process->countMirrorOperations() > 0 || $process->countMirrorAndImportOperations() > 0) {
            $question = new ConfirmationQuestion('Should I continue? (Y/n) ', true);
            if (!$this->questionHelper->ask($input, $output, $question)) {
                $this->io->writeln("Ok, bye");
                return Command::SUCCESS;
            }

            $this->io->writeln('Ok, hold on, this may take a while...');

            foreach ($process->execute() as $operation) {
                $this->io->writeln($operation->toString());
            }

        } else {
            $this->io->writeln('Everything seems up-to-date, good!');
        }

        return Command::SUCCESS;
    }


}
