<?php
/**
 * Created by PhpStorm.
 * User: anderson
 * Date: 12/08/17
 * Time: 12:54
 */

namespace App\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Session\Container;
use Zend\Session\SessionManager;
use Zend\Session\Validator\HttpUserAgent;
use Zend\Session\Validator\RemoteAddr;

class DatabaseCheckMiddleware implements ServerMiddlewareInterface
{
    private $template;

    public function __construct(TemplateRendererInterface $template)
    {
        $this->template = $template;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $database = __DIR__ . "/../../../data/database.sql";

        $create = false;
        if (!file_exists($database)) {
            $create = true;
        }
        $con = new \SQLite3($database);
        if ($create) {
            $this->createStructure($con);
        }

        $request = $request->withAttribute("con", $con);

        return $delegate->process($request);
    }

    /**
     * @param \SQLite3 $con
     */
    public function createStructure($con)
    {
        $table = 'CREATE TABLE request (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    method TEXT,
                    url TEXT,
                    headers TEXT,
                    body TEXT,
                    created_at DATETIME
                  )';

        $res = $con->exec($table);
        if ($res == false) {
            throw new \Exception("Não foi possível criar o SQLITE");
        }
    }

}