<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;

class Users extends BaseController
{
    public function index()
    {
        //
    }

    public function getUser($id)
    {
        $modelUsers = new UsersModel();

        $user = $modelUsers->where('id', $id)->first();


        try {
            return  $this->response->setJSON($this->setResponse(null, null, $user, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function editUser()
    {
        $modelUsers = new UsersModel();
        $data = $this->request->getJSON();
        $password = $data->password;

        $query = [
            'user' => $data->user,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $data->name,
            'superadmin' => $data->superadmin,
        ];

        try {
            $modelUsers->update($data->id, $query);

            return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function setResponse($code = 200, $error = false, $data = null, $message = '')
    {
        $response = [
            'error' => $error,
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ];

        return $response;
    }
}
