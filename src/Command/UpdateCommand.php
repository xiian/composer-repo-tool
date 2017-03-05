<?php
namespace xiian\ComposerRepoTool\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use xiian\ComposerRepoTool\Exception;

/**
 * Command to update the repo for a single package
 */
class UpdateCommand extends BaseCommand
{
    /**
     * @codeCoverageIgnore
     * @inheritdoc
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update a package to use VCS instead of git')
            ->addArgument(
                'package_name',
                InputArgument::REQUIRED,
                'Name of package to be updated (vendor/package format)'
            );
        parent::configure();
    }

    /**
     * @inheritdoc
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \xiian\ComposerRepoTool\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $package_name = $input->getArgument('package_name');

        $output->writeln('<info>Updating single package</info>: ' . $package_name);

        try {
            $package_spec = $this->lock_file->getSpecForPackage($package_name);

            $this->rewriteJsonForRepo($package_spec->source->url);
        }
        catch (\Exception $e) {
            throw new Exception(sprintf('Unable to update %s. %s', $package_name, $e->getMessage()));
        }

        $this->replacePackageInVendorDir($output, $package_name, $package_spec->version);

        $this->doCommit($output, $package_name);

        $output->writeln(sprintf('<info>Successfully updated %s</info>', $package_name));
    }

    /**
     * Changes the entry in composer.json to have the new settings
     *
     * @param $repo_url
     *
     * @throws \xiian\ComposerRepoTool\ComposerFile\Exception
     */
    protected function rewriteJsonForRepo($repo_url)
    {
        $repo_stanza       = $this->json_file->findRepositoryByUrl($repo_url);
        $repo_stanza->type = 'vcs';
        $repo_stanza->url  = $this->convertUrl($repo_url);
        if (!$this->dry_run) {
            $this->json_file->save();
        }
    }

    /**
     * Convert the URL from the old way to the new way
     *
     * @param $url
     *
     * @return string
     */
    public function convertUrl($url)
    {
        return preg_replace('~git@(.+?):(.+)~', 'https://\1/\2', $url);
    }

    /**
     * Replace the installed version of the package with the new VCS version
     *
     * @param OutputInterface $output
     * @param string          $package_name
     * @param string          $package_version
     */
    public function replacePackageInVendorDir(OutputInterface $output, $package_name, $package_version)
    {
        $output->writeln('Requiring anew, preferring dist this time', OutputInterface::VERBOSITY_VERBOSE);
        $this->removePackageDir($output, $this->vendor_path . $package_name);
        $this->shell(
            $output,
            [
                $this->composer_path,
                'update',
                '--working-dir=' . escapeshellcmd($this->working_dir),
                '--no-autoloader',
                '--no-progress',
                '--ignore-platform-reqs',
                '--no-suggest',
                '--prefer-dist',
                escapeshellarg(implode(':', [$package_name, $package_version])),
            ]
        );
    }

    /**
     * Remove the currently installed version of the package
     *
     * @param OutputInterface $output
     * @param string          $package_dir
     */
    public function removePackageDir(OutputInterface $output, $package_dir)
    {
        $output->writeln('Removing existing install at ' . $package_dir, OutputInterface::VERBOSITY_VERBOSE);

        if (!is_dir($package_dir)) {
            return;
        }

        // Doing this in PHP for easy of use with VFS for unit testing
        $iter  = new \RecursiveDirectoryIterator($package_dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            }
            else {
                unlink($file->getPathname());
            }
        }
        rmdir($package_dir);
    }

    /**
     * Commit the changes to composer.json and composer.lock
     *
     * @todo Refactor this away. This is purely out of laziness.
     *
     * @param OutputInterface $output
     * @param string          $package_name
     */
    public function doCommit(OutputInterface $output, $package_name)
    {
        $this->shell(
            $output,
            [
                $this->git_path,
                'add',
                'composer.json',
                'composer.lock',
            ]
        );
        $this->shell(
            $output,
            [
                $this->git_path,
                'commit',
                '-q',
                sprintf('-m "Update %s to use dist"', $package_name),
            ]
        );
    }

}
