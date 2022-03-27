<?php namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes(true);

// Load the system"s routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . "Config/Routes.php"))
{
	require SYSTEMPATH . "Config/Routes.php";
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace("App\Controllers");
$routes->setDefaultController("Home");
$routes->setDefaultMethod("index");
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/**
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

 
// We get a performance increase by specifying the default
// route since we don"t have to scan directories.
$routes->get("/", "Home::index");

// PING
// used by the client to validate/test the server URL
$routes->get("/ping", "PingController::index");

// USER
    // register user
    $routes->post("/register", "UserController::register_v1");
    // login user
    $routes->post("/login", "UserController::login_v1");

// UPDATES
    // get updates
    $routes->get("/api/v1/updates", "UpdatesController::updates_v1");

// DOCUMENTS
    // get all documents
    $routes->get("/api/v1/documents", "DocumentsController::all_v1");
    // get a documents attachments
    $routes->get("/api/v1/documents/(:any)/attachments", "DocumentsController::attachments_v1/$1");
    // update a documents options
    $routes->put("/api/v1/documents/(:any)/options", "DocumentsController::update_options_v1/$1");
    // get a specific document
    $routes->get("/api/v1/documents/(:any)", "DocumentsController::one_v1/$1");
    // update a document
    $routes->put("/api/v1/documents/(:any)", "DocumentsController::update_v1/$1");
    // create a document
    $routes->post("/api/v1/documents", "DocumentsController::add_v1");
    // reorder documents
    $routes->post("/api/v1/documents/order", "DocumentsController::order_v1");
    // delete a document or folder
    $routes->delete("/api/v1/documents/(:any)", "DocumentsController::delete_v1/$1");
    

// MEMBERS
    // get all members
    $routes->get("/api/v1/members", "MembersController::all_v1");

// TAGS
    // get all tags
    $routes->get("/api/v1/tags", "TagsController::all_v1");
    // add a new tag
    $routes->post("/api/v1/tags", "TagsController::add_v1");
    // update a tag
    $routes->put("/api/v1/tags/(:any)", "TagsController::update_v1/$1");
    // delete a tag
    $routes->delete("/api/v1/tags/(:any)", "TagsController::delete_v1/$1");

// STATUSES
    // get all statuses
    $routes->get("/api/v1/statuses", "StatusesController::all_v1");
    // add a new status
    $routes->post("/api/v1/statuses", "StatusesController::add_v1");
    // add a new status
    $routes->post("/api/v1/statuses", "StatusesController::add_v1");
    // update a status
    $routes->put("/api/v1/statuses/(:any)", "StatusesController::update_v1/$1");
    // delete a status
    $routes->delete("/api/v1/statuses/(:any)", "StatusesController::delete_v1/$1");

// STACKS
    // create a stack
    $routes->post("/api/v1/projects/(:any)/stacks", "StacksController::add_v1/$1");
    // mark all tasks as complete
    $routes->get("/api/v1/stacks/(:any)/done", "StacksController::done_v1/$1");
    // mark all tasks as to do
    $routes->get("/api/v1/stacks/(:any)/todo", "StacksController::todo_v1/$1");
    // archive all completed tasks
    $routes->get("/api/v1/stacks/(:any)/archive-done", "StacksController::archive_done_v1/$1");
    // archive all tasks
    $routes->get("/api/v1/stacks/(:any)/archive-all", "StacksController::archive_all_v1/$1");
    // get a stack
    $routes->get("/api/v1/stacks/(:any)", "StacksController::get_v1/$1");
    // update a stack
    $routes->put("/api/v1/stacks/(:any)", "StacksController::update_v1/$1");
    // delete a stack
    $routes->delete("/api/v1/stacks/(:any)", "StacksController::delete_v1/$1");

// TASKS
    // create a task
    $routes->post("/api/v1/stacks/(:any)/tasks", "TasksController::add_v1/$1");
    // update a task
    $routes->put("/api/v1/tasks/(:any)", "TasksController::update_v1/$1");
    // get watchers for the task
    $routes->get("/api/v1/tasks/(:any)/watchers", "TasksController::get_watchers_v1/$1");
    // add the current user to the task watch list
    $routes->get("/api/v1/tasks/(:any)/watch", "TasksController::add_watcher_v1/$1");
    // remove the current user from the watch list
    $routes->get("/api/v1/tasks/(:any)/unwatch", "TasksController::remove_watcher_v1/$1");
    // get single task
    $routes->get("/api/v1/tasks/(:any)", "TasksController::one_v1/$1");
    // delete a task
    $routes->delete("/api/v1/tasks/(:any)", "TasksController::delete_v1/$1");


// PROJECT
    // save stacks order inside a project
    $routes->post("/api/v1/projects/(:any)/order-stack", "ProjectsController::set_order_stacks_v1/$1");
    // save tasks order inside a project
    $routes->post("/api/v1/projects/(:any)/order-task", "ProjectsController::set_order_tasks_v1/$1");
    // get stacks and tasks order in a project
    $routes->get("/api/v1/projects/(:any)/order", "ProjectsController::get_order_v1/$1");


// FILES
    // upload a file
    $routes->post("/api/v1/upload/(:any)", "FilesController::upload_v1/$1");
    // attach a link
    $routes->post("/api/v1/link/(:any)", "FilesController::link_v1/$1");
    // delete a file or a link
    $routes->delete("/api/v1/attachment/(:any)", "FilesController::delete_v1/$1");
    // delete all files and links for the specific task
    $routes->delete("/api/v1/attachments/(:any)", "FilesController::delete_all_v1/$1");
    // download an attachment or redirect in case of a link
    $routes->get("/api/v1/download/(:any)", "FilesController::download_v1/$1");
    // update the attachments title
    $routes->put("/api/v1/attachment/(:any)", "FilesController::update_v1/$1");

// PEOPLE
    // add a new persone to a people list
    $routes->post("/api/v1/people/(:any)", "PeopleController::add_v1/$1");
    // update a person
    $routes->put("/api/v1/person/(:any)", "PeopleController::update_v1/$1");
    // delete a person
    $routes->delete("/api/v1/person/(:any)", "PeopleController::delete_v1/$1");

// NOTEPADS
    // update a notepad
    $routes->put("/api/v1/notepads/(:any)", "NotepadsController::update_v1/$1");

// SEARCH
    // get all query results
    $routes->get("/api/v1/search", "SearchController::query_v1");

// PERMISSIONS
    // get resource permissions for all users connected to that resource
    $routes->get("/api/v1/permissions/(:any)/users/", "PermissionsController::get_users_v1/$1");
    // get resource global permission
    $routes->get("/api/v1/permissions/(:any)", "PermissionsController::get_v1/$1");
    // delete a permission for a specific user
    $routes->delete("/api/v1/permissions/(:any)/users/(:any)", "PermissionsController::delete_user_v1/$1/$2");
    // update a permission for a specific user
    $routes->put("/api/v1/permissions/(:any)", "PermissionsController::update_user_v1/$1");
    // add a permission
    $routes->post("/api/v1/permissions", "PermissionsController::add_v1");

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
if (file_exists(APPPATH . "Config/" . ENVIRONMENT . "/Routes.php"))
{
	require APPPATH . "Config/" . ENVIRONMENT . "/Routes.php";
}
