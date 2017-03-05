<?php

namespace xiian\ComposerRepoTool\Test\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

trait ProcessBuilderMockerTrait
{

    /**
     * @param array $calls
     *
     * @return \Mockery\Mock|ProcessBuilder
     */
    public function mockProcessBuilder(array $calls = [])
    {
        /**
         * A mock process object that always plays nice.
         *
         * @var \Mockery\Mock|Process $mockProcess
         */
        $mockProcess = \Mockery::mock(Process::class)
            ->shouldReceive('getCommandLine')->andReturn('mocked process')->getMock()
            ->shouldReceive('run')->getMock()
            ->shouldReceive('isSuccessful')->andReturn(true)->getMock()
            ->shouldReceive('stop')->getMock();

        /**
         * Mock out the process builder
         * This is how we're sure that we're making the system calls we expect.
         *
         * @var \Mockery\Mock|\Symfony\Component\Process\ProcessBuilder $builder
         */
        $builder = \Mockery::mock(ProcessBuilder::class)->makePartial()
            ->shouldReceive('getProcess')->withNoArgs()->andReturn($mockProcess)->getMock();

        // Calls to reset builder
        $builder->shouldReceive('setArguments')->with(
            \Mockery::on(
                function ($args) {
                    return empty($args);
                }
            )
        );

        // Any unexpected calls should cause problems
        $builder->shouldReceive('setArguments')->withAnyArgs()->never();

        // Load up calls
        foreach ($calls as $call_info) {
            /** @var array $call_info */
            $call = function ($args) use ($call_info) {
                if (count($args) < $call_info[0]) {
                    return false;
                }
                /** @var array $arguments */
                $arguments = $call_info[1];
                foreach ($arguments as $k => $v) {
                    if ($args[$k] !== $v) {
                        return false;
                    }
                }
                return true;
            };
            $builder->shouldReceive('setArguments')->with(\Mockery::on($call))->once();
        }

        return $builder;
    }
}
