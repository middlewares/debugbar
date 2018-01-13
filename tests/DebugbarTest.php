<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use DebugBar\StandardDebugBar;
use Middlewares\Debugbar;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class DebugbarTest extends TestCase
{
    public function debugBarProvider(): array
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
    public function testDebugbar(string $contentType, array $headers, bool $expectedBody, bool $expectedHeader)
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

    public function testAsset()
    {
        $debugbar = new StandardDebugBar();
        $renderer = $debugbar->getJavascriptRenderer();
        $file = '/vendor/highlightjs/highlight.pack.js';

        $response = Dispatcher::run(
            [
                new Debugbar($debugbar),
                function () {
                    return Factory::createResponse(404);
                },
            ],
            Factory::createServerRequest([], 'GET', $renderer->getBaseUrl().$file)
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(file_get_contents($renderer->getBasePath().$file), $body);
    }

    public function testRedirection()
    {
        session_start();

        $response = Dispatcher::run(
            [
                new Debugbar(),
                function () {
                    return Factory::createResponse(302)
                        ->withHeader('Location', '/new-url');
                },
            ],
            Factory::createServerRequest()
        );

        session_write_close();

        $this->assertEquals(302, $response->getStatusCode());
    }
}
