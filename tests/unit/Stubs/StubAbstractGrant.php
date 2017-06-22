<?php

namespace LeagueForkTests\Stubs;

class StubAbstractGrant extends \LeagueFork\OAuth2\Server\Grant\AbstractGrant
{
    protected $responseType = 'foobar';

    public function completeFlow()
    {
        return true;
    }

    public function getAuthorizationServer()
    {
        return $this->server;
    }
}
