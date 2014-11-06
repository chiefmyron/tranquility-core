<?php

/*****************************************************************************
 * Contains mappings of routes to controllers and actions                    *
 * Requires that $router is already defined in parent file                   *
 *****************************************************************************/


// Default controller
$router->map('GET|POST', '/', 'home#index', 'home');

// Authorisation controller (used for OAuth2 endpoints)
$router->map('POST',   '/oauth/token', array('controller' => 'AuthController', 'action' => 'generateToken'));
$router->map('GET',    '/oauth/auth', array('controller' => 'AuthController', 'action' => 'displayAuthorisationPrompt'));
$router->map('POST',   '/oauth/auth', array('controller' => 'AuthController', 'action' => 'processAuthorisationRequest'));

// People controller
$router->map('GET',    '/people', array('controller' => 'PeopleController', 'action' => 'retrievePeopleList'));
$router->map('POST',   '/people', array('controller' => 'PeopleController', 'action' => 'createPerson'));
$router->map('GET',    '/people/[i:id]', array('controller' => 'PeopleController', 'action' => 'retrievePersonDetails'));
$router->map('PUT',    '/people/[i:id]', array('controller' => 'PeopleController', 'action' => 'updatePerson'));
$router->map('DELETE', '/people/[i:id]', array('controller' => 'PeopleController', 'action' => 'deletePerson'));

// Addresses controller
$router->map('GET',    '/people/[i:parentId]/addresses', array('controller' => 'AddressController', 'action' => 'retrieveAddressList'));
$router->map('POST',   '/people/[i:parentId]/addresses', array('controller' => 'AddressController', 'action' => 'createAddress'));
$router->map('PUT',    '/people/[i:parentId]/addresses/[i:id]', array('controller' => 'AddressController', 'action' => 'updateAddress'));
$router->map('DELETE', '/people/[i:parentId]/addresses/[i:id]', array('controller' => 'AddressController', 'action' => 'deleteAddress'));

// Users controller
$router->map('GET',    '/users', array('controller' => 'UsersController', 'action' => 'retrieveUsersList'));
$router->map('POST',   '/users', array('controller' => 'UsersController', 'action' => 'createUser'));
$router->map('GET',    '/users/[i:id]', array('controller' => 'UsersController', 'action' => 'retrieveUserDetails'));
$router->map('PUT',    '/users/[i:id]', array('controller' => 'UsersController', 'action' => 'updateUser'));
$router->map('DELETE', '/users/[i:id]', array('controller' => 'UsersController', 'action' => 'deleteUser'));
$router->map('GET',    '/people/[i:parentId]/userAccount', array('controller' => 'UsersController', 'action' => 'retrieveUserDetailsForParent'));
$router->map('POST',   '/people/[i:parentId]/userAccount', array('controller' => 'UsersController', 'action' => 'createUserForParent'));