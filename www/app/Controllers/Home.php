<?php namespace App\Controllers;

class Home extends BaseController
{
	public function index()
	{
        $date = date('Y-m-d H:i:s', strtotime('-3 seconds'));

        return $this->reply($date);
	}

    public function cleanup()
	{
        try {
            $this->db->query("TRUNCATE stk_activities");
            $this->db->query("TRUNCATE stk_attachments");
            $this->db->query("TRUNCATE stk_documents");
            $this->db->query("TRUNCATE stk_files");
            $this->db->query("TRUNCATE stk_notepads");
            $this->db->query("TRUNCATE stk_people");
            $this->db->query("TRUNCATE stk_permissions");
            $this->db->query("TRUNCATE stk_stacks");
            $this->db->query("TRUNCATE stk_stacks_collapsed");
            $this->db->query("TRUNCATE stk_statuses");
            $this->db->query("TRUNCATE stk_tags");
            $this->db->query("TRUNCATE stk_tasks");
            $this->db->query("TRUNCATE stk_tasks_assignees");
            $this->db->query("TRUNCATE stk_tasks_extensions");
            $this->db->query("TRUNCATE stk_tasks_watchers");
            // $this->db->query("DELETE FROM stk_users WHERE id > 3");
            // $this->db->query("ALTER TABLE stk_users AUTO_INCREMENT = 4");
        } catch (\Exception $e) {
            return $this->reply(false);    
        }

        return $this->reply(true);
	}
}
