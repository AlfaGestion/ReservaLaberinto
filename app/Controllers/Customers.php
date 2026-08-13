<?php

namespace App\Controllers;

use App\Models\CustomersModel;
use App\Models\BookingsModel;
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

    private function normalizePhoneCandidates(string $phone): array
    {
        $phone = trim($phone);

        if ($phone === '') {
            return [];
        }

        $digitsOnly = preg_replace('/\D+/', '', $phone) ?? '';
        $candidates = [$phone];

        if ($digitsOnly !== '' && $digitsOnly !== $phone) {
            $candidates[] = $digitsOnly;
        }

        if ($digitsOnly !== '') {
            $withoutLeadingZeros = ltrim($digitsOnly, '0');
            if ($withoutLeadingZeros !== '') {
                $candidates[] = $withoutLeadingZeros;
            }

            $withLeadingZero = $digitsOnly[0] === '0' ? $digitsOnly : '0' . $digitsOnly;
            $candidates[] = $withLeadingZero;
        }

        return array_values(array_unique(array_filter($candidates, static fn(string $candidate): bool => $candidate !== '')));
    }

    private function normalizeCustomersById(array $customers): array
    {
        $normalized = [];
        $seenIds = [];

        foreach ($customers as $customer) {
            $customerId = (int) ($customer['id'] ?? 0);

            if ($customerId <= 0 || isset($seenIds[$customerId])) {
                continue;
            }

            $seenIds[$customerId] = true;
            $normalized[] = $customer;
        }

        return $normalized;
    }

    private function indexCustomersById(array $customers): array
    {
        $indexed = [];

        foreach ($this->normalizeCustomersById($customers) as $customer) {
            $indexed[(int) $customer['id']] = $customer;
        }

        return $indexed;
    }

    private function buildActiveCustomersBuilder(CustomersModel $customersModel)
    {
        return $customersModel->builder()
            ->select('*')
            ->groupStart()
                ->where('deleted', 0)
                ->orWhere('deleted IS NULL', null, false)
            ->groupEnd();
    }

    private function applyPhoneLookupConstraints($builder, array $phoneCandidates): void
    {
        if ($phoneCandidates === []) {
            return;
        }

        $builder->groupStart();

        foreach ($phoneCandidates as $index => $candidate) {
            if ($index === 0) {
                $builder->groupStart();
            } else {
                $builder->orGroupStart();
            }

            $builder
                ->where('phone', $candidate)
                ->orWhere('complete_phone', $candidate)
                ->orLike('phone', $candidate, 'both')
                ->orLike('complete_phone', $candidate, 'both')
                ->groupEnd();
        }

        $builder->groupEnd();
    }

    private function findCustomerCandidatesByEmail(CustomersModel $customersModel, string $email): array
    {
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '') {
            return [];
        }

        $builder = $this->buildActiveCustomersBuilder($customersModel);
        $builder
            ->where('LOWER(email) = ' . $customersModel->db->escape($normalizedEmail), null, false)
            ->orderBy('id', 'DESC');

        return $this->normalizeCustomersById($builder->get()->getResultArray());
    }

    private function findCustomerCandidatesByPhone(CustomersModel $customersModel, string $phone): array
    {
        $phoneCandidates = $this->normalizePhoneCandidates($phone);

        if ($phoneCandidates === []) {
            return [];
        }

        $builder = $this->buildActiveCustomersBuilder($customersModel);
        $this->applyPhoneLookupConstraints($builder, $phoneCandidates);
        $builder->orderBy('id', 'DESC');

        return $this->normalizeCustomersById($builder->get()->getResultArray());
    }

    private function resolveCustomerTypeLabel(ValuesModel $valuesModel, string $typeValue): string
    {
        $normalizedType = trim($typeValue);

        if ($normalizedType === '') {
            return 'No indicado';
        }

        $type = $valuesModel
            ->where('value', $normalizedType)
            ->where('disabled', 0)
            ->first();

        $label = trim((string) ($type['name'] ?? ''));

        return $label !== '' ? $label : $normalizedType;
    }

    private function countCustomerReservations(BookingsModel $bookingsModel, array $customer): int
    {
        $customerId = (int) ($customer['id'] ?? 0);
        $phoneCandidates = array_values(array_unique(array_filter([
            trim((string) ($customer['phone'] ?? '')),
            trim((string) ($customer['complete_phone'] ?? '')),
        ], static fn(string $candidate): bool => $candidate !== '')));

        if ($customerId <= 0 && $phoneCandidates === []) {
            return 0;
        }

        $builder = $bookingsModel->builder()
            ->select('id')
            ->where('annulled', 0)
            ->groupStart();

        $hasCondition = false;

        if ($customerId > 0) {
            $builder->where('id_customer', $customerId);
            $hasCondition = true;
        }

        foreach ($phoneCandidates as $candidate) {
            if ($hasCondition) {
                $builder->orWhere('phone', $candidate);
                continue;
            }

            $builder->where('phone', $candidate);
            $hasCondition = true;
        }

        $builder->groupEnd();

        if (!$hasCondition) {
            return 0;
        }

        return (int) $builder->countAllResults();
    }

    private function buildValidationCustomerPayload(
        array $customer,
        ValuesModel $valuesModel,
        BookingsModel $bookingsModel,
        string $validationStatus,
        string $message,
        ?string $mismatchField = null
    ): array {
        $reservationCount = $this->countCustomerReservations($bookingsModel, $customer);
        $customerPayload = $customer;
        $customerPayload['name'] = trim((string) ($customerPayload['name'] ?? ''));
        $customerPayload['last_name'] = trim((string) ($customerPayload['last_name'] ?? ''));
        $customerPayload['phone'] = trim((string) ($customerPayload['phone'] ?? ''));
        $customerPayload['complete_phone'] = trim((string) ($customerPayload['complete_phone'] ?? ''));
        $customerPayload['email'] = trim((string) ($customerPayload['email'] ?? ''));
        $customerPayload['city'] = trim((string) ($customerPayload['city'] ?? ''));
        $customerPayload['type_institution'] = trim((string) ($customerPayload['type_institution'] ?? ''));
        $customerPayload['type_label'] = $this->resolveCustomerTypeLabel($valuesModel, (string) ($customerPayload['type_institution'] ?? ''));
        $customerPayload['reservations_count'] = $reservationCount;
        $customerPayload['validation_status'] = $validationStatus;
        $customerPayload['validation_message'] = $message;
        $customerPayload['mismatch_field'] = $mismatchField;

        return [
            'validation_status' => $validationStatus,
            'message' => $message,
            'mismatch_field' => $mismatchField,
            'reservation_count' => $reservationCount,
            'customer' => $customerPayload,
        ];
    }

    private function buildValidationNoticePayload(string $validationStatus, string $message): array
    {
        return [
            'validation_status' => $validationStatus,
            'message' => $message,
            'mismatch_field' => null,
            'reservation_count' => 0,
            'customer' => null,
        ];
    }

    private function resolveValidationCustomer(CustomersModel $customersModel, BookingsModel $bookingsModel, ValuesModel $valuesModel, string $phone, string $email): array
    {
        $normalizedPhone = trim($phone);
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedPhone === '' && $normalizedEmail === '') {
            return $this->buildValidationNoticePayload('not_found', 'Ingresá teléfono y email para validar tu reserva.');
        }

        $phoneCustomers = $this->indexCustomersById($this->findCustomerCandidatesByPhone($customersModel, $normalizedPhone));
        $emailCustomers = $this->indexCustomersById($this->findCustomerCandidatesByEmail($customersModel, $normalizedEmail));

        $phoneIds = array_keys($phoneCustomers);
        $emailIds = array_keys($emailCustomers);
        $commonIds = array_values(array_intersect($phoneIds, $emailIds));

        if (count($commonIds) === 1) {
            $customerId = (int) $commonIds[0];
            $customer = $phoneCustomers[$customerId] ?? $emailCustomers[$customerId] ?? null;

            if ($customer !== null) {
                return $this->buildValidationCustomerPayload(
                    $customer,
                    $valuesModel,
                    $bookingsModel,
                    'exact',
                    'Tus datos están registrados. Podés continuar para realizar una nueva reserva.'
                );
            }
        }

        if (count($commonIds) > 1) {
            return $this->buildValidationNoticePayload(
                'ambiguous',
                'Los datos ingresados corresponden a registros diferentes. Revisá el teléfono y el email.'
            );
        }

        if (count($phoneIds) === 1 && count($emailIds) === 1) {
            return $this->buildValidationNoticePayload(
                'ambiguous',
                'Los datos ingresados corresponden a registros diferentes. Revisá el teléfono y el email.'
            );
        }

        if (count($phoneIds) === 1 && count($emailIds) !== 1) {
            $customerId = (int) $phoneIds[0];
            $customer = $phoneCustomers[$customerId] ?? null;

            if ($customer !== null) {
                return $this->buildValidationCustomerPayload(
                    $customer,
                    $valuesModel,
                    $bookingsModel,
                    'phone',
                    'Encontramos un cliente registrado por teléfono, pero el email ingresado no coincide con nuestros registros.',
                    'email'
                );
            }
        }

        if (count($emailIds) === 1 && count($phoneIds) !== 1) {
            $customerId = (int) $emailIds[0];
            $customer = $emailCustomers[$customerId] ?? null;

            if ($customer !== null) {
                return $this->buildValidationCustomerPayload(
                    $customer,
                    $valuesModel,
                    $bookingsModel,
                    'email',
                    'Encontramos un cliente registrado por email, pero el teléfono ingresado no coincide con nuestros registros.',
                    'phone'
                );
            }
        }

        if ($phoneIds !== [] || $emailIds !== []) {
            return $this->buildValidationNoticePayload(
                'ambiguous',
                'Los datos ingresados corresponden a registros diferentes. Revisá el teléfono y el email.'
            );
        }

        return $this->buildValidationNoticePayload(
            'not_found',
            'No encontramos un cliente con esos datos. Podés registrarte para continuar.'
        );
    }

    private function findCustomerByEmail(CustomersModel $customersModel, string $email): ?array
    {
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '') {
            return null;
        }

        $builder = $this->buildActiveCustomersBuilder($customersModel);
        $builder
            ->where('LOWER(email) = ' . $customersModel->db->escape($normalizedEmail), null, false)
            ->orderBy('id', 'DESC');

        return $this->resolvePreferredCustomer($builder->get()->getResultArray());
    }

    private function findCustomerByPhoneAndEmail(CustomersModel $customersModel, string $phone, string $email): ?array
    {
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '') {
            return null;
        }

        $phoneCandidates = $this->normalizePhoneCandidates($phone);
        $builder = $this->buildActiveCustomersBuilder($customersModel);
        $this->applyPhoneLookupConstraints($builder, $phoneCandidates);
        $builder
            ->where('LOWER(email) = ' . $customersModel->db->escape($normalizedEmail), null, false)
            ->orderBy('id', 'DESC');

        return $this->resolvePreferredCustomer($builder->get()->getResultArray());
    }

    private function findCustomerByPhone(CustomersModel $customersModel, string $phone): ?array
    {
        $phoneCandidates = $this->normalizePhoneCandidates($phone);

        if ($phoneCandidates === []) {
            return null;
        }

        $builder = $this->buildActiveCustomersBuilder($customersModel);
        $this->applyPhoneLookupConstraints($builder, $phoneCandidates);
        $builder->orderBy('id', 'DESC');

        return $this->resolvePreferredCustomer($builder->get()->getResultArray());
    }

    private function findExistingCustomerForRegistration(CustomersModel $customersModel, string $phone, string $email): ?array
    {
        $customer = $this->findCustomerByPhoneAndEmail($customersModel, $phone, $email);

        if ($customer !== null) {
            return $customer;
        }

        $customer = $this->findCustomerByPhone($customersModel, $phone);

        if ($customer !== null) {
            return $customer;
        }

        return $this->findCustomerByEmail($customersModel, $email);
    }

    private function decorateValidationCustomer(?array $customer, string $matchType, bool $requiresConfirmation = false): ?array
    {
        if ($customer === null) {
            return null;
        }

        $customer['match_type'] = $matchType;
        $customer['requires_confirmation'] = $requiresConfirmation;

        return $customer;
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

        $phone = trim((string) $this->request->getVar('phone'));
        $name = trim((string) $this->request->getVar('name'));
        $lastName = trim((string) $this->request->getVar('last_name'));
        $dni = trim((string) $this->request->getVar('dni'));
        $city = trim((string) $this->request->getVar('city'));
        $email = trim((string) $this->request->getVar('email'));
        $type = trim((string) $this->request->getVar('type_institution'));
        $isEmbedded = $this->request->getVar('embed') === '1';

        $completePhone = $phone;

        if ($phone == '' || $name == '' || $email == '' || $dni == '' || $city == '' || $type == '') {
            $query = http_build_query(array_filter([
                'embed' => $isEmbedded ? 1 : null,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
            ]));

            return redirect()->to('Registrarme' . ($query ? '?' . $query : ''))
                ->withInput()
                ->with('msg', ['type' => 'danger', 'body' => 'Completá todos los campos']);
        }

        $existingCustomer = $this->findExistingCustomerForRegistration($modelCustomers, $phone, $email);

        if ($existingCustomer !== null) {
            $query = http_build_query(array_filter([
                'embed' => $isEmbedded ? 1 : null,
                'existing' => 1,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
            ]));

            return redirect()->to('Registrarme' . ($query ? '?' . $query : ''))
                ->withInput()
                ->with('msg', ['type' => 'info', 'body' => 'Tus datos ya están registrados en el sistema.']);
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
                ->with('msg', ['type' => 'danger', 'body' => 'No pudimos completar el alta. Intentá nuevamente']);
        }

        if ($isEmbedded) {
            return view('customers/embed_result', [
                'message' => 'Cliente guardado con éxito',
                'action' => 'created',
                'customer' => $modelCustomers->find($newId),
            ]);
        }

        $redirectUrl = base_url('?registered=1&phone=' . rawurlencode((string) $phone) . '&email=' . rawurlencode((string) $email));
        return redirect()->to($redirectUrl)->with('msg', ['type' => 'success', 'body' => 'Usuario registrado con éxito']);
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
                    'message' => 'Cliente actualizado con éxito',
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
            return  $this->response->setJSON($this->setResponse(null, null, $customer, 'Operación completada'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function validateCustomer($phone, $email)
    {
        $customersModel = new CustomersModel();
        $bookingsModel = new BookingsModel();
        $valuesModel = new ValuesModel();

        $payload = $this->resolveValidationCustomer($customersModel, $bookingsModel, $valuesModel, trim((string) $phone), trim((string) $email));

        try {
            return $this->response->setJSON($this->setResponse(null, null, $payload, 'Operacion completada'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }

        $normalizedPhone = trim((string) $phone);
        $normalizedEmail = trim((string) $email);

        if ($normalizedPhone === '' || $normalizedEmail === '') {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Debe ingresar telefono y email'));
        }

        $customer = $this->findCustomerByPhoneAndEmail($customersModel, $normalizedPhone, $normalizedEmail);

        if ($customer === null) {
            $customer = $this->findCustomerByEmail($customersModel, $normalizedEmail);

            if ($customer !== null) {
                $customer = $this->decorateValidationCustomer($customer, 'email', true);
            }
        } else {
            $customer = $this->decorateValidationCustomer($customer, 'phone_email', false);
        }

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $customer, 'Operación completada'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function validateCustomerLookup()
    {
        $customersModel = new CustomersModel();
        $bookingsModel = new BookingsModel();
        $valuesModel = new ValuesModel();
        $jsonData = $this->request->getJSON(true);
        $rawData = $this->request->getRawInput();

        $phone = trim((string) ($jsonData['phone'] ?? $rawData['phone'] ?? $this->request->getVar('phone') ?? ''));
        $email = trim((string) ($jsonData['email'] ?? $rawData['email'] ?? $this->request->getVar('email') ?? ''));

        $payload = $this->resolveValidationCustomer($customersModel, $bookingsModel, $valuesModel, $phone, $email);

        try {
            return $this->response->setJSON($this->setResponse(null, null, $payload, 'Operacion completada'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }

        if ($phone === '' || $email === '') {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Debe ingresar telefono y email'));
        }

        $customer = $this->findCustomerByPhoneAndEmail($customersModel, $phone, $email);

        if ($customer === null) {
            $customer = $this->findCustomerByEmail($customersModel, $email);

            if ($customer !== null) {
                $customer = $this->decorateValidationCustomer($customer, 'email', true);
            }
        } else {
            $customer = $this->decorateValidationCustomer($customer, 'phone_email', false);
        }

        try {
            return $this->response->setJSON($this->setResponse(null, null, $customer, 'Operación completada'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function getCustomers()
    {
        $customersModel = new CustomersModel();

        $customers = $customersModel->where('deleted', 0)->findAll();

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $customers, 'Operación completada'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function getCustomersWithOffer()
    {
        $customersModel = new CustomersModel();

        $customers = $customersModel->where('offer', 1)->findAll();

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $customers, 'Operación completada'));
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

            return  $this->response->setJSON($this->setResponse(null, null, null, 'Operación completada'));
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

            return  $this->response->setJSON($this->setResponse(null, null, null, 'Operación completada'));
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

