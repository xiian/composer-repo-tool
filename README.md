# composer-repo-tool

[![Build Status](http://img.shields.io/travis/xiian/composer-repo-tool.svg)](https://travis-ci.org/xiian/composer-repo-tool)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/xiian/composer-repo-tool.svg)](https://scrutinizer-ci.com/g/xiian/composer-repo-tool/?branch=master)
[![License](http://img.shields.io/badge/license-MIT-lightgrey.svg)](https://github.com/xiian/composer-repo-tool/blob/master/LICENSE.md)

Manage repositories defined inside your composer.json

Current functionality is limited to converting github repositories of type `git` to type `vcs` (which then enables composer to download archive dists instead of cloning every time).

It is also heavily opinionated for the use case I had when writing it, so your mileage may vary and pull requests welcome.

## Installation
If you're even thinking of using this tool, you're already familiar with composer. It's all pretty standard.

```bash
$ composer require --dev xiian/composer-repo-tool
```

This will install `./vendor/bin/composer-repo-tool` for use inside your project.

You can also install globally:
```bash
$ composer global require --dev xiian/composer-repo-tool
```

Which installs into `$COMPOSER_HOME/vendor/bin/`, which should already be in your $PATH.

## Usage
To update a single package's repository:
```bash
$ composer-repo-tool update $VENDOR/$PACKAGE
```

To update all repositories:
```bash
$ composer-repo-tool update:all
```

You can also pass a `--dry-run` flag to see what the tool *would* do, without actually doing any of it.

## Under the Hood
The `update` command will perform the following steps for each package given:

1. Rewrite the `composer.json` `repositories` entry for the given package
    * Use `vcs` instead of `git`
    * Convert URL from `git@$GITHUB_URL:$USER/$REPO.git` to `https://$GITHUB_URL/$USER/$REPO.git`
1. Phsycially remove the package from the `vendor/` directory
1. Run `composer update` with proper params to switch to using `dist` instead of `source`
1. Perform a git commit of the `composer.lock` and `composer.json` files. (I told you this was opinionated)

The `update:all` command will perform the above steps for every single package that is installed that does not have a `dist` associated with it in the `composer.lock` file.

## TODO
* **Decouple the source control stuff.** I like the idea of being able to perform a task after every package has been updated (for atomicity), but not everybody wants the same thing.
* **Batch mode.** `update:all` is kind of slow because it's performing the full update cycle for every package. If atomicity isn't a concern, all of those updates could be done together, and `composer update` would only need to be run once, which would greatly speed things up.
* **Integrate with `composer` more directly.** Using the internals of `composer/composer` could speed things up a bit by skipping some likely redundant steps of `composer update`.
