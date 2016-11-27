<?php

namespace Middlewares\Tests;

use Middlewares\Debugbar;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\CallableMiddleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;

class DebugbarTest extends \PHPUnit_Framework_TestCase
{
    public function debugBarProvider()
    {
        return [
            ['text/html', [], true, false],
            ['application/json', [], false, false],
            ['text/html', ['X-Requested-With' => 'xmlhttprequest'], true, false],
            ['application/json', ['X-Requested-With' => 'xmlhttprequest'], false, true],
        ];
    }

    /**
     * @dataProvider debugBarProvider
     */
    public function testDebugbar($contentType, array $headers, $expectedBody, $expectedHeader)
    {
        $request = new ServerRequest([], [], '', 'GET', 'php://temp', $headers);

        $response = (new Dispatcher([
            (new Debugbar())->captureAjax(),
            new CallableMiddleware(function () use ($contentType) {
                return (new Response())
                    ->withHeader('Content-Type', $contentType);
            }),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);

        $body = (string) $response->getBody();

        if ($expectedBody) {
            $this->assertNotFalse(strpos($body, '</script>'));
        } else {
            $this->assertFalse(strpos($body, '</script>'));
        }

        if ($expectedHeader) {
            $this->assertTrue($response->hasHeader('phpdebugbar'));
        } else {
            $this->assertFalse($response->hasHeader('phpdebugbar'));
        }
    }
}
