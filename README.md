# Amylian DeepClone

Copyright (c) 2018, [Andreas Prucha (Abexto - Helicon Software Development)](http://www.abexto.com]) <andreas.prucha@gmail.com>

## Status

| Version | Travis Result |
| ------- | ----------------- |
| V 0.1.0 | ![V 0.1.0 Status](https://api.travis-ci.org/amylian/amylian-deepclone.svg?branch=v0.1.0)
| Master  | ![Master Status](https://travis-ci.org/amylian/amylian-deepclone.svg?branch=master) |

## Function Summary

DeepClone is a package providing deep cloning (copying) of object instances in PHP.

Contrary to PHP's own clone functionality it does __not__ rely on the class implementing it's cloning in the magic function`__clone()`.


## Installation

To install this library, run the command below and you will get the latest version

``` bash
composer require amylian/deepclone --dev
```

## Basic Usage


DeepClone can be configured to handle special cases (e.g. instances of classes to be excluded from cloning in the instance tree), but in most cases this is not necessary. The straight forward use of DeepClone is:

```php
$clonedObject = \Amylian\DeepClone\DeepClone::copy($originalObject);
```

## Links

[Amylian/DeepClone Wiki on GitHub](https://github.com/amylian/amylian-deepclone/wiki)

