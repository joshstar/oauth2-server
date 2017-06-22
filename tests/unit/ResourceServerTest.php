<?php

namespace joshstarTests;

use joshstar\OAuth2\Server\Entity\AccessTokenEntity;
use joshstar\OAuth2\Server\Entity\ClientEntity;
use joshstar\OAuth2\Server\Entity\ScopeEntity;
use joshstar\OAuth2\Server\Entity\SessionEntity;
use joshstar\OAuth2\Server\ResourceServer;
use Mockery as M;

class ResourceServerTest extends \PHPUnit_Framework_TestCase
{
    private function returnDefault()
    {
        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');

        $server = new ResourceServer(
            $sessionStorage,
            $accessTokenStorage,
            $clientStorage,
            $scopeStorage
        );

        return $server;
    }

    public function testGetSet()
    {
        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');

        $server = new ResourceServer(
            $sessionStorage,
            $accessTokenStorage,
            $clientStorage,
            $scopeStorage
        );
    }

    public function testDetermineAccessTokenMissingToken()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('get')->andReturn(false);

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');

        $server = new ResourceServer(
            $sessionStorage,
            $accessTokenStorage,
            $clientStorage,
            $scopeStorage
        );

        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->headers = new \Symfony\Component\HttpFoundation\ParameterBag([
            'HTTP_AUTHORIZATION' =>  'Bearer',
        ]);
        $server->setRequest($request);

        $reflector = new \ReflectionClass($server);
        $method = $reflector->getMethod('determineAccessToken');
        $method->setAccessible(true);

        $method->invoke($server);
    }

    public function testIsValidNotValid()
    {
        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('get')->andReturn(false);

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');

        $server = new ResourceServer(
            $sessionStorage,
            $accessTokenStorage,
            $clientStorage,
            $scopeStorage
        );

        $this->setExpectedException('joshstar\OAuth2\Server\Exception\AccessDeniedException');
        $server->isValidRequest(false, 'foobar');
    }

    public function testIsValid()
    {
        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');

        $server = new ResourceServer(
            $sessionStorage,
            $accessTokenStorage,
            $clientStorage,
            $scopeStorage
        );

        $server->setIdKey('at');

        $server->addEventListener('session.owner', function ($event) {
            $this->assertTrue($event->getSession() instanceof \joshstar\OAuth2\Server\Entity\SessionEntity);
        });

        $accessTokenStorage->shouldReceive('get')->andReturn(
            (new AccessTokenEntity($server))->setId('abcdef')->setExpireTime(time() + 300)
        );

        $accessTokenStorage->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
            (new ScopeEntity($server))->hydrate(['id' => 'bar']),
        ]);

        $sessionStorage->shouldReceive('getByAccessToken')->andReturn(
            (new SessionEntity($server))->setId('foobar')->setOwner('user', 123)
        );

        $clientStorage->shouldReceive('getBySession')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->headers = new \Symfony\Component\HttpFoundation\ParameterBag([
            'Authorization' =>  'Bearer abcdef',
        ]);
        $server->setRequest($request);

        $this->assertTrue($server->isValidRequest());
        $this->assertEquals('abcdef', $server->getAccessToken());
    }

    /**
     * @expectedException joshstar\OAuth2\Server\Exception\AccessDeniedException
     */
    public function testIsValidExpiredToken()
    {
        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');

        $server = new ResourceServer(
            $sessionStorage,
            $accessTokenStorage,
            $clientStorage,
            $scopeStorage
        );

        $server->setIdKey('at');

        $server->addEventListener('session.owner', function ($event) {
            $this->assertTrue($event->getSession() instanceof \joshstar\OAuth2\Server\Entity\SessionEntity);
        });

        $accessTokenStorage->shouldReceive('get')->andReturn(
            (new AccessTokenEntity($server))->setId('abcdef')->setExpireTime(time() - 300)
        );

        $accessTokenStorage->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
            (new ScopeEntity($server))->hydrate(['id' => 'bar']),
        ]);

        $sessionStorage->shouldReceive('getByAccessToken')->andReturn(
            (new SessionEntity($server))->setId('foobar')->setOwner('user', 123)
        );

        $clientStorage->shouldReceive('getBySession')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->headers = new \Symfony\Component\HttpFoundation\ParameterBag([
            'Authorization' =>  'Bearer abcdef',
        ]);
        $server->setRequest($request);

        $server->isValidRequest();
    }
}
