<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\UploadModel;

class Auth extends BaseController
{
    public function index()
    {
        $modelUpload = new UploadModel();
        $logo = $modelUpload->first();

        return view('auth/login', ['logo' => $logo]);
    }

    public function login()
    {
        $modelUsers = new UsersModel();
        $modelUpload = new UploadModel();

        $logo = $modelUpload->first();
        $user = $this->request->getVar('user');
        $password = $this->request->getVar('password');

        $userData = $modelUsers->where("user", $user)->first();

        if (isset($userData)) {
            if (password_verify($password, $userData['password'])) {
                $sessionData = [
                    'id_user'    => $userData['id'],
                    'logo'       => isset($logo['name']) ? $logo['name'] : '',
                    'user'       => $userData['user'],
                    'active'     => $userData['active'],
                    'name'       => $userData['name'],
                    'superadmin' => $userData['superadmin'],
                    'logueado'   => true,
                ];

                session()->set($sessionData);

                return redirect()->to(base_url('abmAdmin'));
            } else {
                return redirect()->to('auth/login')->with('msg', ['type' => 'danger', 'body' => 'El usuario o la contraseña no son correctos']);
            }
        } else {
            return redirect()->to('auth/login')->with('msg', ['type' => 'danger', 'body' => 'El usuario o la contraseña no son correctos']);
        }
    }

    public function log_out()
    {
        session()->destroy();

        return redirect()->route('auth/login');
    }

    public function register()
    {
        $modelUsers = new UsersModel();
        $users = $modelUsers->findAll();

        return view('auth/register', ['users' => $users]);
    }

    public function dbRegister()
    {
        $modelUsers = new UsersModel();

        $password = $this->request->getVar('password');
        $repeat_password = $this->request->getVar('repeat_password');
        $hash_password = '';
        $superadmin = $this->request->getVar('superadmin') ? 1 : 0;
        $user = $this->request->getVar('user');
        $name = $this->request->getVar('name');

        if ($password == $repeat_password) {
            $hash_password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            return redirect()->to('auth/register')->with('msg', ['type' => 'danger', 'body' => 'Las contraseñas no coinciden']);
        }

        if ($user == '' || $name == '' || $password == '') {
            return redirect()->to('auth/register')->with('msg', ['type' => 'danger', 'body' => 'Debe completar todos los datos']);
        }

        $query = [
            'user' => $user,
            'password' => $hash_password,
            'superadmin' => $superadmin,
            'name' => $name,
        ];


        try {
            $modelUsers->insert($query);
        } catch (\Exception $e) {
            return "Error al insertar datos: " . $e->getMessage();
        }

        return redirect()->to('auth/login')->with('msg', ['type' => 'success', 'body' => 'Usuario creado correctamente']);
    }
}
