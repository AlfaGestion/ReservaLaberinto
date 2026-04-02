<?php

namespace App\Controllers;

use App\Models\CustomersModel;
use App\Models\ValuesModel;

class Customers extends BaseController
{
    private function resolvePreferredCustomer(array $customers): ?array
    {
        if ($customers === []) {
            return null;
        }

        usort($customers, static function (array $left, array $right): int {
            $leftScore = 0;
            $rightScore = 0;

            if (!empty($left['type_institution'])) {
                $leftScore += 100;
            }
            if (!empty($right['type_institution'])) {
                $rightScore += 100;
            }

            if (!empty($left['email'])) {
                $leftScore += 50;
            }
            if (!empty($right['email'])) {
                $rightScore += 50;
            }

            if ($leftScore === $rightScore) {
                return ((int) ($right['id'] ?? 0)) <=> ((int) ($left['id'] ?? 0));
            }

            return $rightScore <=> $leftScore;
        });

        return $customers[0] ?? null;
    }


    public function register()
    {
        $valuesModel = new ValuesModel();
        $types = $valuesModel->findAll();
        $isEmbedded = $this->request->getGet('embed') === '1';

        return view('customers/register', [
            'types' => $types,
            'prefillPhone' => $this->request->getGet('phone') ?? '',
            'prefillEmail' => $this->request->getGet('email') ?? '',
            'returnValidate' => $this->request->getGet('returnValidate') === '1',
            'isEmbedded' => $isEmbedded,
        ]);
    }

    public function dbRegister()
    {
        $modelCustomers = new CustomersModel();

        $phone = $this->request->getVar('phone');
        $name = $this->request->getVar('name');
        $lastName = $this->request->getVar('last_name');
        $dni = $this->request->getVar('dni');
        $city = $this->request->getVar('city');
        $email = $this->request->getVar('email');
        $type = $this->request->getVar('type_institution');
        $isEmbedded = $this->request->getVar('embed') === '1';

        $completePhone = $phone;

        $existingPhone = $modelCustomers->where('phone', $phone)->where('deleted', 0)->findAll();
        $existingEmail = $modelCustomers->where('email', $email)->where('deleted', 0)->findAll();

        if ($phone == '' || $name == '' || $email == '' || $dni == '' || $city == '' || $type == '') {
            $query = http_build_query(array_filter([
                'embed' => $isEmbedded ? 1 : null,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
            ]));

            return redirect()->to('Registrarme' . ($query ? '?' . $query : ''))
                ->withInput()
                ->with('msg', ['type' => 'danger', 'body' => 'Debe completar todos los campos']);
        }

        if ($existingPhone || $existingEmail) {
            $query = http_build_query(array_filter([
                'embed' => $isEmbedded ? 1 : null,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
            ]));

            return redirect()->to('Registrarme' . ($query ? '?' . $query : ''))
                ->withInput()
                ->with('msg', ['type' => 'danger', 'body' => 'Los datos coinciden con un usuario ya registrado']);
        }

        $query = [
            'name' => $name,
            'last_name' => $lastName,
            'dni' => $dni,
            'phone' => $phone,
            'complete_phone' => $completePhone,
            'email' => $email,
            'type_institution' => $type,
            'deleted' => 0,
            'offer' => 0,
            'city' => $city,
        ];


        try {
            $newId = $modelCustomers->insert($query);
        } catch (\Exception $e) {
            $query = http_build_query(array_filter([
                'embed' => $isEmbedded ? 1 : null,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
            ]));

            return redirect()->to('Registrarme' . ($query ? '?' . $query : ''))
                ->withInput()
                ->with('msg', ['type' => 'danger', 'body' => 'No se pudo guardar el alta. Intente nuevamente']);
        }

        if ($isEmbedded) {
            return view('customers/embed_result', [
                'message' => 'Cliente guardado correctamente',
                'action' => 'created',
                'customer' => $modelCustomers->find($newId),
            ]);
        }

        $redirectUrl = base_url('/?registered=1&phone=' . rawurlencode((string) $phone) . '&email=' . rawurlencode((string) $email));
        return redirect()->to($redirectUrl)->with('msg', ['type' => 'success', 'body' => 'Usuario registrado correctamente']);
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
        $isEmbedded = $this->request->getGet('embed') === '1';

        return view('customers/editar', ['customer' => $customer, 'types' => $types, 'isEmbedded' => $isEmbedded]);
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
        $isEmbedded = $this->request->getVar('embed') === '1';

        $query = [
            'name' => $name,
            'last_name' => $lastName,
            'dni' => $dni,
            'phone' => $phone,
            'complete_phone' => $phone,
            'email' => $email,
            'type_institution' => $type,
            'offer' => $offer,
            'city' => $city
        ];

        try {
            $customersModel->update($id, $query);
            if ($isEmbedded) {
                return view('customers/embed_result', [
                    'message' => 'Cliente editado correctamente',
                    'action' => 'updated',
                    'customer' => $customersModel->find($id),
                ]);
            }
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Cliente editado existosamente']);
        } catch (\Exception $e) {
            if ($isEmbedded) {
                return redirect()->to('customers/editWindow/' . $id . '?embed=1')->with('msg', ['type' => 'danger', 'body' => 'El cliente no se pudo editar']);
            }
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'El cliente no se pudo editar']);
        }
    }

    public function getCustomer($phone)
    {
        $customersModel = new CustomersModel();
        $normalizedPhone = trim((string) $phone);
        $customers = $customersModel->builder()
            ->select('*')
            ->groupStart()
                ->like('complete_phone', $normalizedPhone, 'both')
                ->orWhere('phone', $normalizedPhone)
            ->groupEnd()
            ->groupStart()
                ->where('deleted', 0)
                ->orWhere('deleted IS NULL', null, false)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
        $customer = $this->resolvePreferredCustomer($customers);

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $customer, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function validateCustomer($phone, $email)
    {
        $customersModel = new CustomersModel();
        $normalizedPhone = trim((string) $phone);
        $normalizedEmail = strtolower(trim((string) $email));

        $customers = $customersModel->builder()
            ->select('*')
            ->groupStart()
                ->where('phone', $normalizedPhone)
                ->orWhere('complete_phone', $normalizedPhone)
            ->groupEnd()
            ->where("LOWER(email) = '{$normalizedEmail}'", null, false)
            ->groupStart()
                ->where('deleted', 0)
                ->orWhere('deleted IS NULL', null, false)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
        $customer = $this->resolvePreferredCustomer($customers);

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $customer, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function validateCustomerLookup()
    {
        $customersModel = new CustomersModel();
        $jsonData = $this->request->getJSON(true);
        $rawData = $this->request->getRawInput();

        $phone = trim((string) ($jsonData['phone'] ?? $rawData['phone'] ?? $this->request->getVar('phone') ?? ''));
        $email = strtolower(trim((string) ($jsonData['email'] ?? $rawData['email'] ?? $this->request->getVar('email') ?? '')));

        $customers = $customersModel->builder()
            ->select('*')
            ->groupStart()
                ->where('phone', $phone)
                ->orWhere('complete_phone', $phone)
            ->groupEnd()
            ->where("LOWER(email) = '{$email}'", null, false)
            ->groupStart()
                ->where('deleted', 0)
                ->orWhere('deleted IS NULL', null, false)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
        $customer = $this->resolvePreferredCustomer($customers);

        try {
            return $this->response->setJSON($this->setResponse(null, null, $customer, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
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
