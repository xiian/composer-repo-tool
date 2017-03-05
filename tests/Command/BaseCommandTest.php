<?php

namespace xiian\ComposerRepoTool\Test\Command;

use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use Symfony\Component\Process\Exception\ProcessFailedException;
use xiian\ComposerRepoTool\Command\BaseCommand;

/**
 * @coversDefaultClass \xiian\ComposerRepoTool\Command\BaseCommand
 */
class BaseCommandTest extends \PHPUnit_Framework_TestCase
{
    use ProcessBuilderMockerTrait;

    /**
     * @covers ::shell
     * @uses \xiian\ComposerRepoTool\Command\BaseCommand::setProcessBuilder
     */
    public function testShell()
    {
        $chunks = ['pwd'];

        // Get the builder
        $builder = $this->mockProcessBuilder();

        // Make sure we get the right arguments. Once.
        $builder->shouldReceive('setArguments')->withArgs([$chunks])->once();

        /** @var \Mockery\Mock $process */
        $process = $builder->getProcess();

        // Make sure run is called
        $process->mockery_getExpectationsFor('run')->findExpectation([])->once();

        // ------ real fun
        /** @var \Mockery\Mock|BaseCommand $obj */
        $obj = \Mockery::mock(BaseCommand::class)->makePartial();
        $obj->setProcessBuilder($builder);

        $obj->shell(new DummyOutput(), $chunks);

        // If we got here without exceptions, we're good. The real test is making sure methods were called.
        $this->assertTrue(true);
    }

    /**
     * @covers ::shell
     * @uses \xiian\ComposerRepoTool\Command\BaseCommand::setProcessBuilder
     */
    public function testShellUnsuccessful()
    {
        $chunks         = ['pwd'];
        $exit_code      = 418;
        $exit_code_text = 'I am a teapot';

        // Get the builder
        $builder = $this->mockProcessBuilder();

        // Make sure we get the right arguments. Once.
        $builder->shouldReceive('setArguments')->withArgs([$chunks])->once();

        /** @var \Mockery\Mock $process */
        $process = $builder->getProcess();

        // Make sure run is called
        $process->mockery_getExpectationsFor('run')->findExpectation([])->once();
        $process->mockery_getExpectationsFor('isSuccessful')->findExpectation([])->andReturn(false);
        $process->shouldReceive('getExitCode')->andReturn($exit_code);
        $process->shouldReceive('getExitCodeText')->andReturn($exit_code_text);
        $process->shouldReceive('getWorkingDirectory')->andReturn('dirdurder');
        $process->shouldReceive('isOutputDisabled')->andReturn(true);

        // ------ real fun
        /** @var \Mockery\Mock|BaseCommand $obj */
        $obj = \Mockery::mock(BaseCommand::class)->makePartial();
        $obj->setProcessBuilder($builder);

        $this->expectException(ProcessFailedException::class);
        $obj->shell(new DummyOutput(), $chunks);

        // If we got here without exceptions, we're good. The real test is making sure methods were called.
        $this->assertTrue(true);
    }

    /**
     * @covers ::shell
     * @uses \xiian\ComposerRepoTool\Command\BaseCommand::setProcessBuilder
     * @uses \xiian\ComposerRepoTool\Command\BaseCommand::setDryRun
     */
    public function testShellDoesntRunDuringDryRun()
    {
        $chunks = ['pwd'];

        // Get the builder
        $builder = $this->mockProcessBuilder();

        // Make sure we get the right arguments. Once.
        $builder->shouldReceive('setArguments')->withArgs([$chunks])->once();

        /** @var \Mockery\Mock $process */
        $process = $builder->getProcess();

        // Make sure run is called
        $process->mockery_getExpectationsFor('run')->findExpectation([])->never();

        // ------ real fun
        /** @var \Mockery\Mock|BaseCommand $obj */
        $obj = \Mockery::mock(BaseCommand::class)->makePartial();
        $obj->setDryRun(true);
        $obj->setProcessBuilder($builder);

        $obj->shell(new DummyOutput(), $chunks);

        // If we got here without exceptions, we're good. The real test is making sure methods were called.
        $this->assertTrue(true);
    }
}
