<?php
namespace xiian\ComposerRepoTool\ComposerFile;

/**
 * Abstract base class for composer files, to provide some common functionality
 */
abstract class ComposerFile
{
    /**
     * Representation of the contents of this file
     *
     * @var \stdClass
     */
    protected $contents;

    /**
     * Path to this file
     *
     * @var string
     */
    protected $path;

    /**
     * ComposerFile constructor.
     *
     * @param string $path Path to the file
     *
     * @throws \xiian\ComposerRepoTool\ComposerFile\Exception
     */
    public function __construct($path)
    {
        $this->path     = $path;
        $this->contents = json_decode(file_get_contents($path));
        if (null === $this->contents) {
            throw new Exception(sprintf('%s does not contain valid JSON', $path));
        }
    }

    /**
     * Magic method to proxy object access inside the file
     *
     * @param string $name
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        if (!isset($this->contents->$name)) {
            throw new \InvalidArgumentException(sprintf('Property "%s" does not exist in %s', $name, $this->path));
        }
        return $this->contents->$name;
    }

    /**
     * Save the file
     *
     * @return bool
     */
    public function save()
    {
        return (bool) file_put_contents(
            $this->path,
            json_encode($this->contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }
}
