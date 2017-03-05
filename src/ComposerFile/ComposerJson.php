<?php
namespace xiian\ComposerRepoTool\ComposerFile;

/**
 * Wrapper for composer.json file
 */
class ComposerJson extends ComposerFile
{
    /**
     * Find the Repository stanza for a given repository URL
     *
     * @param string $url Repository URL
     *
     * @return \stdClass
     * @throws Exception
     */
    public function findRepositoryByUrl($url)
    {
        if (!isset($this->contents->repositories)) {
            throw new Exception('composer.json expects there to be a repositories array. None found.');
        }

        $repositories = $this->contents->repositories;
        if (!is_array($repositories)) {
            throw new Exception('Invalid format for repositories. Expecting an array');
        }

        foreach ($repositories as $repo) {
            if (isset($repo->url) && $repo->url === $url) {
                return $repo;
            }
        }
        throw new Exception('Could not find repository in composer.json for url: ' . $url);
    }
}
