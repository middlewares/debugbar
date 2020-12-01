# middlewares/debugbar

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Testing][ico-ga]
[![Total Downloads][ico-downloads]][link-downloads]

Middleware to insert [PHP DebugBar](http://phpdebugbar.com) automatically in html responses.

## Requirements

* PHP >= 7.2
* A [PSR-7 http library](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
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

## Usage

You can provide a `DebugBar\DebugBar` instance to the constructor or an instance of `DebugBar\StandardDebugBar` will be created automatically. Optionally, you can provide a `Psr\Http\Message\ResponseFactoryInterface` and `Psr\Http\Message\StreamFactoryInterface` to create the new responses. If it's not defined, [Middleware\Utils\Factory](https://github.com/middlewares/utils#factory) will be used to detect it automatically.

```php
//Create a StandardDebugBar automatically
$debugbar = new Middlewares\Debugbar();

//Use other Debugbar instance
$debugbar = new Middlewares\Debugbar($myDebugbar);

//Use other Debugbar instance and PSR-17 factories
$debugbar = new Middlewares\Debugbar($myDebugbar, $myResponseFactory, $myStreamFactory);
```

### captureAjax

Use this option to capture ajax requests and send the data in the headers. [More info about AJAX and Stacked data](http://phpdebugbar.com/docs/ajax-and-stack.html#ajax-and-stacked-data). By default it's disabled.

```php
$debugbar = (new Middlewares\Debugbar())->captureAjax();
```

### inline

Set true to dump the js/css code inline in the html. This fixes (or mitigate) some issues related with loading the debugbar assets.

```php
$debugbar = (new Middlewares\Debugbar())->inline();
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/debugbar.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ga]: https://github.com/middlewares/debugbar/workflows/testing/badge.svg
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/debugbar.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/debugbar
[link-downloads]: https://packagist.org/packages/middlewares/debugbar
