<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Tests;

use PHPUnit\Framework\TestCase;
use Silex\Application;
use Silex\ServiceControllerResolver;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit tests for ServiceControllerResolver, see ServiceControllerResolverRouterTest for some
 * integration tests.
 */
class ServiceControllerResolverTest extends Testcase
{
    private $app;
    private $mockCallbackResolver;
    private $mockResolver;
    private $resolver;

    public function setup(): void
    {
        $this->mockResolver = $this->getMockBuilder('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockCallbackResolver = $this->getMockBuilder('Silex\CallbackResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->app = new Application();
        $this->resolver = new ServiceControllerResolver($this->mockResolver, $this->mockCallbackResolver);
    }

    public function testShouldResolveServiceController()
    {
        $service = new class {
            public function methodName() { return new stdClass(); }
        };
        $this->app['some_service'] = $service;

        $this->mockCallbackResolver->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->mockCallbackResolver->expects($this->once())
            ->method('convertCallback')
            ->with('some_service:methodName')
            ->willReturnCallback([$service, 'methodName']);

        $req = Request::create('/');
        $req->attributes->set('_controller', 'some_service:methodName');

        $this->assertEquals($service, $this->resolver->getController($req));
    }

    public function testShouldUnresolvedControllerNames()
    {
        $req = Request::create('/');
        $req->attributes->set('_controller', 'some_class::methodName');

        $this->mockCallbackResolver->expects($this->once())
            ->method('isValid')
            ->with('some_class::methodName')
            ->willReturn(false);

        $this->mockResolver->expects($this->once())
            ->method('getController')
            ->with($req)
            ->willReturn(false);

        $this->assertEquals(false, $this->resolver->getController($req));
    }
}
