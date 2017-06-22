<?php

namespace joshstarTests\Stubs;

class StubAbstractGrant extends \joshstar\OAuth2\Server\Grant\AbstractGrant
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
