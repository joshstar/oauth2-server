<?php
/**
 * OAuth 2.0 Session storage interface
 *
 * @package     league/oauth2-server
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 * @link        https://github.com/thephpleague/oauth2-server
 */

namespace joshstar\OAuth2\Server\Storage;

use joshstar\OAuth2\Server\Entity\AccessTokenEntity;
use joshstar\OAuth2\Server\Entity\AuthCodeEntity;
use joshstar\OAuth2\Server\Entity\ScopeEntity;
use joshstar\OAuth2\Server\Entity\SessionEntity;

/**
 * Session storage interface
 */
interface SessionInterface extends StorageInterface
{
    /**
     * Get a session from an access token
     *
     * @param \joshstar\OAuth2\Server\Entity\AccessTokenEntity $accessToken The access token
     *
     * @return \joshstar\OAuth2\Server\Entity\SessionEntity | null
     */
    public function getByAccessToken(AccessTokenEntity $accessToken);

    /**
     * Get a session from an auth code
     *
     * @param \joshstar\OAuth2\Server\Entity\AuthCodeEntity $authCode The auth code
     *
     * @return \joshstar\OAuth2\Server\Entity\SessionEntity | null
     */
    public function getByAuthCode(AuthCodeEntity $authCode);

    /**
     * Get a session's scopes
     *
     * @param  \joshstar\OAuth2\Server\Entity\SessionEntity
     *
     * @return \joshstar\OAuth2\Server\Entity\ScopeEntity[] Array of \joshstar\OAuth2\Server\Entity\ScopeEntity
     */
    public function getScopes(SessionEntity $session);

    /**
     * Create a new session
     *
     * @param string $ownerType         Session owner's type (user, client)
     * @param string $ownerId           Session owner's ID
     * @param string $clientId          Client ID
     * @param string $clientRedirectUri Client redirect URI (default = null)
     *
     * @return integer The session's ID
     */
    public function create($ownerType, $ownerId, $clientId, $clientRedirectUri = null);

    /**
     * Associate a scope with a session
     *
     * @param \joshstar\OAuth2\Server\Entity\SessionEntity $session The session
     * @param \joshstar\OAuth2\Server\Entity\ScopeEntity   $scope   The scope
     *
     * @return void
     */
    public function associateScope(SessionEntity $session, ScopeEntity $scope);
}
