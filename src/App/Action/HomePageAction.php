<?php

namespace App\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Router;
use Zend\Expressive\Template;
use Zend\Expressive\Plates\PlatesRenderer;
use Zend\Expressive\Twig\TwigRenderer;
use Zend\Expressive\ZendView\ZendViewRenderer;

class HomePageAction implements ServerMiddlewareInterface
{
    private $router;

    private $template;

    public function __construct(Router\RouterInterface $router, Template\TemplateRendererInterface $template = null)
    {
        $this->router = $router;
        $this->template = $template;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $query = $request->getQueryParams();
        if (array_key_exists("ajax", $query)) {
            try {
                return $this->ajax($request);
            } catch (\Exception $e) {
                return new JsonResponse(['exception' => $e->getMessage()]);
            }
        }

        return new HtmlResponse($this->template->render('app::home-page'));
    }

    public function ajax(ServerRequestInterface $request)
    {
        /** @var \SQLite3 $con */
        $con = $request->getAttribute("con");
        $query = $request->getQueryParams();
        $params = $request->getParsedBody();
        switch ($query['ajax']) {
            case "loadRequests":
                $res = $con->query("SELECT * FROM request ORDER BY id DESC LIMIT 100 ");
                while ($item = $res->fetchArray(SQLITE3_ASSOC)) {
                    $requests[] = $item;
                }

                return new JsonResponse($requests);
                break;
            case "searchRequest":

                $res = $con->query("SELECT * FROM request WHERE url LIKE('%" . $params['request'] . "%')");
                $requests = [];
                while ($item = $res->fetchArray(SQLITE3_ASSOC)) {
                    $requests[] = $item;
                }
                if (count($requests) == 0) {
                    throw new \Exception("0 registros");
                }

                return new JsonResponse($requests);
                break;
        }

        throw new \Exception("Comando não encontrado");
    }
}
