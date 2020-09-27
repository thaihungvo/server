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
// used by the client to validate/test the server URL
$routes->get('/ping', 'PingController::index');

// USER
    // login user
    $routes->post('/login', 'UserController::login_v1');
    // register user
    $routes->post('/register', 'UserController::register_v1');

// MEMBERS
    // get all members
    $routes->get('/api/v1/members', 'MembersController::all_v1/$1');

// TAGS
    // get all boards tags
    $routes->get('/api/v1/boards/(:any)/tags', 'TagsController::all_v1/$1');
    // add a new tag
    $routes->post('/api/v1/boards/(:any)/tags', 'TagsController::add_v1/$1');
    // update a tag
    $routes->put('/api/v1/boards/(:any)/tags/(:any)', 'TagsController::update_v1/$1/$2');

// STACKS
    // archive all completed tasks
    $routes->get('/api/v1/stacks/(:any)/archive-done', 'StacksController::archive_done_v1/$1');
    // archive all tasks
    $routes->get('/api/v1/stacks/(:any)/archive-all', 'StacksController::archive_all_v1/$1');
    // mark all tasks as complete
    $routes->get('/api/v1/stacks/(:any)/done', 'StacksController::done_v1/$1');
    // mark all tasks as to do
    $routes->get('/api/v1/stacks/(:any)/todo', 'StacksController::todo_v1/$1');
    // get all stacks in a board
    $routes->get('/api/v1/boards/(:any)/stacks', 'StacksController::all_v1/$1');
    // create a stack
    $routes->post('/api/v1/boards/(:any)/stacks', 'StacksController::add_v1/$1');
    // update a stack
    $routes->put('/api/v1/stacks/(:any)', 'StacksController::update_v1/$1');
    // delete a stack
    $routes->delete('/api/v1/stacks/(:any)', 'StacksController::delete_v1/$1');

// TASKS
    // get watchers for the task
    $routes->get('/api/v1/tasks/(:any)/watchers', 'TasksController::get_watchers_v1/$1');
    // add the current user to the task watch list
    $routes->get('/api/v1/tasks/(:any)/watch', 'TasksController::add_watcher_v1/$1');
    // remove the current user from the watch list
    $routes->get('/api/v1/tasks/(:any)/unwatch', 'TasksController::remove_watcher_v1/$1');
    // tasks by board
    $routes->get('/api/v1/boards/(:any)/tasks', 'TasksController::all_board_v1/$1');
    // tasks by stack
    $routes->get('/api/v1/stacks/(:any)/tasks', 'TasksController::all_stack_v1/$1');
    // single task
    $routes->get('/api/v1/tasks/(:any)', 'TasksController::one_v1/$1');
    // create a task
    $routes->post('/api/v1/boards/(:any)/tasks/(:any)', 'TasksController::add_v1/$1/$2');
    // update a task
    $routes->put('/api/v1/tasks/(:any)', 'TasksController::update_v1/$1');
    // delete a task
    $routes->delete('/api/v1/tasks/(:any)', 'TasksController::delete_v1/$1');
    

// BOARDS
    // get all boards
    $routes->get('/api/v1/boards', 'BoardsController::all_v1');
    // create a board
    $routes->post('/api/v1/boards', 'BoardsController::add_v1');
    // get a specific board
    $routes->get('/api/v1/boards/(:any)', 'BoardsController::one_v1/$1');
    // update a board
    $routes->put('/api/v1/boards/(:any)', 'BoardsController::update_v1/$1');
    // save stacks order inside a board
    $routes->post('/api/v1/boards/(:any)/order-stacks', 'BoardsController::order_stacks_v1/$1');
    // save tasks order inside a board
    $routes->post('/api/v1/boards/(:any)/order-tasks', 'BoardsController::order_tasks_v1/$1/$2');

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
