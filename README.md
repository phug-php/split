# Phug Split

[![Latest Stable Version](https://img.shields.io/packagist/v/phug/split.svg)](https://packagist.org/packages/phug/split)
[![Tests](https://github.com/phug-php/split/actions/workflows/tests.yml/badge.svg)](https://github.com/phug-php/split/actions/workflows/tests.yml)

**Split** is a tool to handle a mono-repo that can be downloaded as single package,
multiple packages or both.

## Install

```shell script
composer require phug/split
```

## Usage

Put **composer.json** files in sub-directory of a main package repository.

```shell script
vendor/bin/split update
```

Check options with:
```shell script
vendor/bin/split update --help
```

Check other commands with:
```shell script
vendor/bin/split --help
```

Passing GIT authentication via environment variables:

Export env var such as:
```shell script
REPOSITORY_CREDENTIALS=my-git-username:my-authentication-token
```
```shell script
vendor/bin/split update --git-credentials=$REPOSITORY_CREDENTIALS
```

## Example

[phug-php/phug](https://github.com/phug-php/phug) is a library that can be required either entirely with `phug/phug` or as separated packages
`phug/parser`, `phug/compiler`, etc.

Each package is defined by a directory in [src/Phug](https://github.com/phug-php/phug/tree/master/src/Phug) with a dedicated `composer.json`
file inside each of them.

Once you created a GitHuba (or other git publisher) repository, and registered each of them on [packagist.org](https://packagist.org/)
(you can submit repository with just a single composer.json file with basic infos, the split will insert the actual content later).

You can now simply run from the monorepository the `vendor/bin/split update` or run this automatically from a hook or a job.

For instance `phug/phug` uses GitHub Actions to trigger it automatically after each commit:
[.github/workflows/split.yml](https://github.com/phug-php/phug/blob/master/.github/workflows/split.yml)

It relies on very few steps:
- Load PHP via `shivammathur/setup-php@v2`
- Cache dependencies folder with `actions/cache@v2` to make the job faster (optional)
- Install `phug/split` with:
  ```shell script
  test -f composer.json && mv composer.json composer.json.save -f
  composer require phug/split --no-interaction
  test -f composer.json.save && mv composer.json.save composer.json -f
  ```
  (`mv` commands are optional, it's here to install only `phug/split` as your other dependencies are not needed for this job)
- Run the `split update` command
  ```yaml
  - name: Split monorepository
    run: vendor/bin/split update --git-credentials=$REPOSITORY_CREDENTIALS
    env:
      REPOSITORY_CREDENTIALS: ${{ secrets.REPOSITORY_CREDENTIALS }}
  ```
  Here we pass git credentials using the `git-credentials` as the job runner will not have an authenticaed git user able to
  push to your repositories.

  The authentication variable is you git username, followed by `:`, followed by an authentication token you can generate
  via the page https://github.com/settings/tokens
  (See documentation: [Managing your personal access tokens](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens)).

  Don't put this clear token directly in the GitHub Action file, use a repository secret
  (See documentation: [Encrypted secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets))
  to keep it private.
