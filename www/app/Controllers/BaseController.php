<?php
namespace App\Controllers;

use CodeIgniter\Events\Events;
use App\Models\DocumentModel;
use App\Models\PermissionModel;

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
    const SECTION_PERMISSION = "permission";

    const PERMISSION_FULL = "FULL";
    const PERMISSION_EDIT = "EDIT";
    const PERMISSION_LIMITED = "LIMITED";
    const PERMISSION_READONLY = "READONLY";

    const PERMISSION_TYPE_DOCUMENT = "DOCUMENT";
    const PERMISSION_TYPE_STACK = "STACK";
    const PERMISSION_TYPE_TASK = "TASK";

	/**
	 * Constructor.
	 */
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);
        $this->db = db_connect();
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
        $documentModel = new DocumentModel($this->request->user);
        return $documentModel->getDocument($documentId);
    }

    protected function getExpandedDocument($documentId)
    {
        helper("documents");
        $document = $this->getDocument($documentId);

        if ($document->data) {
            foreach (get_object_vars($document->data) as $key => $value) {
                $document->$key = $document->data->$key;
            }
        }
        unset($document->data);

        documents_expand_document($document, $this->request->user);
        return $document;
    }

    protected function can($action, $resource, $errorMsg = null)
    {
        $permissions = "";

        $actions = [
            "add" => "A",
            "update" => "U",
            "delete" => "D",
            "options" => "O"
        ];

        if (isset($resource->data->permissions)) {
            $permissions = $resource->data->permissions;
        } else if (isset($resource->permissions)) {
            $permissions = $resource->permissions;
        }

        if (strpos($permissions, $actions[$action]) === false) {
            $response = $this->reply(null, 403, $errorMsg ? $errorMsg : "You do not have permission to perform this action");
            $response->send();
            die();
        }
    }

    protected function exists($resource, $errorMsg = null)
    {
        if (!isset($resource)) {
            $response = $this->reply(null, 404, $errorMsg ? $errorMsg : "The requested resource was not found");
            $response->send();
            die();
        }
    }

    protected function addPermission($resourceId, $type, $permission = "FULL")
    {
        $permissionModel = new PermissionModel();
        $permission = [
            "resource" => $resourceId,
            "permission" => $permission,
            "type" => $type,
        ];
        try {
            if ($permissionModel->insert($permission) === false) {
                throw new \Exception(implode(" ", $permissionModel->errors()));
            }
        } catch (\Exception $e) {
            throw $e;
        }
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
