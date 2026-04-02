<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use CodeIgniter\HTTP\ResponseInterface;

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
        $password = trim((string) ($data->password ?? ''));
        $query = [
            'user' => trim((string) ($data->user ?? '')),
            'name' => trim((string) ($data->name ?? '')),
            'superadmin' => !empty($data->superadmin) ? 1 : 0,
            'active' => isset($data->active) ? (!empty($data->active) ? 1 : 0) : 1,
        ];

        if ($query['user'] === '' || $query['name'] === '') {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['error' => true, 'message' => 'Debe completar usuario y nombre']);
        }

        if ($password !== '') {
            $query['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        try {
            $modelUsers->update($data->id, $query);

            return  $this->response->setJSON([
                'error' => false,
                'message' => 'Usuario actualizado correctamente',
                'item' => $modelUsers->find($data->id),
            ]);
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function saveUser()
    {
        $modelUsers = new UsersModel();
        $data = $this->request->getJSON();

        $user = trim((string) ($data->user ?? ''));
        $name = trim((string) ($data->name ?? ''));
        $password = trim((string) ($data->password ?? ''));
        $repeatPassword = trim((string) ($data->repeat_password ?? ''));
        $superadmin = !empty($data->superadmin) ? 1 : 0;
        $active = isset($data->active) ? (!empty($data->active) ? 1 : 0) : 1;

        if ($user === '' || $name === '' || $password === '') {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['error' => true, 'message' => 'Debe completar todos los datos']);
        }

        if ($password !== $repeatPassword) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['error' => true, 'message' => 'Las contrasenas no coinciden']);
        }

        if ($modelUsers->where('user', $user)->first()) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['error' => true, 'message' => 'El usuario ya existe']);
        }

        $query = [
            'user' => $user,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'superadmin' => $superadmin,
            'name' => $name,
            'active' => $active,
        ];

        try {
            $id = $modelUsers->insert($query, true);
            return $this->response->setJSON([
                'error' => false,
                'message' => 'Usuario creado correctamente',
                'item' => $modelUsers->find($id),
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['error' => true, 'message' => 'No se pudo crear el usuario']);
        }
    }

    public function disableUser($id)
    {
        $modelUsers = new UsersModel();
        $user = $modelUsers->find($id);

        if (!$user) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                ->setJSON(['error' => true, 'message' => 'Usuario no encontrado']);
        }

        try {
            $modelUsers->update($id, ['active' => 0]);
            return $this->response->setJSON([
                'error' => false,
                'message' => 'Usuario desactivado correctamente',
                'id' => $id,
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['error' => true, 'message' => 'No se pudo desactivar el usuario']);
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
