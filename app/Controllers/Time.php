<?php

namespace App\Controllers;

use App\Models\TimeModel;

class Time extends BaseController
{

    public $schedules = [
        '07:00',
        '07:30',
        '08:00',
        '08:30',
        '09:00',
        '09:30',
        '10:00',
        '10:30',
        '11:00',
        '11:30',
        '12:00',
        '12:30',
        '13:00',
        '13:30',
        '14:00',
        '14:30',
        '15:00',
        '15:30',
        '16:00',
        '16:30',
        '17:00',
        '17:30',
        '18:00',
        '18:30',
        '19:00',
        '19:30',
        '20:00',
        '20:30',
        '21:00',
        '21:30',
        '22:00',
        '22:30',
        '23:00',
        '23:30',
        '00:00',
        '00:30',
        '01:00',
        '01:30',
        '02:00',
        '02:30',
        '03:00',
        '03:30',
        '04:00',
        '04:30',
        '05:00',
        '05:30',
        '06:00',
        '06:30'
    ];
    public function saveTime()
    {
        $timeModel = new TimeModel();

        $from = $this->request->getVar('from');
        $until = $this->request->getVar('until');
        $isMonday = $this->request->getVar('switchMonday');
        $isTuesday = $this->request->getVar('switchTuesday');
        $isWednesday = $this->request->getVar('switchWednesday');
        $isThursday = $this->request->getVar('switchThursday');
        $isFriday = $this->request->getVar('switchFriday');
        $isSaturday = $this->request->getVar('switchSaturday');
        $isSunday = $this->request->getVar('switchSunday');
        // $from_cut = $this->request->getVar('from_cut');
        // $until_cut = $this->request->getVar('until_cut');

        $nocturnalTime = $this->request->getVar('horarioNocturno');

        if ($from == '' || $until == '') {
            return redirect()->to('abmAdmin')->with('msg', ['type' => 'danger', 'body' => 'Debe completar todos los campos']);
        }

        $query = [
            'from' => $from,
            'until' => $until,
            // 'from_cut' => $from_cut,
            // 'until_cut' => $until_cut,
            'nocturnal_time' => $nocturnalTime,
            'is_monday' => $isMonday,
            'is_tuesday' => $isTuesday,
            'is_wednesday' => $isWednesday,
            'is_thursday' => $isThursday,
            'is_friday' => $isFriday,
            'is_saturday' => $isSaturday,
            'is_sunday' => $isSunday,
        ];

        $existingHours = $timeModel->findAll();

        if ($existingHours) {
            try {
                $timeModel->update($existingHours[0]['id'], $query);
            } catch (\Exception $e) {
                return "Error al insertar datos: " . $e->getMessage();
            }

            return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Horarios editados correctamente']);
        }

        try {
            $timeModel->insert($query);
        } catch (\Exception $e) {
            return "Error al insertar datos: " . $e->getMessage();
        }

        return redirect()->to('abmAdmin')->with('msg', ['type' => 'success', 'body' => 'Horarios guardados correctamente']);
    }

    public function getTime()
    {
        $timeModel = new TimeModel();
        $times = $timeModel->findAll();
        $time = [];

        if ($times) {
            $start_time = $times[0]['from'];
            $end_time = $times[0]['until'];

            $start_index = array_search($start_time, $this->schedules);
            $end_index = array_search($end_time, $this->schedules);

            if ($start_index !== false && $end_index !== false) {
                $length = ($end_index - $start_index) + 1;
                
                $time['opneningTime'] = array_slice($this->schedules, $start_index, $length);
                $time['closed']['is_monday'] = $times[0]['is_monday'] ?? 0;
                $time['closed']['is_tuesday'] = $times[0]['is_tuesday'] ?? 0;
                $time['closed']['is_wednesday'] = $times[0]['is_wednesday'] ?? 0;
                $time['closed']['is_thursday'] = $times[0]['is_thursday'] ?? 0;
                $time['closed']['is_friday'] = $times[0]['is_friday'] ?? 0;
                $time['closed']['is_saturday'] = $times[0]['is_saturday'] ?? 0;
                $time['closed']['is_sunday'] = $times[0]['is_sunday'] ?? 0;
            } else {
                log_message('warning', 'Las horas de inicio/fin (' . $start_time . ' a ' . $end_time . ') no se encontraron en el array de horarios completo.');
            }
        }

        try {
            return $this->response->setJSON($this->setResponse(null, null, $time, 'Respuesta exitosa'));
        } catch (\Exception $e) {
            return $this->response->setJSON($this->setResponse(404, true, null, $e->getMessage()));
        }
    }

    public function getNocturnalTime()
    {
        $timeModel = new TimeModel();
        $openingTime = array_values(array_filter(
            $timeModel->getOpeningTime(),
            fn ($value) => is_string($value) && preg_match('/^\d{2}:\d{2}$/', $value)
        ));
        $times = $timeModel->findAll()[0] ?? null;
        $configuredNocturnalTime = $times['nocturnal_time'] ?? null;

        if (!$configuredNocturnalTime) {
            return $this->response->setJSON($this->setResponse(null, null, [], 'Respuesta exitosa'));
        }

        $index = array_search($configuredNocturnalTime, $openingTime, true);
        $nocturnalTime = $index === false ? [] : array_slice($openingTime, (int) $index);

        try {
            return  $this->response->setJSON($this->setResponse(null, null, $nocturnalTime, 'Respuesta exitosa'));
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
