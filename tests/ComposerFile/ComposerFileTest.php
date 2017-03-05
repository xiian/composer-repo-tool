<?php

namespace xiian\ComposerRepoTool\Test\ComposerFile;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use xiian\ComposerRepoTool\ComposerFile\ComposerFile;
use xiian\ComposerRepoTool\ComposerFile\Exception;

/**
 * @coversDefaultClass \xiian\ComposerRepoTool\ComposerFile\ComposerFile
 * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__construct
 */
class ComposerFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  vfsStreamFile
     */
    private $file;

    /**
     * @var ComposerFile
     */
    private $obj;

    protected function setUp()
    {
        $root = vfsStream::setup();

        // Set up the file
        $json       = [
            'one'   => 1,
            'two'   => 'dos',
            'three' => [1, '2', 3],
            'four'  => (object)['this' => 'here', 'that' => 'there', 'other' => 'everywhere'],
        ];
        $this->file = vfsStream::newFile('composerfiletest')->at($root)->withContent(json_encode($json));

        $this->obj = $this->getMockForAbstractClass(ComposerFile::class, [$this->file->url()]);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(ComposerFile::class, $this->obj);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorValidation()
    {
        $this->file->withContent('asdf');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('does not contain valid JSON');
        $this->getMockForAbstractClass(ComposerFile::class, [$this->file->url()]);
    }

    /**
     * @covers ::__get
     */
    public function testMagicGet()
    {
        $this->assertEquals(1, $this->obj->one);
    }

    /**
     * @covers ::__get
     */
    public function testMagicGetMissing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('~Property ".+" does not exist in .*~');
        $this->obj->missing;
    }

    /**
     * @covers ::save
     * @uses \xiian\ComposerRepoTool\ComposerFile\ComposerFile::__get
     */
    public function testSave()
    {
        $original = $this->file->getContent();

        // Change a value
        $this->obj->four->this = 'dva';

        // Save it off
        $this->obj->save();

        // Make sure it's different
        $this->assertNotEquals($original, $this->file->getContent());
    }
}
