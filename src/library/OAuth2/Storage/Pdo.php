<?php

namespace Tranquility\OAuth2\Storage;

/**
 * Customises OAuth2 library for specific Tranquility table structures
 */

class Pdo extends \OAuth2\Storage\Pdo
          implements \OAuth2\Storage\AuthorizationCodeInterface,
                     \OAuth2\Storage\AccessTokenInterface,
                     \OAuth2\Storage\ClientCredentialsInterface,
                     \OAuth2\Storage\UserCredentialsInterface,
                     \OAuth2\Storage\RefreshTokenInterface,
                     \OAuth2\Storage\JwtBearerInterface,
                     \OAuth2\Storage\ScopeInterface,
                     \OAuth2\Storage\PublicKeyInterface {

    /**
     * Constructor
     * 
     * Override to specify new table names. These can be overriden themselves
     * by the $config array, if required.
     * 
     * @param \PDO $connection
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct($connection, $config = array()) {
        $config = array_merge(array(
            'client_table'        => 'tql_sys_oauth_clients',
            'access_token_table'  => 'tql_sys_oauth_access_tokens',
            'refresh_token_table' => 'tql_sys_oauth_refresh_tokens',
            'code_table'          => 'tql_sys_oauth_authorization_codes',
            'jwt_table'           => 'tql_sys_oauth_jwt',
            'scope_table'         => 'tql_sys_oauth_scopes',
            'user_table'          => 'tql_entity_users',
        ));
        
        // Use parent to construct
        parent::__construct($connection, $config);
    }

    /* OAuth2\Storage\UserCredentialsInterface */
    public function checkUserCredentials($username, $password) {
        $mapper = new UsersMapper(array(), $this->db, null);
        return $mapper->validateUserCredentials($username, $password);
    }

    public function getUserDetails($username) {
        $mapper = new UsersMapper(array(), $this->db, null);
        $response = $mapper->getUserByUsername($username);
        if ($response->hasErrors()) {
            return false;
        }
        
        return $response['users']['0'];
    }

    public function getUser($username)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where username=:username', $this->config['user_table']));
        $stmt->execute(array('username' => $username));

        if (!$userInfo = $stmt->fetch()) {
            return false;
        }

        // the default behavior is to use "username" as the user_id
        return array_merge(array(
            'user_id' => $username
        ), $userInfo);
    }
}
