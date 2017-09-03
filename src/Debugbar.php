<?php

namespace Middlewares;

use DebugBar\DebugBar as Bar;
use DebugBar\StandardDebugBar;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Middlewares\Utils\Helpers;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @var bool Whether dump the css/js code inline in the html
     */
    private $inline = false;

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
     * Configure whether the js/css code should be inserted inline in the html.
     *
     * @param bool $inline
     *
     * @return self
     */
    public function inline($inline = true)
    {
        $this->inline = $inline;

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
            if ($debugbar->isDataPersisted() || session_status() === PHP_SESSION_ACTIVE) {
                $debugbar->stackData();
            }

            return $response;
        }

        //Html response
        if (stripos($response->getHeaderLine('Content-Type'), 'text/html') === 0) {
            $html = (string) $response->getBody();

            if (!$isAjax) {
                if ($this->inline) {
                    ob_start();
                    echo "<style>\n";
                    $renderer->dumpCssAssets();
                    echo "\n</style>";
                    echo "<script>\n";
                    $renderer->dumpJsAssets();
                    echo "\n</script>";
                    $code = ob_get_clean();
                } else {
                    $code = $renderer->renderHead();
                }

                $html = self::injectHtml($html, $code, '</head>');
            }

            $html = self::injectHtml($html, $renderer->render(!$isAjax), '</body>');

            $body = Utils\Factory::createStream();
            $body->write($html);

            return Helpers::fixContentLength($response->withBody($body));
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
