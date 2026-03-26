<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\PrintBookings;
use App\Models\BookingsModel;
use App\Models\CustomersModel;
use App\Models\FieldsModel;
use App\Models\MercadoPagoModel;
use App\Models\PaymentsModel;
use App\Models\TimeModel;
use App\Models\UsersModel;
use App\Models\OffersModel;
use App\Models\UploadModel;
use App\Models\RateModel;
use App\Models\ValuesModel;
use CodeIgniter\I18n\Time;

class Bookings extends BaseController
{
    public function saveBooking()
    {
        $bookingsModel = new BookingsModel();
        $customersModel = new CustomersModel();

        $data = $this->request->getJSON();
        // log_message('info', 'Procesando reserva: ' . json_encode($data, JSON_PRETTY_PRINT));
        $searchTerm = '%' . $data->telefono . '%';

        $queryCustomer = [
            'name'  => $data->nombre,
            'phone' => $data->telefono,
            'area_code' => $data->codigoArea,
            'offer' => 0,
        ];

        // Verificar si ya existe una reserva activa
        $existingBooking = $bookingsModel->where('date', $data->fecha)
            ->where('id_field', $data->cancha)
            ->where('time_from', $data->horarioDesde)
            ->where('time_until', $data->horarioHasta)
            ->where('annulled', 0) // Solo trae las no anuladas
            ->first();

        if ($existingBooking) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Ya existe una reserva activa para esa fecha, servicio y horario.'));
        }

        // $idCustomer = $customersModel->where('phone', $data->telefono)->first()['id'] ?? null;
        $existingCustomer = $customersModel->where('phone', 'like', $searchTerm)->first();
        // $existingCustomer = $customersModel->where('phone', $data->telefono)->first();
        $exist = true;

        if ($existingCustomer) {
            $exist = false;

            $queryCustomer = [
                'offer' => 0,
                'quantity' => $existingCustomer['quantity'] + 1
            ];

            $customersModel->update($existingCustomer['id'], $queryCustomer);
        }

        if ($exist) {
            $customersModel->insert($queryCustomer);
            $idCustomer = $customersModel->getInsertID();
        }

        $queryBooking = [
            'date'                  => $data->fecha,
            'id_field'              => $data->cancha,
            'time_from'             => $data->horarioDesde,
            'time_until'            => $data->horarioHasta,
            'name'                  => $data->nombre,
            'visitors'              => $data->visitantes,
            'phone'                 => $data->codigoArea . $data->telefono,
            'payment'               => $data->monto,
            'approved'              => 0,
            'total'                 => $data->total,
            'parcial'               => $data->parcial,
            'diference'             => $data->diferencia,
            'reservation'           => $data->reservacion,
            'code'                  => $this->codeGenerate(),
            'total_payment'         => $data->pagoTotal,
            'payment_method'        => $data->metodoDePago,
            'id_preference_parcial' => $data->preferenceIdParcial,
            'id_preference_total'   => $data->preferenceIdTotal,
            'use_offer'             => $data->oferta,
            'booking_time'          => date("Y-m-d H:i:s"),
            'mp'                    => 0,
            'annulled'              => 0, // Aseguramos que este nuevo registro no esté anulado
            'id_customer'           => $idCustomer,
        ];


        try {
            if (count($queryBooking) != 0) {
                $bookingsModel->insert($queryBooking);
                return $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
            }
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }



