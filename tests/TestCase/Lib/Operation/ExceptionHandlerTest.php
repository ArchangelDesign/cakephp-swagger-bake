<?php

namespace SwaggerBake\Test\TestCase\Lib\Operation;

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use phpDocumentor\Reflection\DocBlockFactory;
use SwaggerBake\Lib\Configuration;
use SwaggerBake\Lib\Factory\SwaggerFactory;
use SwaggerBake\Lib\Operation\ExceptionHandler;

class ExceptionHandlerTest extends TestCase
{
    public $fixtures = [
        'plugin.SwaggerBake.Employees',
    ];

    private $router;
    private $config;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $router = new Router();
        $router::scope('/', function (RouteBuilder $builder) {
            $builder->setExtensions(['json']);
            $builder->resources('Employees', [
                'only' => ['index','update']
            ]);
        });
        $this->router = $router;

        $this->config = [
            'prefix' => '/',
            'yml' => '/config/swagger-bare-bones.yml',
            'json' => '/webroot/swagger.json',
            'webPath' => '/swagger.json',
            'hotReload' => false,
            'exceptionSchema' => 'Exception',
            'requestAccepts' => ['application/x-www-form-urlencoded'],
            'responseContentTypes' => ['application/json'],
            'namespaces' => [
                'controllers' => ['\SwaggerBakeTest\App\\'],
                'entities' => ['\SwaggerBakeTest\App\\'],
                'tables' => ['\SwaggerBakeTest\App\\'],
            ]
        ];
    }

    public function testErrorCodes(): void
    {
        $config = new Configuration($this->config, SWAGGER_BAKE_TEST_APP);
        $swagger = (new SwaggerFactory($config))->create();

        $exceptions = [
            '400' => '\Cake\Http\Exception\BadRequestException',
            '401' => '\Cake\Http\Exception\UnauthorizedException',
            '403' => '\Cake\Http\Exception\ForbiddenException',
            '404' => '\Cake\Datasource\Exception\RecordNotFoundException',
            '405' => '\Cake\Http\Exception\MethodNotAllowedException',
            '500' => '\Exception'
        ];

        $factory = DocBlockFactory::createInstance();
        foreach ($exceptions as $code => $exception) {
            /** @var \phpDocumentor\Reflection\DocBlock\Tags\Throws $throws */
            $throws = $factory->create("/** @throws $exception */ */")->getTagsByName('throws')[0];
            $this->assertEquals($code, (new ExceptionHandler($throws, $swagger, $config))->getCode());
        }
    }

    public function testMessage(): void
    {
        $config = new Configuration($this->config, SWAGGER_BAKE_TEST_APP);
        $swagger = (new SwaggerFactory($config))->create();

        $factory = DocBlockFactory::createInstance();
        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Throws $throws */
        $throws = $factory->create("/** @throws \Exception description */")->getTagsByName('throws')[0];
        $this->assertEquals('description', (new ExceptionHandler($throws, $swagger, $config))->getMessage());
    }

    public function testSchema(): void
    {
        $config = new Configuration($this->config, SWAGGER_BAKE_TEST_APP);
        $swagger = (new SwaggerFactory($config))->create();

        $factory = DocBlockFactory::createInstance();
        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Throws $throws */
        $throws = $factory->create("/** @throws \Exception description */")->getTagsByName('throws')[0];
        $this->assertEquals(
            '#/components/schemas/Exception',
            (new ExceptionHandler($throws, $swagger, $config))->getSchema()
        );
    }

    public function testCustomSchema(): void
    {
        $config = new Configuration($this->config, SWAGGER_BAKE_TEST_APP);
        $swagger = (new SwaggerFactory($config))->create();

        $factory = DocBlockFactory::createInstance();
        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Throws $throws */

        $throws = $factory
            ->create("/** @throws \MixerApi\ExceptionRender\ValidationException */")
            ->getTagsByName('throws')[0];

        $this->assertEquals(
            '#/x-swagger-bake/components/schemas/app-exceptions/ValidationException',
            (new ExceptionHandler($throws, $swagger, $config))->getSchema()
        );
    }
}