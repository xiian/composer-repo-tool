<?php
namespace xiian\ComposerRepoTool\ComposerFile;

/**
 * Wrapper for composer.lock file
 */
class ComposerLock extends ComposerFile
{
    /**
     * Get a package object for the package of the given name
     *
     * @param string $name
     *
     * @return \stdClass
     * @throws Exception
     */
    public function getSpecForPackage($name)
    {
        foreach (['packages', 'packages-dev'] as $packages) {
            if (!isset($this->contents->$packages)) {
                continue;
            }
            /** @var array $package_specs */
            $package_specs = $this->contents->$packages;
            if (!is_array($package_specs)) {
                continue;
            }
            foreach ($package_specs as $package_spec) {
                if ($package_spec->name === $name) {
                    return $package_spec;
                }
            }
        }
        throw new Exception(sprintf('Could not find package spec for %s in %s', $name, $this->path));
    }

    /**
     * Iterate over all packages that do not have a 'dist' property
     *
     * @return \Generator
     */
    public function packagesWithoutDist()
    {
        foreach (['packages', 'packages-dev'] as $packages) {
            if (!isset($this->contents->$packages)) {
                continue;
            }
            /** @var array $package_specs */
            $package_specs = $this->contents->$packages;
            if (!is_array($package_specs)) {
                continue;
            }
            foreach ($package_specs as $package_spec) {
                if (isset($package_spec->dist)) {
                    continue;
                }
                yield $package_spec;
            }
        }
    }
}
