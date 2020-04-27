<?php namespace App\Controllers;

use App\Models\UserModel;
use App\Entities\User;

class Home extends BaseController
{
	public function index()
	{
        $data = [
            'success' => true,
            'id' => 123
        ];

        $userModel = new UserModel();
        
        $data = [
            'username' => 'darth',
            'email'    => 'd.vader@theempire.com'
        ];
        $userModel->save($data);

        $users = $userModel->findAll();

        return $this->response->setStatusCode(200)->setJSON($users);
	}

	//--------------------------------------------------------------------

}
