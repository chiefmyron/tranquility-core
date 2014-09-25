<?php

/*****************************************************************************
 * Contains mappings of routes to controllers and actions                    *
 * Requires that $router is already defined in parent file                   *
 *****************************************************************************/


// Default controller
$router->map('GET|POST', '/', 'home#index', 'home');

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