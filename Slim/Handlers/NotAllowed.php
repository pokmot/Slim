<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Body;

/**
 * Default Slim application not allowed handler
 *
 * It outputs a simple message in either JSON, XML or HTML based on the
 * Accept header.
 */
class NotAllowed
{
    /**
     * Invoke error handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     * @param  string[]               $methods  Allowed HTTP methods
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $methods)
    {
        if ($request->getMethod() === 'OPTIONS') {
            $status = 200;
            $contentType = 'text/plain';
            $output = $this->renderPlainNotAllowedMessage($methods, $request);
        } else {
            $status = 405;
            $contentType = $this->determineContentType($request->getHeaderLine('Accept'));
            switch ($contentType) {
                case 'application/json':
                    $output = $this->renderJsonNotAllowedMessage($methods, $request);
                    break;

                case 'text/xml':
                case 'application/xml':
                    $output = $this->renderXmlNotAllowedMessage($methods, $request);
                    break;

                case 'text/html':
                default:
                    $output = $this->renderHtmlNotAllowedMessage($methods, $request);
                    break;
            }
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);
        $allow = implode(', ', $methods);

        return $response
                ->withStatus($status)
                ->withHeader('Content-type', $contentType)
                ->withHeader('Allow', $allow)
                ->withBody($body);
    }

    /**
     * Read the accept header and determine which content type we know about
     * is wanted.
     *
     * @param  string $acceptHeader Accept header from request
     * @return string
     */
    private function determineContentType($acceptHeader)
    {
        $list = explode(',', $acceptHeader);
        $known = ['application/json', 'application/xml', 'text/xml', 'text/html'];

        foreach ($list as $type) {
            if (in_array($type, $known)) {
                return $type;
            }
        }

        return 'text/html';
    }

    /**
     * Render PLAIN not allowed message
     *
     * @param  array                  $methods
     * @param  ServerRequestInterface $request
     * @return string
     */
    protected function renderPlainNotAllowedMessage($methods, $request)
    {
        $allow = implode(', ', $methods);

        return 'Allowed methods: ' . $allow;
    }

    /**
     * Render JSON not allowed message
     *
     * @param  array                  $methods
     * @param  ServerRequestInterface $request
     * @return string
     */
    protected function renderJsonNotAllowedMessage($methods, $request)
    {
        $allow = implode(', ', $methods);

        return '{"message":"Method not allowed. Must be one of: ' . $allow . '"}';
    }

    /**
     * Render XML not allowed message
     *
     * @param  array                  $methods
     * @param  ServerRequestInterface $request
     * @return string
     */
    protected function renderXmlNotAllowedMessage($methods, $request)
    {
        $allow = implode(', ', $methods);

        return "<root><message>Method not allowed. Must be one of: $allow</message></root>";
    }

    /**
     * Render HTML not allowed message
     *
     * @param  array                  $methods
     * @param  ServerRequestInterface $request
     * @return string
     */
    protected function renderHtmlNotAllowedMessage($methods, $request)
    {
        $allow = implode(', ', $methods);
        $output = <<<END
<html>
    <head>
        <title>Method not allowed</title>
        <style>
            body{
                margin:0;
                padding:30px;
                font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;
            }
            h1{
                margin:0;
                font-size:48px;
                font-weight:normal;
                line-height:48px;
            }
        </style>
    </head>
    <body>
        <h1>Method not allowed</h1>
        <p>Method not allowed. Must be one of: <strong>$allow</strong></p>
    </body>
</html>
END;

        return $output;
    }
}
