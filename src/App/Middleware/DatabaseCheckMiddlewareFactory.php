<?php

namespace App\Middleware;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use App\Middleware\DatabaseCheckMiddleware;

class DatabaseCheckMiddlewareFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {

        return new DatabaseCheckMiddleware($container->get(\Zend\Expressive\Template\TemplateRendererInterface::class));
    }
}
