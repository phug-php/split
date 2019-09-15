# Phug Split

[![Latest Stable Version](https://img.shields.io/packagist/v/phug/split.svg?style=flat-square)](https://packagist.org/packages/phug/split)
[![Build Status](https://img.shields.io/travis/phug-php/split/master.svg?style=flat-square)](https://travis-ci.org/phug-php/split)

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
