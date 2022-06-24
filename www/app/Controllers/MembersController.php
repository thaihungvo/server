<?php namespace App\Controllers;

use App\Models\UserModel;

class MembersController extends BaseController
{
	public function all_v1($peopleId)
	{
        $userModel = new UserModel();
        $users = $userModel->findAll();

        foreach ($users as &$user) {
            unset($user->password);
            unset($user->created);
            unset($user->updated);
            $user->id = intval($user->id);
        }

        return $this->reply($users);
    }
}