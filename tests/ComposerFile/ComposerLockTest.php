<?php

namespace xiian\ComposerRepoTool\Test\ComposerFile;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use xiian\ComposerRepoTool\ComposerFile\ComposerLock;
use xiian\ComposerRepoTool\ComposerFile\Exception;

/**
 * @coversDefaultClass \xiian\ComposerRepoTool\ComposerFile\ComposerLock
 */
class ComposerLockTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  vfsStreamFile
     */
    private $file;

    protected function setUp()
    {
        // Set up the file
        $this->file = vfsStream::newFile('composerfiletest')->at(vfsStream::setup())->withContent(json_encode([]));
    }

    /**
     * Make sure we don't go ballistic on invalid data
     * @covers ::packagesWithoutDist
     *
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testPackagesWithoutDistMissing()
    {
        $obj = new ComposerLock($this->file->url());
        $out = $obj->packagesWithoutDist();

        $count = 0;
        foreach ($out as $x) {
            ++$count;
        }
        $this->assertEquals(0, $count);
    }

    /**
     * Make sure we don't go ballistic on invalid data
     * @covers ::packagesWithoutDist
     *
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testPackagesWithoutDistInvalidPackageSpec()
    {
        $this->file->withContent(
            json_encode(
                [
                    'packages' => 1
                ]
            )
        );
        $obj = new ComposerLock($this->file->url());
        $out = $obj->packagesWithoutDist();

        $count = 0;
        foreach ($out as $x) {
            ++$count;
        }
        $this->assertEquals(0, $count);
    }

    /**
     * @covers ::packagesWithoutDist
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testPackagesWithoutDist()
    {
        $this->file->withContent(
            json_encode(
                [
                    'packages'     => [
                        (object)['dist' => 'hi'],
                        (object)['source' => 'hi'],
                        (object)['dist' => 'hi'],
                        (object)['dist' => 'hi'],
                        (object)['source' => 'hi'],
                        (object)['source' => 'hi'],
                    ],
                    'packages-dev' => [
                        (object)['dist' => 'hi'],
                        (object)['source' => 'hi'],
                    ],
                ]
            )
        );
        $obj = new ComposerLock($this->file->url());
        $out = $obj->packagesWithoutDist();

        $count = 0;
        foreach ($out as $x) {
            $this->assertObjectNotHasAttribute('dist', $x);
            ++$count;
        }
        $this->assertEquals(4, $count, 'packageWithoutDist should have found 3 packages');
    }

    /**
     * @covers ::getSpecForPackage
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testGetSpecForPackageMissing()
    {
        $obj = new ComposerLock($this->file->url());
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not find package spec for');
        $obj->getSpecForPackage('asdf');
    }

    /**
     * Make sure nothing blows up on invalid data
     * @covers ::getSpecForPackage
     *
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testGetSpecForPackageInValid()
    {
        $this->file->withContent(
            json_encode(
                [
                    'packages' => 'other'
                ]
            )
        );

        $obj = new ComposerLock($this->file->url());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not find package spec for');
        $obj->getSpecForPackage('asdf');
    }

    /**
     * @covers ::getSpecForPackage
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testGetSpecForPackageValid()
    {
        $name = 'asdf';
        $this->file->withContent(
            json_encode(
                [
                    'packages' => [
                        (object)['name' => 'abc', 'special' => false],
                        (object)['name' => $name, 'special' => true],
                        (object)['name' => 'xyz', 'special' => false],
                    ]
                ]
            )
        );
        $obj = new ComposerLock($this->file->url());

        $spec = $obj->getSpecForPackage($name);
        $this->assertTrue($spec->special);
        $this->assertEquals($name, $spec->name);
    }
}
