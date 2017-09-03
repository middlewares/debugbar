<?php

namespace Middlewares\Tests;

use Middlewares\Debugbar;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class DebugbarTest extends TestCase
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
     * @param mixed $contentType
     * @param mixed $expectedBody
     * @param mixed $expectedHeader
     */
    public function testDebugbar($contentType, array $headers, $expectedBody, $expectedHeader)
    {
        $request = Factory::createServerRequest();

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $response = Dispatcher::run([
            (new Debugbar())->captureAjax(),
            function () use ($contentType) {
                return Factory::createResponse()
                    ->withHeader('Content-Type', $contentType)
                    ->withHeader('Content-Length', '0');
            },
        ], $request);

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

        $this->assertEquals(strlen($body), (int) $response->getHeaderLine('Content-Length'));
    }

    public function testInline()
    {
        $response = Dispatcher::run([
            (new Debugbar())->inline(),
            function () {
                echo '<html><head></head><body></body></html>';

                return Factory::createResponse()
                    ->withHeader('Content-Type', 'text/html');
            },
        ]);

        $body = (string) $response->getBody();

        $this->assertNotFalse(strpos($body, '</script>'));
        $this->assertNotFalse(strpos($body, '</style>'));
    }
}
