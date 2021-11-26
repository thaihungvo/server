<?php
namespace App\Events;

class Activities {
    static function insert($activities) {
        $db = \Config\Database::connect();

        if (!count($activities)) return;

        $request = \Config\Services::request();
        $user = $request->user;
        $date = date("Y-m-d H:i:s", \strtotime("now"));
        
        $query = array();
        $query[] = "INSERT INTO ". $db->prefixTable("activities") ." (`user`, `instance`, `document`, `parent`, `item`, `action`, `section`, `created`)";
        
        foreach ($activities as $i => $activity) {
            $value = "";
            if ($i == 0) $value .= "VALUES ";
            $value .= "('".$db->escapeString($user->id)."', '".$db->escapeString($user->instance)."', '".$db->escapeString(isset($activity["document"]) ? $activity["document"] : "")."', '".$db->escapeString(isset($activity["parent"]) ? $activity["parent"] : "")."', '".$db->escapeString($activity["item"])."', '".$db->escapeString($activity["action"])."', '".$db->escapeString($activity["section"])."', '". $date ."')";
            if ($i < count($activities) - 1) {
                $value .= ",";
            }
            $query[] = $value;
        }

        $db->query(implode(" ", $query));
        $db->close();

        cache()->save("last-update", strtotime("now"));
    }
}