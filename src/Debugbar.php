<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use DebugBar\DebugBar as Bar;
use DebugBar\StandardDebugBar;

class Debugbar implements MiddlewareInterface
{
    private static $mimes = [
        'css' => 'text/css',
        'js' => 'text/javascript',
    ];

    /**
     * @var Bar|null The debugbar
     */
    private $debugbar;

    /**
     * @var bool Whether send data using headers in ajax requests
     */
    private $captureAjax = false;

    /**
     * Constructor. Set the debug bar.
     *
     * @param Bar|null $debugbar
     */
    public function __construct(Bar $debugbar = null)
    {
        $this->debugbar = $debugbar;
    }

    /**
     * Configure whether capture ajax requests to send the data with headers.
     *
     * @param bool $captureAjax
     *
     * @return self
     */
    public function captureAjax($captureAjax = true)
    {
        $this->captureAjax = $captureAjax;

        return $this;
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $debugbar = $this->debugbar ?: new StandardDebugBar();
        $renderer = $debugbar->getJavascriptRenderer();

        //Asset response
        $path = $request->getUri()->getPath();
        $baseUrl = $renderer->getBaseUrl();

        if (strpos($path, $baseUrl) === 0) {
            $file = $renderer->getBasePath().substr($path, strlen($baseUrl));

            if (file_exists($file)) {
                $response = Utils\Factory::createResponse();
                $response->getBody()->write(file_get_contents($file));
                $extension = pathinfo($file, PATHINFO_EXTENSION);

                if (isset(self::$mimes[$extension])) {
                    return $response->withHeader('Content-Type', self::$mimes[$extension]);
                }

                return $response;
            }
        }

        $response = $delegate->process($request);

        $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

        //Redirection response
        if (in_array($response->getStatusCode(), [302, 301])) {
            if ($this->debugbar->isDataPersisted() || session_status() === PHP_SESSION_ACTIVE) {
                $this->debugbar->stackData();
            }

            return $response;
        }

        //Html response
        if (stripos($response->getHeaderLine('Content-Type'), 'text/html') === 0) {
            $html = (string) $response->getBody();

            if (!$isAjax) {
                $html = self::injectHtml($html, $renderer->renderHead(), '</head>');
            }

            $html = self::injectHtml($html, $renderer->render(!$isAjax), '</body>');

            $body = Utils\Factory::createStream();
            $body->write($html);

            return $response->withBody($body);
        }

        //Ajax response
        if ($isAjax && $this->captureAjax) {
            $headers = $debugbar->getDataAsHeaders();

            foreach ($headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    /**
     * Inject html code before a tag.
     *
     * @param string $html
     * @param string $code
     * @param string $before
     *
     * @return ResponseInterface
     */
    private static function injectHtml($html, $code, $before)
    {
        $pos = strripos($html, $before);

        if ($pos === false) {
            return $html.$code;
        }

        return substr($html, 0, $pos).$code.substr($html, $pos);
    }
}
