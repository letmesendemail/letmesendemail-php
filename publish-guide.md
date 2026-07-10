# Publishing — letmesendemail-php

## Registry

[Packagist](https://packagist.org) — `letmesendemail/letmesendemail-php`

## How Versioning Works

Packagist derives the package version from the Git tag. Do not add a `version`
field to `composer.json`. The tag must match `vX.Y.Z` format (e.g., `v1.0.0`).

## Maintainer Prerequisites

1. An account on [packagist.org](https://packagist.org) that is a maintainer of the `letmesendemail/letmesendemail-php` package.
2. GitHub repository access with push permission for the `letmesendemail/letmesendemail-php` repository.
3. If using GitHub Actions for automated publishing, a Packagist API token stored as a repository secret named `PACKAGIST_TOKEN`.

## First-Time Setup

1. Go to [packagist.org/packages/submit](https://packagist.org/packages/submit)
2. Enter the repository URL: `https://github.com/letmesendemail/letmesendemail-php`
3. Packagist auto-detects the package from `composer.json`.
4. Enable auto-update in package settings so new tags publish automatically.
5. (Optional) Configure a GitHub Actions workflow or Packagist webhook for automated publishing.

## Pre-Release Validation

Before tagging a release, run all validation checks from the repository root:

```bash
composer validate --strict
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse
vendor/bin/pest
```

Fix any failures before proceeding.

## Releasing a Version

The SDK resolves its version from the Composer install metadata at runtime.
There is no VERSION constant or manifest field to update.

```bash
# 1. Ensure CHANGELOG.md is updated (move Unreleased entries to a new version section)
# 2. Commit all changes
# 3. Tag and push
git tag v<version>
git push origin v<version>
```

Packagist picks it up automatically when auto-update is enabled.
The User-Agent header will reflect the tag version without any manual change.

## Manual Publishing

If auto-update is not configured, update the package manually:

1. Go to `https://packagist.org/packages/letmesendemail/letmesendemail-php`
2. Click "Update" to fetch the latest tag.

## Verifying

```bash
composer require letmesendemail/letmesendemail-php
```

Then check that the installed version matches the released tag.

## Recovering a Broken Release

- **Packagist does not support deleting versions.** Instead, publish a patch release with the fix.
- If a version must be removed from visibility, use the "Disable" action on Packagist.
- For critical security issues, contact Packagist support to request removal.
