<?php

namespace joshstarTests\Grant;

use joshstar\OAuth2\Server\AuthorizationServer;
use joshstar\OAuth2\Server\Entity\AuthCodeEntity;
use joshstar\OAuth2\Server\Entity\ClientEntity;
use joshstar\OAuth2\Server\Entity\ScopeEntity;
use joshstar\OAuth2\Server\Entity\SessionEntity;
use joshstar\OAuth2\Server\Exception\InvalidRequestException;
use joshstar\OAuth2\Server\Grant\AuthCodeGrant;
use joshstar\OAuth2\Server\Grant\RefreshTokenGrant;
use Mockery as M;

class AuthCodeGrantTest extends \PHPUnit_Framework_TestCase
{
    public function testSetAuthTokenTTL()
    {
        $grant = new AuthCodeGrant();
        $grant->setAuthTokenTTL(100);

        $class = new \ReflectionClass($grant);
        $property = $class->getProperty('authTokenTTL');
        $property->setAccessible(true);
        $this->assertEquals(100, $property->getValue($grant));
    }

    public function testCheckAuthoriseParamsMissingClientId()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_GET = [];
        $server = new AuthorizationServer();

        $grant = new AuthCodeGrant();

        $server->addGrantType($grant);
        $grant->checkAuthorizeParams();
    }

    public function testCheckAuthoriseParamsMissingRedirectUri()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $server = new AuthorizationServer();
        $_GET = [
            'client_id' =>  'testapp',
        ];

        $grant = new AuthCodeGrant();

        $server->addGrantType($grant);
        $grant->checkAuthorizeParams();
    }

    public function testCheckAuthoriseParamsInvalidClient()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidClientException');

        $_GET = [
            'client_id'     =>  'testapp',
            'redirect_uri'  =>  'http://foo/bar',
            'response_type' =>  'code',
        ];
        $server = new AuthorizationServer();

        $grant = new AuthCodeGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(null);

        $server->setClientStorage($clientStorage);

        $server->addGrantType($grant);
        $grant->checkAuthorizeParams();
    }

    public function testCheckAuthoriseParamsMissingStateParam()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_GET = [
            'client_id' =>  'testapp',
            'redirect_uri'  =>  'http://foo/bar',
        ];
        $server = new AuthorizationServer();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );
        $server->setClientStorage($clientStorage);

        $grant = new AuthCodeGrant();
        $server->requireStateParam(true);

        $server->addGrantType($grant);
        $grant->checkAuthorizeParams();
    }

    public function testCheckAuthoriseParamsMissingResponseType()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_GET = [
            'client_id'     =>  'testapp',
            'redirect_uri'  =>  'http://foo/bar',
        ];
        $server = new AuthorizationServer();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );
        $server->setClientStorage($clientStorage);

        $grant = new AuthCodeGrant();

        $server->addGrantType($grant);
        $grant->checkAuthorizeParams();
    }

    public function testCheckAuthoriseParamsInvalidResponseType()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\UnsupportedResponseTypeException');

        $_GET = [
            'client_id'     =>  'testapp',
            'redirect_uri'  =>  'http://foo/bar',
            'response_type' =>  'foobar',
        ];
        $server = new AuthorizationServer();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );
        $server->setClientStorage($clientStorage);

        $grant = new AuthCodeGrant();

        $server->addGrantType($grant);
        $grant->checkAuthorizeParams();
    }

    public function testCheckAuthoriseParamsInvalidScope()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidScopeException');

        $_GET = [
            'response_type' =>  'code',
            'client_id'     =>  'testapp',
            'redirect_uri'  =>  'http://foo/bar',
            'scope'         =>  'foo',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $sessionStorage->shouldReceive('create');
        $sessionStorage->shouldReceive('getScopes')->andReturn([]);

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('create');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn([]);

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');
        $scopeStorage->shouldReceive('get')->andReturn(null);

        $server->setClientStorage($clientStorage);
        $server->setScopeStorage($scopeStorage);
        $server->setSessionStorage($sessionStorage);
        $server->setAccessTokenStorage($accessTokenStorage);

        $server->addGrantType($grant);
        $grant->checkAuthorizeParams();
    }

    public function testCheckAuthoriseParams()
    {
        $_GET = [
            'response_type' =>  'code',
            'client_id'     =>  'testapp',
            'redirect_uri'  =>  'http://foo/bar',
            'scope'         =>  'foo',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $sessionStorage->shouldReceive('create')->andreturn(123);
        $sessionStorage->shouldReceive('getScopes')->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
        ]);
        $sessionStorage->shouldReceive('associateScope');

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('create');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
        ]);
        $accessTokenStorage->shouldReceive('associateScope');

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');
        $scopeStorage->shouldReceive('get')->andReturn(
            (new ScopeEntity($server))->hydrate(['id' => 'foo'])
        );

        $server->setClientStorage($clientStorage);
        $server->setScopeStorage($scopeStorage);
        $server->setSessionStorage($sessionStorage);
        $server->setAccessTokenStorage($accessTokenStorage);

        $server->addGrantType($grant);

        $result = $grant->checkAuthorizeParams();

        $this->assertTrue($result['client'] instanceof ClientEntity);
        $this->assertTrue($result['redirect_uri'] === $_GET['redirect_uri']);
        $this->assertTrue($result['state'] === null);
        $this->assertTrue($result['response_type'] === 'code');
        $this->assertTrue($result['scopes']['foo'] instanceof ScopeEntity);
    }

    public function testNewAuthoriseRequest()
    {
        $server = new AuthorizationServer();
        $client = (new ClientEntity($server))->hydrate(['id' => 'testapp']);
        $scope = (new ScopeEntity($server))->hydrate(['id' => 'foo']);

        $grant = new AuthCodeGrant();
        $server->addGrantType($grant);

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $sessionStorage->shouldReceive('create')->andreturn(123);
        $sessionStorage->shouldReceive('getScopes')->shouldReceive('getScopes')->andReturn([$scope]);
        $sessionStorage->shouldReceive('associateScope');
        $server->setSessionStorage($sessionStorage);

        $authCodeStorage = M::mock('joshstar\OAuth2\Server\Storage\AuthCodeInterface');
        $authCodeStorage->shouldReceive('setServer');
        $authCodeStorage->shouldReceive('get');
        $authCodeStorage->shouldReceive('create');
        $authCodeStorage->shouldReceive('associateScope');
        $server->setAuthCodeStorage($authCodeStorage);

        $grant->newAuthorizeRequest('user', 123, [
            'client'        => $client,
            'redirect_uri'  =>  'http://foo/bar',
            'scopes'        =>  [$scope],
            'state'         =>  'foobar'
        ]);
    }

    public function testCompleteFlowMissingClientId()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_POST['grant_type'] = 'authorization_code';

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $server->addGrantType($grant);
        $server->issueAccessToken();
    }

    public function testCompleteFlowMissingClientSecret()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_POST = [
            'grant_type' => 'authorization_code',
            'client_id' =>  'testapp',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $server->addGrantType($grant);
        $server->issueAccessToken();
    }

    public function testCompleteFlowMissingRedirectUri()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_POST = [
            'grant_type' => 'authorization_code',
            'client_id' =>  'testapp',
            'client_secret' => 'foobar',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $server->addGrantType($grant);
        $server->issueAccessToken();
    }

    public function testCompleteFlowInvalidClient()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidClientException');

        $_POST = [
            'grant_type'    =>  'authorization_code',
            'client_id'     =>  'testapp',
            'client_secret' =>  'foobar',
            'redirect_uri'  =>  'http://foo/bar',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(null);

        $server->setClientStorage($clientStorage);

        $server->addGrantType($grant);
        $server->issueAccessToken();
    }

    public function testCompleteFlowMissingCode()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_POST = [
            'grant_type'    =>  'authorization_code',
            'client_id'     =>  'testapp',
            'client_secret' =>  'foobar',
            'redirect_uri'  =>  'http://foo/bar',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $sessionStorage->shouldReceive('create');
        $sessionStorage->shouldReceive('getScopes')->andReturn([]);

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('create');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn([]);

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');
        $scopeStorage->shouldReceive('get')->andReturn(null);

        $authCodeStorage = M::mock('joshstar\OAuth2\Server\Storage\AuthCodeInterface');
        $authCodeStorage->shouldReceive('setServer');
        $authCodeStorage->shouldReceive('get');

        $server->setClientStorage($clientStorage);
        $server->setScopeStorage($scopeStorage);
        $server->setSessionStorage($sessionStorage);
        $server->setAccessTokenStorage($accessTokenStorage);
        $server->setAuthCodeStorage($authCodeStorage);

        $server->addGrantType($grant);
        $server->issueAccessToken();
    }

    public function testCompleteFlowInvalidCode()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_POST = [
            'grant_type'    =>  'authorization_code',
            'client_id'     =>  'testapp',
            'client_secret' =>  'foobar',
            'redirect_uri'  =>  'http://foo/bar',
            'code'          =>  'foobar',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $sessionStorage->shouldReceive('create');
        $sessionStorage->shouldReceive('getScopes')->andReturn([]);

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('create');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn([]);

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');
        $scopeStorage->shouldReceive('get')->andReturn(null);

        $authCodeStorage = M::mock('joshstar\OAuth2\Server\Storage\AuthCodeInterface');
        $authCodeStorage->shouldReceive('setServer');
        $authCodeStorage->shouldReceive('get');

        $server->setClientStorage($clientStorage);
        $server->setScopeStorage($scopeStorage);
        $server->setSessionStorage($sessionStorage);
        $server->setAccessTokenStorage($accessTokenStorage);
        $server->setAuthCodeStorage($authCodeStorage);

        $server->addGrantType($grant);
        $server->issueAccessToken();
    }

    public function testCompleteFlowExpiredCode()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_POST = [
            'grant_type'    =>  'authorization_code',
            'client_id'     =>  'testapp',
            'client_secret' =>  'foobar',
            'redirect_uri'  =>  'http://foo/bar',
            'code'          =>  'foobar',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $sessionStorage->shouldReceive('create');
        $sessionStorage->shouldReceive('getScopes')->andReturn([]);

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('create');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn([]);

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');
        $scopeStorage->shouldReceive('get')->andReturn(null);

        $authCodeStorage = M::mock('joshstar\OAuth2\Server\Storage\AuthCodeInterface');
        $authCodeStorage->shouldReceive('setServer');
        $authCodeStorage->shouldReceive('get')->andReturn(
            (new AuthCodeEntity($server))->setId('foobar')->setExpireTime(time() - 300)->setRedirectUri('http://foo/bar')
        );

        $server->setClientStorage($clientStorage);
        $server->setScopeStorage($scopeStorage);
        $server->setSessionStorage($sessionStorage);
        $server->setAccessTokenStorage($accessTokenStorage);
        $server->setAuthCodeStorage($authCodeStorage);

        $server->addGrantType($grant);
        $server->issueAccessToken();
    }

    public function testCompleteFlowRedirectUriMismatch()
    {
        $this->setExpectedException('joshstar\OAuth2\Server\Exception\InvalidRequestException');

        $_POST = [
            'grant_type'    =>  'authorization_code',
            'client_id'     =>  'testapp',
            'client_secret' =>  'foobar',
            'redirect_uri'  =>  'http://foo/bar',
            'code'          =>  'foobar',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $sessionStorage->shouldReceive('create');
        $sessionStorage->shouldReceive('getScopes')->andReturn([]);

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('create');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn([]);

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');
        $scopeStorage->shouldReceive('get')->andReturn(null);

        $authCodeStorage = M::mock('joshstar\OAuth2\Server\Storage\AuthCodeInterface');
        $authCodeStorage->shouldReceive('setServer');
        $authCodeStorage->shouldReceive('get')->andReturn(
            (new AuthCodeEntity($server))->setId('foobar')->setExpireTime(time() + 300)->setRedirectUri('http://fail/face')
        );

        $server->setClientStorage($clientStorage);
        $server->setScopeStorage($scopeStorage);
        $server->setSessionStorage($sessionStorage);
        $server->setAccessTokenStorage($accessTokenStorage);
        $server->setAuthCodeStorage($authCodeStorage);

        $server->addGrantType($grant);
        $server->issueAccessToken();
    }

    public function testCompleteFlow()
    {
        $_POST = [
            'grant_type'    => 'authorization_code',
            'client_id'     =>  'testapp',
            'client_secret' =>  'foobar',
            'redirect_uri'  =>  'http://foo/bar',
            'code'          =>  'foo',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('getBySession')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $sessionStorage->shouldReceive('create')->andreturn(123);
        $sessionStorage->shouldReceive('associateScope');
        $sessionStorage->shouldReceive('getByAuthCode')->andReturn(
            (new SessionEntity($server))->setId('foobar')
        );
        $sessionStorage->shouldReceive('getByAccessToken')->andReturn(
            (new SessionEntity($server))->setId('foobar')
        );
        $sessionStorage->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
        ]);

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('create');
        $accessTokenStorage->shouldReceive('associateScope');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
        ]);

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');
        $scopeStorage->shouldReceive('get')->andReturn(
            (new ScopeEntity($server))->hydrate(['id' => 'foo'])
        );

        $authCodeStorage = M::mock('joshstar\OAuth2\Server\Storage\AuthCodeInterface');
        $authCodeStorage->shouldReceive('setServer');
        $authCodeStorage->shouldReceive('delete');
        $authCodeStorage->shouldReceive('get')->andReturn(
            (new AuthCodeEntity($server))->setId('foobar')->setRedirectUri('http://foo/bar')->setExpireTime(time() + 300)
        );
        $authCodeStorage->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
        ]);

        $server->setClientStorage($clientStorage);
        $server->setScopeStorage($scopeStorage);
        $server->setSessionStorage($sessionStorage);
        $server->setAccessTokenStorage($accessTokenStorage);
        $server->setAuthCodeStorage($authCodeStorage);

        $server->addGrantType($grant);
        $server->issueAccessToken();
    }

    public function testCompleteFlowWithRefreshToken()
    {
        $_POST = [
            'grant_type'    => 'authorization_code',
            'client_id'     =>  'testapp',
            'client_secret' =>  'foobar',
            'redirect_uri'  =>  'http://foo/bar',
            'code'          =>  'foo',
        ];

        $server = new AuthorizationServer();
        $grant = new AuthCodeGrant();
        $rtgrant = new RefreshTokenGrant();

        $clientStorage = M::mock('joshstar\OAuth2\Server\Storage\ClientInterface');
        $clientStorage->shouldReceive('setServer');
        $clientStorage->shouldReceive('getBySession')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );
        $clientStorage->shouldReceive('get')->andReturn(
            (new ClientEntity($server))->hydrate(['id' => 'testapp'])
        );

        $sessionStorage = M::mock('joshstar\OAuth2\Server\Storage\SessionInterface');
        $sessionStorage->shouldReceive('setServer');
        $sessionStorage->shouldReceive('create')->andreturn(123);
        $sessionStorage->shouldReceive('associateScope');
        $sessionStorage->shouldReceive('getByAuthCode')->andReturn(
            (new SessionEntity($server))->setId('foobar')
        );
        $sessionStorage->shouldReceive('getByAccessToken')->andReturn(
            (new SessionEntity($server))->setId('foobar')
        );
        $sessionStorage->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
        ]);

        $accessTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\AccessTokenInterface');
        $accessTokenStorage->shouldReceive('setServer');
        $accessTokenStorage->shouldReceive('create');
        $accessTokenStorage->shouldReceive('associateScope');
        $accessTokenStorage->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
        ]);

        $scopeStorage = M::mock('joshstar\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');
        $scopeStorage->shouldReceive('get')->andReturn(
            (new ScopeEntity($server))->hydrate(['id' => 'foo'])
        );

        $authCodeStorage = M::mock('joshstar\OAuth2\Server\Storage\AuthCodeInterface');
        $authCodeStorage->shouldReceive('setServer');
        $authCodeStorage->shouldReceive('delete');
        $authCodeStorage->shouldReceive('get')->andReturn(
            (new AuthCodeEntity($server))->setId('foobar')->setRedirectUri('http://foo/bar')->setExpireTime(time() + 300)
        );
        $authCodeStorage->shouldReceive('getScopes')->andReturn([
            (new ScopeEntity($server))->hydrate(['id' => 'foo']),
        ]);

        $refreshTokenStorage = M::mock('joshstar\OAuth2\Server\Storage\RefreshTokenInterface');
        $refreshTokenStorage->shouldReceive('setServer');
        $refreshTokenStorage->shouldReceive('create');
        $refreshTokenStorage->shouldReceive('associateScope');

        $server->setClientStorage($clientStorage);
        $server->setScopeStorage($scopeStorage);
        $server->setSessionStorage($sessionStorage);
        $server->setAccessTokenStorage($accessTokenStorage);
        $server->setAuthCodeStorage($authCodeStorage);
        $server->setRefreshTokenStorage($refreshTokenStorage);

        $server->addGrantType($grant);
        $server->addGrantType($rtgrant);
        $server->issueAccessToken();
    }
}
