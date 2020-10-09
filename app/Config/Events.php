<?php namespace Config;

use CodeIgniter\Events\Events;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', function () {
	if (ENVIRONMENT !== 'testing')
	{
		while (\ob_get_level() > 0)
		{
			\ob_end_flush();
		}

		\ob_start(function ($buffer) {
			return $buffer;
		});
	}

	/*
	 * --------------------------------------------------------------------
	 * Debug Toolbar Listeners.
	 * --------------------------------------------------------------------
	 * If you delete, they will no longer be collected.
	 */
	if (ENVIRONMENT !== 'production')
	{
		// Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
		// Services::toolbar()->respond();
    }

    Events::on('activity', function($item, $action, $section, $board = "") {
        $db = \Config\Database::connect();

        $request = \Config\Services::request();
        $user = $request->user;

        if (!strlen($board)) {
            $board = $request->board->id;
        }
        
        $query = array(
            "INSERT INTO ". $db->prefixTable("activities") ." (`user`, `board`, `item`, `action`, `section`, `created`)",
            "VALUES ('".$db->escapeString($user->id)."', '".$db->escapeString($board)."', '".$db->escapeString($item)."', '".$db->escapeString($action)."', '".$db->escapeString($section)."', NOW())"
        );

        $db->query(implode(" ", $query));
        $db->close();

        cache()->save("last-update", strtotime("now"));
    });

    // Boards
    Events::on('AFTER_board_ADD', function($board) {
        Events::trigger('activity', $board, "CREATE", "BOARDS", $board);
    });

    Events::on('AFTER_board_UPDATE', function($board) {
        Events::trigger('activity', $board, "UPDATE", "BOARD", $board);
    });

    // Stacks
    Events::on('AFTER_stacks_ORDER', function($board) {
        Events::trigger('activity', $board, "UPDATE", "BOARD", $board);
    });

    // Stack
    Events::on('AFTER_stack_UPDATE', function($stack, $board) {
        Events::trigger('activity', $stack, "UPDATE", "STACK", $board);
    });

    // Tasks
    Events::on('AFTER_tasks_ORDER', function($board) {
        Events::trigger('activity', $board, "UPDATE", "BOARD", $board);
    });

    // Task
    Events::on('AFTER_task_ADD', function($task) {
        Events::trigger('activity', $task, "CREATE", "TASK");
    });

    Events::on('AFTER_task_DELETE', function($task) {
        Events::trigger('activity', $task, "DELETE", "TASK");
    });

    Events::on('AFTER_task_UPDATE', function($task) {
        Events::trigger('activity', $task, "UPDATE", "TASK");
    });

    Events::on('AFTER_task_watcher_ADD', function($task) {
        Events::trigger('activity', $task, "CREATE", "WATCHER");
    });

    Events::on('AFTER_task_watcher_REMOVE', function($task) {
        Events::trigger('activity', $task, "DELETE", "WATCHER");
    });
});
