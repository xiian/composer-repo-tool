<?php

namespace xiian\ComposerRepoTool\Test\Command;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use xiian\ComposerRepoTool\Command\UpdateCommand;
use xiian\ComposerRepoTool\Exception;

/**
 * @coversDefaultClass \xiian\ComposerRepoTool\Command\UpdateCommand
 */
class UpdateCommandTest extends \PHPUnit_Framework_TestCase
{
    use ProcessBuilderMockerTrait;

    public function convertUrlProvider()
    {
        $r = [];

        $r['empty']      = ['', ''];
        $r['basic']      = [
            'https://github.com/xiian/composer-repo-tool.git',
            'git@github.com:xiian/composer-repo-tool.git'
        ];
        $r['enterprise'] = [
            'https://enterprise.github.com/xiian/composer-repo-tool.git',
            'git@enterprise.github.com:xiian/composer-repo-tool.git'
        ];

        // This may change, but for now, just want to keep this behavior documented
        $r['Non-git user stays the same'] = [
            'xiian@github.com:xiian/composer-repo-tool.git',
            'xiian@github.com:xiian/composer-repo-tool.git',
        ];

        $r['Paths stay the same'] = [
            '/tmp/var/package.git',
            '/tmp/var/package.git',
        ];

        return $r;
    }

    /**
     * @covers ::convertUrl
     * @dataProvider convertUrlProvider
     *
     * @param string $expect
     * @param string $url
     *
     * @uses         \xiian\ComposerRepoTool\Command\BaseCommand
     * @uses         \xiian\ComposerRepoTool\Command\UpdateCommand::configure
     */
    public function testConvertUrl($expect, $url)
    {
        $obj = new UpdateCommand();
        $out = $obj->convertUrl($url);
        $this->assertEquals($expect, $out);
    }

    /**
     * @covers \xiian\ComposerRepoTool\Command\UpdateCommand
     * @uses   \xiian\ComposerRepoTool\ComposerFile\ComposerFile
     * @uses   \xiian\ComposerRepoTool\ComposerFile\ComposerJson
     * @uses   \xiian\ComposerRepoTool\ComposerFile\ComposerLock
     * @uses   \xiian\ComposerRepoTool\Command\BaseCommand
     */
    public function testExecute()
    {
        $testdir = vfsStream::setup(
            'project_root',
            null,
            [
                'composer.json' => json_encode(['repositories' => [['url' => 'git@github.com:xiian/composer-repo-tool.git']]]),
                'composer.lock' => json_encode(['packages' => [['name' => 'xiian/composer-repo-tool', 'version' => 'dev-master', 'source' => ['url' => 'git@github.com:xiian/composer-repo-tool.git']]]]),
                'vendor'        => []
            ]
        );

        // Now that the groundwork is done, begin actual test
        $tester = new CommandTester(
            new UpdateCommand(
                null, $this->mockProcessBuilder(
                [
                    'Composer update' => [2, ['composer', 'update']],
                    'Git add'         => [4, ['git', 'add']],
                    'Git commit'      => [4, ['git', 'commit']]
                ]
            )
            )
        );
        $tester->execute(
            [
                'package_name'  => 'xiian/composer-repo-tool',
                '--working-dir' => $testdir->url(),
            ]
        );

        $this->assertEquals(0, $tester->getStatusCode(), 'Command should return successfully');

        // Actual verification
        $json_content = json_decode($testdir->getChild('composer.json')->getContent());
        $this->assertEquals('vcs', $json_content->repositories[0]->type);
        $this->assertEquals('https://github.com/xiian/composer-repo-tool.git', $json_content->repositories[0]->url);
        // Can't check composer.lock file because we can't run composer on VFS. But the mock process builder should take care of that
    }

    /**
     * @covers \xiian\ComposerRepoTool\Command\UpdateCommand
     * @uses   \xiian\ComposerRepoTool\ComposerFile\ComposerFile
     * @uses   \xiian\ComposerRepoTool\ComposerFile\ComposerLock
     * @uses   \xiian\ComposerRepoTool\Command\BaseCommand
     */
    public function testExecuteWithProblemFindingPackage()
    {
        $testdir = vfsStream::setup(
            'project_root',
            null,
            [
                'composer.json' => json_encode(['repositories' => [['url' => 'git@github.com:xiian/composer-repo-tool.git']]]),
                'composer.lock' => json_encode(['packages' => []]),
                'vendor'        => []
            ]
        );

        // Now that the groundwork is done, begin actual test
        $tester = new CommandTester(
            new UpdateCommand(
                null, $this->mockProcessBuilder()
            )
        );
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('~Unable to update .*?. .*~');
        $tester->execute(
            [
                'package_name'  => 'xiian/composer-repo-tool',
                '--working-dir' => $testdir->url(),
            ]
        );
    }

    /**
     * @covers ::doCommit
     */
    public function testDoCommit()
    {
        $package_name = 'xiian/composer-repo-tool';
        $output       = new NullOutput();

        /** @var \Mockery\Mock|UpdateCommand $obj */
        $obj = \Mockery::mock(UpdateCommand::class)->makePartial();

        $obj->shouldReceive('shell')->withArgs([$output, ['git', 'add', 'composer.json', 'composer.lock']])->once();
        $obj->shouldReceive('shell')->withArgs([$output, ['git', 'commit', '-q', sprintf('-m "Update %s to use dist"', $package_name)]])->once();
        $obj->shouldReceive('shell')->withAnyArgs()->never();

        $obj->doCommit($output, $package_name);

        // The real test is the method calls.
        $this->assertTrue(true);
    }

    /**
     * @covers ::removePackageDir
     * @uses \xiian\ComposerRepoTool\Command\BaseCommand
     */
    public function testRemovePackageDir()
    {
        $structure = [
            'others' => [],
            'xiian'  => [
                'other-useful-tool'  => [],
                'composer-repo-tool' => [
                    'src'       => [
                        'timmy' => 'test sources'
                    ],
                    'README.md' => 'Hello'
                ]
            ]
        ];

        $testdir = vfsStream::setup('vendor', null, $structure);

        $tooldir = $testdir->getChild('xiian')->getChild('composer-repo-tool');

        $obj = new UpdateCommand();
        $obj->removePackageDir(new DummyOutput(), $tooldir->url());

        $this->assertEquals(
            [
                'vendor' => [
                    'others' => [],
                    'xiian'  => [
                        'other-useful-tool' => []
                    ]
                ]
            ],
            vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure(),
            'Package directory was not removed properly'
        );
    }
}
