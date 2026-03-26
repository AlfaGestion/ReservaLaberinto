<?php

namespace App\Controllers;

use App\Models\CustomersModel;
use App\Models\ValuesModel;

class Customers extends BaseController
{

    public function register()
    {
        $valuesModel = new ValuesModel();
        $types = $valuesModel->findAll();

        return view('customers/register', ['types' => $types]);
    }

    public function dbRegister()
    {
        $modelCustomers = new CustomersModel();

        $phone = $this->request->getVar('phone');
        $areaCode = $this->request->getVar('areaCode');
        $name = $this->request->getVar('name');
        $lastName = $this->request->getVar('last_name');
        $dni = $this->request->getVar('dni');
        $city = $this->request->getVar('city');
        $email = $this->request->getVar('email');
        $type = $this->request->getVar('type_institution');

        $completePhone = $areaCode . $phone;

        $existingPhone = $modelCustomers->where('phone', $phone)->where('deleted', 0)->findAll();
        $existingEmail = $modelCustomers->where('email', $email)->where('deleted', 0)->findAll();

        if ($phone == '' || $areaCode == '' || $name == '' || $email == '' || $dni == '' || $city == '' || $type == '') {
            return redirect()->to('customers/register')->with('msg', ['type' => 'danger', 'body' => 'Debe completar todos los campos']);
        }

        if ($existingPhone || $existingEmail) {
            return redirect()->to('customers/register')->with('msg', ['type' => 'danger', 'body' => 'Los datos coinciden con un usuario ya registrado']);
        }

        $query = [
            'name' => $name,
            'last_name' => $lastName,
            'dni' => $dni,
            'phone' => $phone,
            'area_code' => $areaCode,
            'complete_phone' => $completePhone,
            'email' => $email,
            'type_institution' => $type,
            'deleted' => 0,
            'offer' => 0,
            'city' => $city,
        ];


        try {
            $modelCustomers->insert($query);
        } catch (\Exception $e) {
            return "Error al insertar datos: " . $e->getMessage();
        }

        return redirect()->to(base_url())->with('msg', ['type' => 'success', 'body' => 'Usuario registrado correctamente']);
    }

    public function createOffer()
    {
        return view('customers/createOffer');
    }

    public function delete($id)
    {
        $customersModel = new CustomersModel();

        try {
            $customersModel->update($id, ['deleted' => 1]);
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Cliente eliminado existosamente']);
        } catch (\Exception $e) {
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'El cliente no se pudo eliminar']);
        }
    }

    public function editWindow($id)
    {

        $customersModel = new CustomersModel();
        $valuesModel = new ValuesModel();
        $customer = $customersModel->find($id);
        $types = $valuesModel->findAll();

        return view('customers/editar', ['customer' => $customer, 'types' => $types]);
    }

    public function edit()
    {
        $customersModel = new CustomersModel();

        $id = $this->request->getVar('idCustomer');
        $phone = $this->request->getVar('phone');
        $name = $this->request->getVar('name');
        $lastName = $this->request->getVar('last_name');
        $dni = $this->request->getVar('dni');
        $offer = $this->request->getVar('offer');
        $city = $this->request->getVar('city');
        $email = $this->request->getVar('email');
        $type = $this->request->getVar('type_institution');

        $query = [
            'name' => $name,
            'last_name' => $lastName,
            'dni' => $dni,
            'phone' => $phone,
            'email' => $email,
            'type_institution' => $type,
            'offer' => $offer,
            'city' => $city
        ];

        try {
            $customersModel->update($id, $query);
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Cliente editado existosamente']);
        } catch (\Exception $e) {
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'El cliente no se pudo editar']);
        }
    }

    public function getCustomer($phone)
    {
        $customersModel = new CustomersModel();

        $customer = $customersModel->like('complete_phone', $phone, 'both')->where('deleted', 0)->first();

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $customer, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function validateCustomer($phone, $email)
    {
        $customersModel = new CustomersModel();

        $customer = $customersModel->where('phone', $phone)
            ->where('email', $email)->where('deleted', 0)->first();

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $customer, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function getCustomers()
    {
        $customersModel = new CustomersModel();

        $customers = $customersModel->where('deleted', 0)->findAll();

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $customers, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function getCustomersWithOffer()
    {
        $customersModel = new CustomersModel();

        $customers = $customersModel->where('offer', 1)->findAll();

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $customers, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function setOfferTrue()
    {
        $customersModel = new CustomersModel();
        $data = $this->request->getJSON();

        try {

            $customersModel->set(['offer' => $data])->where('offer', false)->update();

            return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }


    public function setOfferFalse()
    {
        $customersModel = new CustomersModel();
        $data = $this->request->getJSON();

        try {

            $customersModel->set(['offer' => $data])->where('offer', true)->update();

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
