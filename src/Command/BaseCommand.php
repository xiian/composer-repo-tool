<?php
namespace xiian\ComposerRepoTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;
use xiian\ComposerRepoTool\ComposerFile;

/**
 * Abstract base class for other Commands
 */
abstract class BaseCommand extends Command
{
    /**
     * Whether this is a dry run or not
     *
     * @var bool
     */
    protected $dry_run = false;

    /**
     * Representation of composer.json file
     *
     * @var ComposerFile\ComposerJson
     */
    protected $json_file;

    /**
     * Representation of composer.lock file
     *
     * @var ComposerFile\ComposerLock
     */
    protected $lock_file;

    /**
     * Path to the vendor/ dir that composer uses
     *
     * @var string
     */
    protected $vendor_path;

    /**
     * Working directory for all commands
     *
     * @var string
     */
    protected $working_dir;

    /**
     * Path to composer executable
     *
     * @var string
     */
    protected $composer_path = 'composer';

    /**
     * Path to git executable
     *
     * @var string
     */
    protected $git_path = 'git';

    /**
     * ProcessBuilder to be used by ::shell()
     *
     * Set as a property so that we can swap it out as needed for testing.
     *
     * @var ProcessBuilder
     */
    private $process_builder;

    /**
     * BaseCommand constructor.
     *
     * @codeCoverageIgnore
     *
     * @param string|null         $name
     * @param ProcessBuilder|null $builder Allow overriding of ProcessBuilder used for shell commands
     */
    public function __construct($name = null, ProcessBuilder $builder = null)
    {
        if (null === $builder) {
            $builder = new ProcessBuilder();
        }
        $this->setProcessBuilder($builder);
        parent::__construct($name);
    }

    /**
     * Set the ProcessBuilder to be used by `shell`
     *
     * @codeCoverageIgnore
     *
     * @param ProcessBuilder $builder
     */
    public function setProcessBuilder(ProcessBuilder $builder)
    {
        $this->process_builder = $builder;
    }

    /**
     * Set whether this is a dry run or not
     *
     * @codeCoverageIgnore
     *
     * @param bool $dry_run
     */
    public function setDryRun($dry_run = true)
    {
        $this->dry_run = $dry_run;
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    protected function configure()
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Perform a dry run'
            )
            ->addOption(
                'working-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to where composer.json, composer.lock and vendor/ are',
                getcwd()
            )
            ->addOption(
                'composer-bin',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to composer executable to use.',
                'composer'
            )
            ->addOption(
                'git-bin',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to git executable to use.',
                'git'
            );
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \xiian\ComposerRepoTool\ComposerFile\Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Get real
        $this->working_dir = rtrim($input->getOption('working-dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $vendor_path    = $this->working_dir . 'vendor/';
        $lock_file_path = $this->working_dir . 'composer.lock';
        $json_file_path = $this->working_dir . 'composer.json';

        // Validate
        if (!is_dir($vendor_path)) {
            throw new \InvalidArgumentException(sprintf('Vendor directory (%s) is not a directory.', $vendor_path));
        }

        if (!is_file($lock_file_path)) {
            throw new \InvalidArgumentException(sprintf('%s not found in working-dir', $lock_file_path));
        }

        if (!is_file($json_file_path)) {
            throw new \InvalidArgumentException(sprintf('%s not found in working-dir', $json_file_path));
        }

        $this->setDryRun((bool) $input->getOption('dry-run'));

        $this->lock_file   = new ComposerFile\ComposerLock($lock_file_path);
        $this->json_file   = new ComposerFile\ComposerJson($json_file_path);
        $this->vendor_path = $vendor_path;

        $this->composer_path = $input->getOption('composer-bin');
        $this->git_path      = $input->getOption('git-bin');
    }

    /**
     * Convenience function to execute shell commands
     *
     * Will not actually perform operations if performing dry_run
     *
     * @param OutputInterface $output
     * @param string[]        $cmd_chunks
     *
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function shell(OutputInterface $output, $cmd_chunks)
    {
        $builder = $this->process_builder;
        $builder->setArguments($cmd_chunks);
        $process = $builder->getProcess();
        $builder->setArguments([]);

        $cmd = $process->getCommandLine();
        $output->writeln('<info>Executing</info>: ' . $cmd, OutputInterface::VERBOSITY_DEBUG);
        if ($this->dry_run) {
            return;
        }

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