    public function getBookings($fecha)
    {
        $bookingsModel = new BookingsModel();
        $fieldsModel = new FieldsModel();
        $timeModel = new TimeModel();

        $time = $timeModel->getOpeningTime();

        if ($fecha != '') {
            $bookings = $bookingsModel->where('date', $fecha)->where('annulled', 0)->findAll();
        }

        $timeBookings = [];

        foreach ($bookings as $booking) {
            $found = false;

            foreach ($timeBookings as &$timeBooking) {
                if (intval($timeBooking['id_cancha']) === intval($booking['id_field'])) {
                    $indexFrom = array_search($booking['time_from'], $time);
                    $indexUntil = array_search($booking['time_until'], $time);

                    // ✅ Cambio clave: UNTIL ahora es exclusivo
                    for ($currentTime = $indexFrom; $currentTime < $indexUntil; $currentTime++) {
                        $timeBooking['time'][] = $time[$currentTime];
                    }

                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $reserva = [
                    'id_cancha' => $booking['id_field'],
                    'nombre_cancha' => $fieldsModel->getField($booking['id_field'])['name'],
                    'time' => [],
                ];

                $indexFrom = array_search($booking['time_from'], $time);
                $indexUntil = array_search($booking['time_until'], $time);

                // ✅ Cambio clave: UNTIL ahora es exclusivo
                for ($currentTime = $indexFrom; $currentTime < $indexUntil; $currentTime++) {
                    $reserva['time'][] = $time[$currentTime];
                }

                $timeBookings[] = $reserva;
            }
        }


        if ($bookings) {
            try {
                return $this->response->setJSON($this->setResponse(null, null, [
                    'reservas' => $timeBookings,
                    'canchas' => $fieldsModel->findAll()
                ], 'Respuesta exitosa'));
            } catch (\Exception $e) {
                return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        } else {
            log_message('info', 'No hay reservas para la fecha seleccionada');
            return $this->response->setJSON($this->setResponse(200, true, null, 'No hay reservas para la fecha seleccionada'));
        }
    }



    public function completePayment($id)
    {
        $bookingsModel = new BookingsModel();
        $paymentsModel = new PaymentsModel();
        $data = $this->request->getJSON();
        $booking = $bookingsModel->getBooking($id);

        $total = floatval($booking['total']);
        $pagoAnterior = floatval($booking['payment']);
        $nuevoPago = floatval($data->pago);
        $nuevoAcumulado = $pagoAnterior + $nuevoPago;

        // ✅ Validación para evitar pagos duplicados o montos excedidos
        if ($nuevoPago <= 0 || $nuevoAcumulado > $total) {
            return $this->response->setJSON(
                $this->setResponse(400, true, null, 'El monto es inválido o excede el total de la reserva.')
            );
        }

        $diferencia = $total - $nuevoAcumulado;
        $pagoTotal = abs($total - $nuevoAcumulado) < 0.01 ? 1 : 0;

        $queryBookings = [
            'total_payment' => $pagoTotal,
            'payment' => $nuevoAcumulado,
            'diference' => $diferencia,
        ];

        $queryPayments = [
            'id_user' => $data->idUser,
            'id_booking' => $id,
            'id_customer' => $data->idCustomer,
            'amount' => $nuevoPago,
            'payment_method' => $data->medioPago,
            'date' => Time::now()->toDateString(),
            'created_at' => Time::now(),
        ];

        try {
            $bookingsModel->update($id, $queryBookings);
            $paymentsModel->insert($queryPayments);
            return $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }




    public function getBooking($id)
    {
        $bookingsModel = new BookingsModel();
        $booking = $bookingsModel->getBooking($id);

        if ($booking) {
            try {
                return  $this->response->setJSON($this->setResponse(null, null, $booking, 'Respuesta exitosa'));
            } catch (\Exception $e) {
                return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        }
    }

    public function getReports()
    {
        $usersModel = new UsersModel();
        $paymentsModel = new PaymentsModel();
        $customersModel = new CustomersModel();
        $data = $this->request->getJSON();
        $user = $data->user == '' ? 'all' : $data->user;

        $query = $paymentsModel->select('*')
            ->where('date >=', $data->fechaDesde)
            ->where('date <=', $data->fechaHasta);

        if ($user !== 'all') {
            $query->where('id_user', $user);
        }

        $paymentsResult = $query->findAll();

        $payments = [];

        foreach ($paymentsResult as $payment) {
            log_message('info', 'Procesando pago: ' . json_encode($payment, JSON_PRETTY_PRINT));
            $pago = [
                'fecha' => date("d/m/Y", strtotime($payment['date'])),
                'pago' => $payment['amount'],
                'usuario' => $usersModel->getUserName($payment['id_user']),
                'idUsuario' => $payment['id_user'],
                'cliente' => !empty($payment['id_customer'])
                    ? $customersModel->getCustomerName($payment['id_customer'])
                    : '',
                'telefonoCliente' => !empty($payment['id_customer'])
                    ? $customersModel->getCustomerPhone($payment['id_customer'])
                    : '',
                'metodoPago' => $payment['payment_method'],
                'idMercadoPago' => $payment['id_mercado_pago'],
            ];

            array_push($payments, $pago);
        }

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $payments, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function cancelBooking()
    {
        $mercadoPagoModel = new MercadoPagoModel();
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();
        $idBooking = $data->idBooking;
        $mpPayment = $mercadoPagoModel->where('id_booking', $idBooking)->first();

        try {
            if (isset($mpPayment)) {
                $mercadoPagoModel->update($mpPayment['id'], ['annulled' => 1]);
            }
            $bookingsModel->update($idBooking, ['annulled' => 1]);

            return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function editBooking()
    {
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();

        $idBooking = $data->bookingId;

        $queryUpdate = [
            'id_field' => $data->cancha,
            'diference' => $data->diferencia,
            'date' => $data->fecha,
            'time_from' => $data->horarioDesde,
            'time_until' => $data->horarioHasta,
            'total_payment' => $data->pagoTotal,
            'parcial' => $data->parcial,
            'total' => $data->total,
            'visitors' => $data->visitantes
        ];

        try {
            $bookingsModel->update($idBooking, $queryUpdate);

            return  $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function getMpPayments()
    {
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();

        $bookings = $bookingsModel->where('date >=', $data->fechaDesde)
            ->where('date <=', $data->fechaHasta)
            ->findAll();

        $reservations = [];

        foreach ($bookings as $booking) {
            $fecha = date("d/m/Y", strtotime($booking['date']));
            $reservation = intval($booking['reservation']);

            if (array_key_exists($fecha, $reservations)) {
                $reservations[$fecha] += $reservation;
            } else {
                $reservations[$fecha] = $reservation;
            }
        }

        $result = [];

        foreach ($reservations as $fecha => $pago) {
            $result[] = [
                'fecha' => $fecha,
                'reserva' => $pago
            ];
        }

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $result, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function confirmMP()
    {
        $bookingsModel = new BookingsModel();
        $data = $this->request->getJSON();

        try {
            $bookingsModel->update($data->bookingId, ['mp' => $data->confirm]);

            return $this->response->setJSON($this->setResponse(null, null, null, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function saveAdminBooking()
    {
        $bookingsModel = new BookingsModel();
        $paymentsModel = new PaymentsModel();
        $customersModel = new CustomersModel();

        $data = $this->request->getJSON();

        // Validaciones mínimas
        if (!$data || empty($data->fecha) || empty($data->nombre) || empty($data->monto) || empty($data->total) || empty($data->cancha)) {
            return $this->response->setJSON($this->setResponse(400, true, null, 'Faltan datos obligatorios.'));
        }

        $pagoTotal = ($data->monto == $data->total) ? 1 : 0;
        $telefonoCompleto = $data->codigoArea . $data->telefono;

        // Intentar buscar cliente por teléfono
        $existingCustomer = $customersModel->where('phone', $data->telefono)->first();
        $idCustomer = $data->idCustomer ?? null;

        // Armar reserva
        $queryBooking = [
            'date'            => $data->fecha,
            'id_field'        => $data->cancha,
            'time_from'       => $data->horarioDesde,
            'time_until'      => $data->horarioHasta,
            'name'            => $data->nombre,
            'phone'           => $telefonoCompleto,
            'payment'         => $data->monto,
            'total'           => $data->total,
            'visitors'        => $data->visitantes,
            'description'     => $data->descripcion,
            'code'            => $this->codeGenerate(),
            'diference'       => floatVal($data->total) - floatVal($data->monto),
            'total_payment'   => $pagoTotal,
            'payment_method'  => $data->metodoDePago,
            'approved'        => 1,
            'mp'              => 1,
            'annulled'        => 0,
            'id_customer'     => $idCustomer,
        ];

        try {
            $bookingInsert = $bookingsModel->insert($queryBooking, true);

            if ($bookingInsert) {
                $queryPayment = [
                    'id_booking'     => $bookingsModel->getInsertID(),
                    'id_user'        => session()->get('id_user'),
                    'id_customer'    => $idCustomer,
                    'amount'         => $data->monto,
                    'payment_method' => $data->metodoDePago,
                    'date'           => $data->fecha,
                ];

                // log_message('info', 'Datos recibidos: ' . print_r($queryPayment, true));
                $paymentsModel->insert($queryPayment);

                if ($existingCustomer) {
                    $idCustomer = $existingCustomer['id'];
                    $queryCustomer = [
                        'offer' => 0,
                        'quantity' => $existingCustomer['quantity'] + 1
                    ];
                    $customersModel->update($existingCustomer['id'], $queryCustomer);
                } else {
                    $queryCustomer = [
                        'name'  => $data->nombre,
                        'phone' => $data->telefono,
                        'area_code' => $data->codigoArea,
                        'quantity' => 1,
                        'offer' => 0,
                    ];
                    $customersModel->insert($queryCustomer);
                    $idCustomer = $customersModel->getInsertID();
                }
            }

            return $this->response->setJSON($this->setResponse(null, null, null, 'Reserva guardada exitosamente'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(500, true, null, 'Error al guardar la reserva: ' . $e->getMessage()));
        }
    }


    public function bookingPdf($bookingId)
    {
        $pdfLibrary = new PrintBookings();
        $bookingsModel = new BookingsModel();
        $mercadoPagoModel = new MercadoPagoModel();
        $fieldsModel = new FieldsModel();

        $booking = $bookingsModel->getBooking($bookingId);
        $mpPayment = $mercadoPagoModel->where('id_booking', $bookingId)->first();

        //Generar PDF
        $printData = [
            'nombre' => $booking['name'],
            'telefono' => $booking['phone'],
            'fecha' => $booking['date'],
            'horario' => $booking['time_from'] . 'hs' . ' a ' . $booking['time_until'] . 'hs',
            'servicio' => $fieldsModel->getField($booking['id_field'])['name'],
            'total_servicio' => '$' . $booking['total'],
            'pagado' => '$' . $booking['payment'],
            'saldo' => '$' . $booking['diference'],
            'detalle' => $booking['description'],
        ];

        if ($mpPayment) {
            $printData['id_mercado_pago'] = $mpPayment['payment_id'];
            $printData['estado_pago'] = $mpPayment['status'];
        };

        $pdfLibrary->printBooking($printData);
    }

    public function generateReportPdf($user, $fechaDesde, $fechaHasta)
    {
        $usersModel = new UsersModel();
        $paymentsModel = new PaymentsModel();
        $customersModel = new CustomersModel();
        $pdfLibrary = new PrintBookings();

        $query = $paymentsModel->select('*')
            ->where('date >=', $fechaDesde)
            ->where('date <=', $fechaHasta);

        if ($user !== 'all') {
            $query->where('id_user', $user);
        }

        $paymentsResult = $query->findAll();

        $payments = [];

        foreach ($paymentsResult as $payment) {
            $pago = [
                'fecha' => date("d/m/Y", strtotime($payment['date'])),
                'pago' => $payment['amount'],
                'usuario' => $usersModel->getUserName($payment['id_user']),
                'idUsuario' => $payment['id_user'],
                'cliente' => $customersModel->getCustomerName($payment['id_customer']),
                'telefonoCliente' => $customersModel->getCustomerPhone($payment['id_customer']),
                'metodoPago' => $payment['payment_method'],
                'idMercadoPago' => $payment['id_mercado_pago'],
            ];

            array_push($payments, $pago);
        }

        $pdfLibrary->printReports($payments);
    }

    public function generatePaymentsReportPdf($fechaDesde, $fechaHasta)
    {
        $bookingsModel = new BookingsModel();
        $pdfLibrary = new PrintBookings();

        $bookings = $bookingsModel->where('date >=', $fechaDesde)
            ->where('date <=', $fechaHasta)
            ->findAll();

        $reservations = [];

        foreach ($bookings as $booking) {
            $fecha = date("d/m/Y", strtotime($booking['date']));
            $reservation = intval($booking['reservation']);

            if (array_key_exists($fecha, $reservations)) {
                $reservations[$fecha] += $reservation;
            } else {
                $reservations[$fecha] = $reservation;
            }
        }

        $result = [];

        foreach ($reservations as $fecha => $pago) {
            $result[] = [
                'fecha' => $fecha,
                'reserva' => $pago
            ];
        }

        $pdfLibrary->printPaymentsReports($result);
    }

    public function scheduleAvailability($idField)
    {
        $bookingsModel = new BookingsModel();
        $timeModel = new TimeModel();

        // 1. Obtener el punto de partida y el rango de 7 días.
        $today = Time::now('America/Argentina/Buenos_Aires');

        // El punto de inicio de la disponibilidad es MAÑANA.
        $startDay = $today->addDays(1);

        // ⬇️ CORRECCIÓN 1: Definir el final del rango como 6 días después del día de inicio (7 días en total).
        $endOfRange = $startDay->addDays(6)->toDateString();

        $currentWeek = [];
        $currentDay = $startDay; // El bucle comienza en mañana

        // Iteramos exactamente por 7 días (Mañana hasta el final del rango).
        while ($currentDay->toDateString() <= $endOfRange) {
            $currentWeek[] = $currentDay->toDateString();
            $currentDay = $currentDay->addDays(1);
        }

        // 2. Obtener el horario de apertura. (Sin cambios)
        $openingHours = $timeModel->getOpeningTime();

        // 3. Generar todos los lapsos de 2 horas posibles. (Sin cambios)
        $possibleSlots = [];
        $hoursCount = count($openingHours);
        for ($i = 0; $i < $hoursCount - 4; $i += 4) {
            $start = $openingHours[$i];
            if (isset($openingHours[$i + 4])) {
                $end = $openingHours[$i + 4];
                $possibleSlots[] = [
                    'from' => $start,
                    'until' => $end,
                ];
            }
        }

        // 4. Obtener las reservas existentes para el nuevo rango de 7 días.
        $reservedBookings = $bookingsModel
            ->where('id_field', $idField)
            ->where('date >=', $startDay->toDateString())
            ->where('date <=', $endOfRange) // Usamos el nuevo final del rango
            ->where('annulled', 0)
            ->findAll();

        // Crear una lista de reservas existentes. (Sin cambios)
        $reservedSlots = [];
        foreach ($reservedBookings as $booking) {
            $date = $booking['date'];
            $from = $booking['time_from'];
            $until = $booking['time_until'];

            $reservedSlots[$date][] = [
                'from' => $from,
                'until' => $until
            ];
        }

        $availability = [];
        $dayMapping = [
            'Sunday' => 'is_sunday',
            'Monday' => 'is_monday',
            'Tuesday' => 'is_tuesday',
            'Wednesday' => 'is_wednesday',
            'Thursday' => 'is_thursday',
            'Friday' => 'is_friday',
            'Saturday' => 'is_saturday'
        ];

        $dayNamesSpanish = [
            'Sunday' => 'domingos',
            'Monday' => 'lunes',
            'Tuesday' => 'martes',
            'Wednesday' => 'miércoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sábados'
        ];

        foreach ($currentWeek as $date) {
            $currentDay = Time::parse($date);
            $dayName = $currentDay->format('l');

            // ⬇️ CORRECCIÓN 2: Lógica de días cerrados (Restaurada y Funcional)
            $dayKey = $dayMapping[$dayName] ?? null;

            if ($dayKey && ($openingHours[$dayKey] ?? null) == 1) {
                $dayNameInSpanish = $dayNamesSpanish[$dayName] ?? $dayName;
                $availability[] = [
                    'date' => $currentDay->format('d/m/Y'),
                    'available_slots' => ["Cerrado los {$dayNameInSpanish}"]
                ];
                continue; // Pasa al siguiente día del bucle si está cerrado.
            }

            $availableToday = [];
            foreach ($possibleSlots as $slot) {
                $isReserved = false;
                if (isset($reservedSlots[$date])) {
                    foreach ($reservedSlots[$date] as $reservedSlot) {
                        if (
                            $slot['from'] == $reservedSlot['from'] &&
                            $slot['until'] == $reservedSlot['until']
                        ) {
                            $isReserved = true;
                            break;
                        }
                    }
                }

                // Mantenemos solo el filtro de reserva (ya que todos los días son futuros)
                if (!$isReserved) {
                    $availableToday[] = $slot['from'] . ' a ' . $slot['until'];
                }
            }

            if (!empty($availableToday)) {
                $availability[] = [
                    'date' => Time::parse($date)->format('d/m/Y'),
                    'available_slots' => $availableToday
                ];
            }
        }

        // 6. Devolver el resultado en formato JSON.
        try {
            return $this->response->setJSON($this->setResponse(200, false, ['availability' => $availability], 'Horarios disponibles obtenidos correctamente.'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(500, true, null, 'Error al procesar la disponibilidad: ' . $e->getMessage()));
        }
    }

    public function viewBookings()
    {
        $bookingsModel = new BookingsModel();
        $fieldsModel = new FieldsModel();
        $rateModel = new RateModel();
        $customersModel = new CustomersModel();
        $timeModel = new TimeModel();
        $usersModel = new UsersModel();
        $offerModel = new OffersModel();
        $uploadModel = new UploadModel();
        $valuesModel = new ValuesModel();

        $users = $usersModel->findAll();

        $values = $valuesModel->where('disabled', 0)->findAll();

        $firstRow = $timeModel->first();
        $isSunday = $firstRow ? $firstRow['is_sunday'] : null;

        $bookings = [];

        foreach ($bookingsModel->getBookings() as $booking) {
            $reserva = [
                'id' => $booking['id'],
                'cancha' => $fieldsModel->getField($booking['id_field'])['name'],
                'fecha' => date("d/m/Y", strtotime($booking['date'])),
                'horario' => $booking['time_from'] . ' a ' . $booking['time_until'],
                'nombre' => $booking['name'],
                'telefono' => $booking['phone'],
                'pago_total' => $booking['total_payment'] == 1 ? 'Si' : 'No',
                'total_reserva' => $booking['total'],
                'diferencia' => $booking['diference'],
                'monto_reserva' => $booking['payment'],
                'metodo_pago' => $booking['payment_method']
            ];

            array_push($bookings, $reserva);
        }

        $getTime = $timeModel->findAll();
        if ($getTime) {
            $time = $getTime[0];
        } else {
            $time = [
                'from' => 0,
                'until' => 0,
                'from_cut' => 0,
                'until_cut' => 0,
                'nocturnal_time' => 0
            ];
        }

        $openingTime = array_slice($timeModel->getOpeningTime(), 0, -7);

        $getRate = $rateModel->findAll();
        if ($getRate) {
            $rate = $getRate[0];
        } else {
            $rate = 0;
        }

        $getOfferRate = $offerModel->findAll();
        if ($getOfferRate) {
            $offerRate = $getOfferRate[0];
        } else {
            $offerRate = 0;
        }

        $fields = $fieldsModel->findAll();

        $customers = $customersModel->where('deleted', 0)->findAll();

        $logo = $uploadModel->first();

        return view('customers/booking', ['bookings' => $bookings, 'rate' => $rate, 'customers' => $customers, 'time' => $time, 'openingTime' => $openingTime, 'fields' => $fields, 'users' => $users, 'offerRate' => $offerRate, 'logo' => $logo, 'values' => $values, 'esDomingo' => $isSunday]);
    }

    public function showCustomerBooking($code)
    {
        $bookingsModel = new BookingsModel();
        $booking = $bookingsModel->where('code', $code)->first();
        if ($booking) {
            try {
                return  $this->response->setJSON($this->setResponse(null, null, $booking, 'Respuesta exitosa'));
            } catch (\Exception $e) {
                return  $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
            }
        }
    }


    public function codeGenerate()
    {
        // Definir el conjunto de caracteres permitidos
        $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        // 1. Baraja la cadena de caracteres
        $caracteres_mezclados = str_shuffle($caracteres);
        // 2. Toma una subcadena de la longitud deseada
        $codigo_alfanumerico = substr($caracteres_mezclados, 0, 10);
        // $codigo_alfanumerico podría ser algo como "aZ3qLp8oTf"
        return $codigo_alfanumerico;
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
