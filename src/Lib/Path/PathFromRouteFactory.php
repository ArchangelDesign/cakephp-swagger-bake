<?php

namespace SwaggerBake\Lib\Path;

use Exception;
use phpDocumentor\Reflection\DocBlock;
use SwaggerBake\Lib\Configuration;
use SwaggerBake\Lib\Model\ExpressiveRoute;
use SwaggerBake\Lib\OpenApi\Path;
use SwaggerBake\Lib\Utility\DocBlockUtility;
use SwaggerBake\Lib\Utility\NamespaceUtility;

class PathFromRouteFactory
{
    /** @var ExpressiveRoute */
    private $route;

    /** @var Configuration */
    private $config;

    public function __construct(ExpressiveRoute $route, Configuration $config)
    {
        $this->config = $config;
        $this->route = $route;
    }

    /**
     * Creates a Path if possible, otherwise returns null
     *
     * @return Path|null
     */
    public function create() : ?Path
    {
        $path = new Path();

        if (empty($this->route->getMethods())) {
            return null;
        }

        $docBlock = $this->getDocBlock();

        $path
            ->setResource($this->getResourceName())
            ->setSummary($docBlock ? $docBlock->getSummary() : '')
            ->setDescription($docBlock ? $docBlock->getDescription() : '')
        ;

        return $path;
    }

    /**
     * @return DocBlock|null
     */
    private function getDocBlock() : ?DocBlock
    {
        if (empty($this->route->getController())) {
            return null;
        }

        $className = $this->route->getController() . 'Controller';
        $methodName = $this->route->getAction();
        $controller = NamespaceUtility::getController($className, $this->config);

        if (!class_exists($controller)) {
            return null;
        }

        try {
            return DocBlockUtility::getMethodDocBlock(new $controller, $methodName);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Returns a routes resource (e.g. /api/model/action)
     *
     * @return string
     */
    private function getResourceName() : string
    {
        $pieces = $this->getRoutablePieces();

        if ($this->config->getPrefix() == '/') {
            return implode('/', $pieces);
        }

        return substr(
            implode('/', $pieces),
            strlen($this->config->getPrefix())
        );
    }

    /**
     * Splits the route (URL) into pieces with forward-slash "/" as  the separator after removing path variables
     *
     * @return string[]
     */
    private function getRoutablePieces() : array
    {
        return array_map(
            function ($piece) {
                if (substr($piece, 0, 1) == ':') {
                    return '{' . str_replace(':', '', $piece) . '}';
                }
                return $piece;
            },
            explode('/', $this->route->getTemplate())
        );
    }
}