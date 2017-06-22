<?php

namespace LeagueForkTests\Entity;

use LeagueFork\OAuth2\Server\AuthorizationServer;
use LeagueFork\OAuth2\Server\Entity\ScopeEntity;
use LeagueFork\OAuth2\Server\Entity\SessionEntity;
use LeagueForkTests\Stubs\StubAbstractTokenEntity;
use Mockery as M;

class AbstractTokenEntityTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGet()
    {
        $server = M::mock('LeagueFork\OAuth2\Server\AbstractServer');
        $time = time();

        $entity = new StubAbstractTokenEntity($server);
        $entity->setId('foobar');
        $entity->setExpireTime($time);
        $entity->setSession((new SessionEntity($server)));
        $entity->associateScope((new ScopeEntity($server))->hydrate(['id' => 'foo']));

        $this->assertEquals('foobar', $entity->getId());
        $this->assertEquals($time, $entity->getExpireTime());
        // $this->assertTrue($entity->getSession() instanceof SessionEntity);
        // $this->assertTrue($entity->hasScope('foo'));

        // $result = $entity->getScopes();
        // $this->assertTrue(isset($result['foo']));
    }

    /*public function testGetSession()
    {
        $server = M::mock('LeagueFork\OAuth2\Server\AuthorizationServer');
        $server->shouldReceive('setSessionStorage');

        $sessionStorage = M::mock('LeagueFork\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('getByAccessToken')->andReturn(
            (new SessionEntity($server))
        );
        $sessionStorage->shouldReceive('setServer');

        $server->shouldReceive('getStorage')->andReturn($sessionStorage);

        $server->setSessionStorage($sessionStorage);

        $entity = new StubAbstractTokenEntity($server);
        $this->assertTrue($entity->getSession() instanceof SessionEntity);
    }*/

    /*public function testGetScopes()
    {
        $server = M::mock('LeagueFork\OAuth2\Server\AuthorizationServer');
        $server->shouldReceive('setAccessTokenStorage');

        $accessTokenStorage = M::mock('LeagueFork\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn(
            []
        );
        $accessTokenStorage->shouldReceive('setServer');

        $server->setAccessTokenStorage($accessTokenStorage);

        $entity = new StubAbstractTokenEntity($server);
        $this->assertEquals($entity->getScopes(), []);
    }*/

    /*public function testHasScopes()
    {
        $server = M::mock('LeagueFork\OAuth2\Server\AuthorizationServer');

        $accessTokenStorage = M::mock('LeagueFork\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn(
            []
        );
        $accessTokenStorage''>shouldReceive('setServer');

        $server->setAccessTokenStorage($accessTokenStorage);

        $entity = new StubAbstractTokenEntity($server);
        $this->assertFalse($entity->hasScope('foo'));
    }*/

    public function testFormatScopes()
    {
        $server = M::mock('LeagueFork\OAuth2\Server\AbstractServer');

        $entity = new StubAbstractTokenEntity($server);
        $reflectedEntity = new \ReflectionClass('LeagueForkTests\Stubs\StubAbstractTokenEntity');
        $method = $reflectedEntity->getMethod('formatScopes');
        $method->setAccessible(true);

        $scopes = [
            (new ScopeEntity($server))->hydrate(['id' => 'scope1', 'description' => 'foo']),
            (new ScopeEntity($server))->hydrate(['id' => 'scope2', 'description' => 'bar']),
        ];

        $result = $method->invokeArgs($entity, [$scopes]);

        $this->assertTrue(isset($result['scope1']));
        $this->assertTrue(isset($result['scope2']));
        $this->assertTrue($result['scope1'] instanceof ScopeEntity);
        $this->assertTrue($result['scope2'] instanceof ScopeEntity);
    }

    public function test__toString()
    {
        $server = M::mock('LeagueFork\OAuth2\Server\AbstractServer');

        $entity = new StubAbstractTokenEntity($server);
        $this->assertEquals('', (string) $entity);
        $entity->setId('foobar');
        $this->assertEquals('foobar', (string) $entity);
    }
}
