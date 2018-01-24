# middlewares/debugbar

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to insert [PHP DebugBar](http://phpdebugbar.com) automatically in html responses.

## Requirements

* PHP >= 7.0
* A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http message implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/debugbar](https://packagist.org/packages/middlewares/debugbar).

```sh
composer require middlewares/debugbar
```

## Example

```php
$dispatcher = new Dispatcher([
	new Middlewares\Debugbar()
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

## Options

#### `__construct(DebugBar\DebugBar $debugbar = null)`

To use a custom DebugBar instance. If it's not defined, an intance of `DebugBar\StandardDebugBar` will be created.

#### `captureAjax(bool $captureAjax = true)`

Set true to capture ajax requests and send the data in the headers (disabled by default).

#### `inline(bool $inline = true)`

Set true to dump the js/css code inline in the html.

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/debugbar.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/debugbar/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/debugbar.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/debugbar.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/e84e852f-9ac2-4cd7-9c8b-15021497abca.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/debugbar
[link-travis]: https://travis-ci.org/middlewares/debugbar
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/debugbar
[link-downloads]: https://packagist.org/packages/middlewares/debugbar
[link-sensiolabs]: https://insight.sensiolabs.com/projects/e84e852f-9ac2-4cd7-9c8b-15021497abca
