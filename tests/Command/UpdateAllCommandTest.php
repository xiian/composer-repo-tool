<?php

namespace xiian\ComposerRepoTool\Test\Command;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use xiian\ComposerRepoTool\Command\UpdateAllCommand;
use Symfony\Component\Console\Command\Command;

class UpdateAllCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \xiian\ComposerRepoTool\Command\UpdateAllCommand
     * @uses   \xiian\ComposerRepoTool\ComposerFile\ComposerLock<extended>
     * @uses   \xiian\ComposerRepoTool\Command\BaseCommand
     */
    public function testExecute()
    {
        $package_name = 'xiian/composer-repo-tool';

        $testdir = vfsStream::setup(
            'project_root',
            null,
            [
                'composer.json' => json_encode(['repositories' => [['url' => 'git@github.com:xiian/composer-repo-tool.git']]]),
                'composer.lock' => json_encode(['packages' => [['name' => $package_name, 'version' => 'dev-master', 'source' => ['url' => 'git@github.com:xiian/composer-repo-tool.git']]]]),
                'vendor'        => []
            ]
        );

        /** @var \Mockery\Mock|\Symfony\Component\Console\Command\Command $mockCommand */
        $mockCommand = \Mockery::mock(
            Command::class,
            [
                'setApplication' => '',
                'isEnabled'      => true,
                'getAliases'     => [],
                'getName'        => 'update',
                'getDefinition'  => '',
            ]
        );
        $arguments   = [
            '--working-dir'  => $testdir->url(),
            '--dry-run'      => false,
            '--git-bin'      => 'gitbin',
            '--composer-bin' => 'composerbin',
        ];

        $mockCommand->shouldReceive('run')->with(
            \Mockery::on(
                function ($arg) use ($arguments, $package_name) {
                    if (!($arg instanceof ArrayInput)) {
                        return false;
                    }
                    /** @var ArrayInput $arg */
                    // Make sure that everything passed along to update:all is passed to update
                    foreach ($arguments as $k => $v) {
                        $this->assertEquals($v, $arg->getParameterOption($k));
                    }
                    $this->assertEquals($package_name, $arg->getParameterOption('package_name'));
                    return true;
                }
            ),
            OutputInterface::class
        )->once();
        $mockCommand->shouldReceive('run')->withAnyArgs()->never();

        // Now that the groundwork is done, begin actual test
        $command = new UpdateAllCommand();
        $app     = new Application();
        $app->add($command);
        $app->add($mockCommand);

        $command->setApplication($app);
        $tester = new CommandTester($command);

        $tester->execute($arguments);
        $this->assertEquals(0, $tester->getStatusCode(), 'Command should return successfully');
    }

    /**
     * @covers \xiian\ComposerRepoTool\Command\UpdateAllCommand
     * @uses   \xiian\ComposerRepoTool\ComposerFile\ComposerLock<extended>
     * @uses   \xiian\ComposerRepoTool\Command\BaseCommand
     */
    public function testExecuteInteractive()
    {
        $package_name = 'xiian/composer-repo-tool';

        $testdir = vfsStream::setup(
            'project_root',
            null,
            [
                'composer.json' => json_encode(['repositories' => [['url' => 'git@github.com:xiian/composer-repo-tool.git']]]),
                'composer.lock' => json_encode(['packages' => [['name' => $package_name, 'version' => 'dev-master', 'source' => ['url' => 'git@github.com:xiian/composer-repo-tool.git']]]]),
                'vendor'        => []
            ]
        );

        /** @var \Mockery\Mock|\Symfony\Component\Console\Command\Command $mockCommand */
        $mockCommand = \Mockery::mock(
            Command::class,
            [
                'setApplication' => '',
                'isEnabled'      => true,
                'getAliases'     => [],
                'getName'        => 'update',
                'getDefinition'  => '',
            ]
        );
        $arguments   = [
            '--working-dir' => $testdir->url(),
        ];
        // We shouldn't ever run anything, because default interactive behavior is to *not* update package
        $mockCommand->shouldReceive('run')->withAnyArgs()->never();

        $arguments['--interactive'] = true;

        // Now that the groundwork is done, begin actual test
        $command = new UpdateAllCommand();
        $app     = new Application();
        $app->add($command);
        $app->add($mockCommand);

        $command->setApplication($app);
        $tester = new CommandTester($command);

        $tester->execute($arguments, ['interactive' => false]);
        $this->assertEquals(0, $tester->getStatusCode(), 'Command should return successfully');
    }
}
