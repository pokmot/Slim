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
 * Default Slim application not found handler.
 *
 * It outputs a simple message in either JSON, XML or HTML based on the
 * Accept header.
 */
class NotFound
{
    /**
     * Invoke not found handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {

        $contentType = $this->determineContentType($request->getHeaderLine('Accept'));
        switch ($contentType) {
            case 'application/json':
                $output = $this->renderJsonNotFoundMessage($request);
                break;

            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXmlNotFoundMessage($request);
                break;

            case 'text/html':
            default:
                $output = $this->renderHtmlNotFoundMessage($request);
                break;
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response->withStatus(404)
                        ->withHeader('Content-Type', $contentType)
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
     * Render JSON not allowed message
     *
     * @param  ServerRequestInterface $request
     * @return string
     */
    protected function renderJsonNotFoundMessage($request)
    {
        return '{"message":"Not found"}';
    }

    /**
     * Render XML not allowed message
     *
     * @param  ServerRequestInterface $request
     * @return string
     */
    protected function renderXmlNotFoundMessage($request)
    {
        return '<root><message>Not found</message></root>';
    }

    /**
     * Render HTML not allowed message
     *
     * @param  ServerRequestInterface $request
     * @return string
     */
    protected function renderHtmlNotFoundMessage($request)
    {
        $homeUrl = (string)($request->getUri()->withPath('')->withQuery('')->withFragment(''));
        $output = <<<END
<html>
    <head>
        <title>Page Not Found</title>
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
            strong{
                display:inline-block;
                width:65px;
            }
        </style>
    </head>
    <body>
        <h1>Page Not Found</h1>
        <p>
            The page you are looking for could not be found. Check the address bar
            to ensure your URL is spelled correctly. If all else fails, you can
            visit our home page at the link below.
        </p>
        <a href='$homeUrl'>Visit the Home Page</a>
    </body>
</html>
END;

        return $output;
    }
}
