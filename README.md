# bin-wrapper

[![Latest Stable Version](https://poser.pugx.org/itgalaxy/bin-wrapper/v/stable)](https://packagist.org/packages/itgalaxy/bin-wrapper)
[![Travis Build Status](https://img.shields.io/travis/itgalaxy/bin-wrapper/master.svg?label=build)](https://travis-ci.org/itgalaxy/bin-wrapper)
[![Build status](https://ci.appveyor.com/api/projects/status/immgvmhqmj3rv53t?svg=true)](https://ci.appveyor.com/project/evilebottnawi/bin-wrapper)

Binary wrapper that makes your programs seamlessly available as local dependencies

## Install

The utility can be installed with Composer:

```shell
$ composer require bin-wrapper
```

## Usage

```php
<?php
use Itgalaxy\BinWrapper\BinWrapper;

$url = 'https://github.com/itgalaxy/pngquant-bin/raw/master/bin-vendor';
$platform = strtolower(PHP_OS);
$binWrapper = new BinWrapper();
$binWrapper
    ->src($url . '/freebsd/x64/pngquant', 'darwin', 'x64')
    ->src($url . '/linux/x64/pngquant', 'linux', 'x64')
    ->src($url . '/linux/x86/pngquant', 'linux', 'x86')
    ->src($url . '/macos/pngquant', 'darwin')
    ->src($url . '/win/pngquant.exe', 'windowsnt')
    ->dest(__DIR__ . '/vendor-bin')
    ->using(substr($platform, 0, 3) === 'win' ? 'pngquant.exe' : 'pngquant')
    ->version('>=1.71');

$binWrapper->run(['--version']); // You can use `try {} catch {}` for catching exceptions
```

Get the path to your binary with `$binWrapper->path()`:

```php
<?php
echo $binWrapper->path();
```

## API

Coming soon

## Related

- [bin-wrapper](https://github.com/kevva/bin-wrapper) - Thanks you for inspiration.

## Contribution

Feel free to push your code if you agree with publishing under the MIT license.

## [Changelog](CHANGELOG.md)

## [License](LICENSE)
