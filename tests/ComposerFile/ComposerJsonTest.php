<?php

namespace xiian\ComposerRepoTool\Test\ComposerFile;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use xiian\ComposerRepoTool\ComposerFile\ComposerJson;
use xiian\ComposerRepoTool\ComposerFile\Exception;

/**
 * @coversDefaultClass \xiian\ComposerRepoTool\ComposerFile\ComposerJson
 */
class ComposerJsonTest extends \PHPUnit_Framework_TestCase
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
     * @covers ::findRepositoryByUrl
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testFindRepositoryByUrlUninitialized()
    {
        $obj = new ComposerJson($this->file->url());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('composer.json expects there to be a repositories array. None found.');
        $obj->findRepositoryByUrl('earl');
    }

    /**
     * @covers ::findRepositoryByUrl
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testFindRepositoryByUrlNonArray()
    {
        $contents = [
            'repositories' => 'asdf'
        ];
        $this->file->withContent(json_encode($contents));

        $obj = new ComposerJson($this->file->url());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid format for repositories. Expecting an array');
        $obj->findRepositoryByUrl('earl');
    }

    /**
     * @covers ::findRepositoryByUrl
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testFindRepositoryByUrlMissing()
    {
        $contents = [
            'repositories' => []
        ];
        $this->file->withContent(json_encode($contents));

        $obj = new ComposerJson($this->file->url());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not find repository in composer.json for url: earl');
        $obj->findRepositoryByUrl('earl');
    }

    /**
     * @covers ::findRepositoryByUrl
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
     */
    public function testFindRepositoryByUrlSuccessul()
    {
        $contents = [
            'repositories' => [
                ['url' => 'doesntmatter'],
                [
                    'url'  => 'earl',
                    'name' => 'duke/earl'
                ],
                ['url' => 'doesntmatter'],
            ]
        ];
        $this->file->withContent(json_encode($contents));

        $obj = new ComposerJson($this->file->url());

        $out = $obj->findRepositoryByUrl('earl');

        $this->assertEquals((object)['url' => 'earl', 'name' => 'duke/earl'], $out);
    }
}
