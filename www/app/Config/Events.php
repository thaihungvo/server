<?php namespace Config;

use CodeIgniter\Events\Events;
use App\Events\Notifications;

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

    Events::on('activity', function($item, $action, $section, $parent = "") {
        $db = \Config\Database::connect();

        $request = \Config\Services::request();
        $user = $request->user;
        
        $query = array(
            "INSERT INTO ". $db->prefixTable("activities") ." (`user`, `instance`, `parent`, `item`, `action`, `section`, `created`)",
            "VALUES ('".$db->escapeString($user->id)."', '".$db->escapeString($user->instance)."', '".$db->escapeString($parent)."', '".$db->escapeString($item)."', '".$db->escapeString($action)."', '".$db->escapeString($section)."', '". date("Y-m-d H:i:s", \strtotime("now")) ."')"
        );

        $db->query(implode(" ", $query));
        $db->close();

        cache()->save("last-update", strtotime("now"));
    });

    Events::on('activities', function($activities) {
        $db = \Config\Database::connect();

        if (!count($activities)) return;

        $request = \Config\Services::request();
        $user = $request->user;
        
        $query = array();
        $query[] = "INSERT INTO ". $db->prefixTable("activities") ." (`user`, `instance`, `parent`, `item`, `action`, `section`, `created`)";
        
        foreach ($activities as $i => $activity) {
            $value = "";
            if ($i == 0) $value .= "VALUES ";
            $value .= "('".$db->escapeString($user->id)."', '".$db->escapeString($user->instance)."', '".$db->escapeString(isset($activity["parent"]) ? $activity["parent"] : "")."', '".$db->escapeString($activity["item"])."', '".$db->escapeString($activity["action"])."', '".$db->escapeString($activity["section"])."', '". date("Y-m-d H:i:s", \strtotime("now")) ."')";
            if ($i < count($activities) - 1) {
                $value .= ",";
            }
            $query[] = $value;
        }

        $db->query(implode(" ", $query));
        $db->close();

        cache()->save("last-update", strtotime("now"));
    });

    Events::on("notify", "App\Events\Notifications::send");
});
