<?php namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes(true);

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php'))
{
	require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/**
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

// USER
$routes->post('/login', 'UserController::login_v1');
$routes->post('/register', 'UserController::register_v1');

// BOARDS

$routes->get('/api/v1/boards/(:any)/tags', 'TagsController::all_v1/$1');
$routes->post('/api/v1/boards/(:any)/tags', 'TagsController::add_v1/$1');
$routes->put('/api/v1/boards/(:any)/tags/(:any)', 'TagsController::update_v1/$1/$2');
$routes->get('/api/v1/boards', 'BoardsController::all_v1');
$routes->post('/api/v1/boards', 'BoardsController::create_v1');
$routes->get('/api/v1/boards/(:any)', 'BoardsController::one_v1/$1');
$routes->put('/api/v1/boards/(:any)', 'BoardsController::update_v1/$1');

/**
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need to it be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php'))
{
	require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
