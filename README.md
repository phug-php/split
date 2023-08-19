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

## Example

https://github.com/phug-php/phug
