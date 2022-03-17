<?php
namespace App\Controllers;

use CodeIgniter\Events\Events;
use App\Models\DocumentModel;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 *
 * @package CodeIgniter
 */

use CodeIgniter\Controller;

class BaseController extends Controller
{

	/**
	 * An array of helpers to be loaded automatically upon
	 * class instantiation. These helpers will be available
	 * to all other controllers that extend BaseController.
	 *
	 * @var array
	 */
    protected $helpers = [];
    protected $lockId = null;
    protected $permissionSection = null;

    const TYPE_FOLDER = "folder";
    const TYPE_PROJECT = "project";
    const TYPE_NOTEPAD = "notepad";
    const TYPE_PEOPLE = "people";
    const TYPE_FILE = "file";

    const ACTION_CREATE = "CREATE";
    const ACTION_UPDATE = "UPDATE";
    const ACTION_DELETE = "DELETE";
    const ACTION_UNKNOWN = "UNKNOWN";

    const SECTION_DOCUMENTS = "documents";
    const SECTION_DOCUMENT = "document";
    const SECTION_STACK = "stack";
    const SECTION_TASK = "task";
    const SECTION_ORDER = "order";
    const SECTION_WATCHER = "watcher";

    const PERMISSION_FULL = "FULL";
    const PERMISSION_EDIT = "EDIT";
    const PERMISSION_LIMITED = "LIMITED";
    const PERMISSION_READONLY = "READONLY";

	/**
	 * Constructor.
	 */
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);

		//--------------------------------------------------------------------
		// Preload any models, libraries, etc, here.
		//--------------------------------------------------------------------
		// E.g.:
		// $this->session = \Config\Services::session();
	}

    public function reply($data = null, $code = 200, $msg = null, $unlock = true)
    {
        $response = new \stdClass();
        $response->message = $msg;
        $response->code = $code;
        $response->data = $data;

        if ($unlock) {
            $this->unlock();
        }

        return $this->response->setStatusCode($code)->setJSON($response);
    }

    protected function getDocument($documentId)
    {
        $db = db_connect();
        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        return $documentModel
            ->select("documents.*")
            ->join("permissions", "permissions.resource = documents.id AND permissions.user = ".$db->escape($this->request->user->id), 'left')
            ->groupStart()
                ->where("public", 1)
                ->orGroupStart()
                    ->where("public", 0)
                    ->where("owner", $this->request->user->id)
                ->groupEnd()
                ->orWhere("permissions.permission IS NOT NULL", null)
            ->groupEnd()
            ->find($documentId);
    }

    protected function can($action, $permission)
    {
        helper("permissions");
        return permissions_can($action, $permission, $this->permissionSection);
    }

    protected function lock($id)
    {
        $user = $this->request->user;

        $lockedBy = cache($id);

        if ($lockedBy) {
            $this->reply($lockedBy, 423, "WRN-RESOURCE-LOCKED", false)->send();
            die();
        }
        $this->lockId = $id;
        cache()->save($this->lockId, $user, 30);
    }

    protected function unlock()
    {
        if ($this->lockId) {
            cache()->delete($this->lockId);
        }
    }

    protected function addActivity($document = "", $parent = "", $item, $action, $section)
    {
        Events::trigger("activities", [
            [
                "document" => $document,
                "parent" => $parent,
                "item" => $item,
                "action" => $action,
                "section" => $section
            ]
        ]);
    }

    protected function addActivities($activities)
    {
        Events::trigger('activities', $activities);
    }

    protected function notify()
    {
        $user = $this->request->user;

        Events::trigger("notify", $user);
    }
}
