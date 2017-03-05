<?php
namespace xiian\ComposerRepoTool\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use xiian\ComposerRepoTool\Exception;

/**
 * Command to update *all* packages we can find that need it
 */
class UpdateAllCommand extends BaseCommand
{
    /**
     * @codeCoverageIgnore
     * @inheritdoc
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('update:all')
            ->setDescription('Update all packages that have repos of type "git" to be "VCS"')
            ->addOption('interactive', 'i', null, 'Process packages interactively');
        parent::configure();
    }

    /**
     * @inheritdoc
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Updating all packages');

        $interactive = $input->getOption('interactive');

        /** @var UpdateCommand $command */
        $command = $this->getApplication()->find('update');

        $input_array = [
            'package_name'   => '',
            '--dry-run'      => $input->getOption('dry-run'),
            '--working-dir'  => $input->getOption('working-dir'),
            '--git-bin'      => $input->getOption('git-bin'),
            '--composer-bin' => $input->getOption('composer-bin'),
        ];

        foreach ($this->lock_file->packagesWithoutDist() as $package_spec) {
            try {
                // Interactive mode
                if ($interactive) {
                    $question = new ConfirmationQuestion(
                        sprintf('Update package "%s"? [y/N] ', $package_spec->name),
                        false
                    );
                    if (!$this->getHelper('question')->ask($input, $output, $question)) {
                        throw new Exception(sprintf('Skipping %s', $package_spec->name));
                    }
                }

                $input_array['package_name'] = $package_spec->name;

                $command->run(new ArrayInput($input_array), $output);
            }
            catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
    }

}
