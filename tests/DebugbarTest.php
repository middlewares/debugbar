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
    /**
     * @return array<array<mixed>>
     */
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
     * @param array<string,string> $headers
     */
    public function testDebugbar(string $contentType, array $headers, bool $expectedBody, bool $expectedHeader): void
    {
        $request = Factory::createServerRequest('GET', '/');

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $response = Dispatcher::run([
            (new Debugbar())->captureAjax(),
            function () use ($contentType) {
                return Factory::createResponse()
                    ->withHeader('Content-Type', $contentType);
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
    }

    public function testInline(): void
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

    public function testAsset(): void
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
            Factory::createServerRequest('GET', $renderer->getBaseUrl().$file)
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(file_get_contents($renderer->getBasePath().$file), $body);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRedirection(): void
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
            Factory::createServerRequest('GET', '/')
        );

        session_write_close();

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testRenderOptions(): void
    {
        $response = Dispatcher::run(
            [
                (new Debugbar())
                    ->renderOptions(['base_url' => '/custom-url/']),
                function () {
                    return Factory::createResponse()
                        ->withHeader('Content-Type', 'text/html');
                },
            ]
        );

        $body = (string) $response->getBody();

        $this->assertNotFalse(strpos($body, '/custom-url/'));
    }
}
