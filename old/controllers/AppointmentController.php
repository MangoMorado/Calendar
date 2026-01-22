<?php

require_once 'models/AppointmentModel.php';
require_once 'models/UserModel.php';

class AppointmentController
{
    private $appointmentModel;

    private $userModel;

    private $currentUser;

    public function __construct($conn, $currentUser)
    {
        $this->appointmentModel = new AppointmentModel($conn);
        $this->userModel = new UserModel($conn);
        $this->currentUser = $currentUser;
    }

    public function getAllAppointments()
    {
        return $this->appointmentModel->getAllAppointments();
    }

    public function getAppointmentById($id)
    {
        return $this->appointmentModel->getAppointmentById($id);
    }

    public function createAppointment($data)
    {
        // Validar campos requeridos
        if (empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
            return ['success' => false, 'message' => 'Faltan campos requeridos'];
        }

        // Obtener datos del formulario
        $title = $data['title'];
        $description = isset($data['description']) ? $data['description'] : '';
        $startTime = str_replace('T', ' ', $data['start_time']);
        $endTime = str_replace('T', ' ', $data['end_time']);
        $calendarType = isset($data['calendar_type']) ? $data['calendar_type'] : 'general';
        $userId = isset($data['user_id']) ? intval($data['user_id']) : null;

        // Validar el tipo de calendario
        if (! in_array($calendarType, ['estetico', 'veterinario', 'general'])) {
            $calendarType = 'general';
        }

        // Validar fechas
        if (strtotime($endTime) <= strtotime($startTime)) {
            return ['success' => false, 'message' => 'La hora de fin debe ser posterior a la hora de inicio'];
        }

        // Crear la cita
        $result = $this->appointmentModel->createAppointment($title, $description, $startTime, $endTime, $calendarType, $userId);

        if ($result) {
            // Determinar el nombre del calendario para el historial
            $calendarName = '';
            switch ($calendarType) {
                case 'estetico':
                    $calendarName = 'Estético';
                    break;
                case 'veterinario':
                    $calendarName = 'Veterinario';
                    break;
                default:
                    $calendarName = 'General';
            }

            // Registrar la acción en el historial del usuario
            $this->userModel->updateUserHistory($this->currentUser['id'], "Creó una cita: '$title' en el calendario $calendarName", [
                'id' => $result,
                'date' => $startTime,
                'calendar' => $calendarType,
                'extra' => 'Duración: '.round((strtotime($endTime) - strtotime($startTime)) / 60).' minutos',
            ]);

            return ['success' => true, 'message' => 'Cita creada con éxito', 'id' => $result];
        }

        return ['success' => false, 'message' => 'Error al crear la cita'];
    }

    public function updateAppointment($id, $data)
    {
        // Verificar ID y campos requeridos
        if (empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
            return ['success' => false, 'message' => 'Faltan campos requeridos'];
        }

        // Obtener datos del formulario
        $title = $data['title'];
        $description = isset($data['description']) ? $data['description'] : '';
        $startTime = str_replace('T', ' ', $data['start_time']);
        $endTime = str_replace('T', ' ', $data['end_time']);
        $calendarType = isset($data['calendar_type']) ? $data['calendar_type'] : null;
        $userId = isset($data['user_id']) ? intval($data['user_id']) : null;

        // Validar el tipo de calendario si está establecido
        if ($calendarType !== null && ! in_array($calendarType, ['estetico', 'veterinario', 'general'])) {
            $calendarType = 'general';
        }

        // Obtener datos de la cita original para el historial
        $originalAppointment = $this->appointmentModel->getAppointmentById($id);

        // Validar fechas
        if (strtotime($endTime) <= strtotime($startTime)) {
            return ['success' => false, 'message' => 'La hora de fin debe ser posterior a la hora de inicio'];
        }

        // Actualizar la cita
        $result = $this->appointmentModel->updateAppointment($id, $title, $description, $startTime, $endTime, $calendarType, $userId);

        if ($result) {
            // Determinar si el tipo de calendario cambió para el historial
            $calendarChanged = $calendarType !== null && isset($originalAppointment['calendar_type']) && $calendarType !== $originalAppointment['calendar_type'];
            $calendarInfo = '';

            if ($calendarChanged) {
                // Obtener el nombre de los calendarios para el historial
                $oldCalendarName = '';
                switch ($originalAppointment['calendar_type']) {
                    case 'estetico':
                        $oldCalendarName = 'Estético';
                        break;
                    case 'veterinario':
                        $oldCalendarName = 'Veterinario';
                        break;
                    default:
                        $oldCalendarName = 'General';
                }

                $newCalendarName = '';
                switch ($calendarType) {
                    case 'estetico':
                        $newCalendarName = 'Estético';
                        break;
                    case 'veterinario':
                        $newCalendarName = 'Veterinario';
                        break;
                    default:
                        $newCalendarName = 'General';
                }

                $calendarInfo = " (Cambió de calendario $oldCalendarName a $newCalendarName)";
            }

            // Registrar la acción en el historial del usuario
            $this->userModel->updateUserHistory($this->currentUser['id'], "Actualizó una cita: '$title'$calendarInfo", [
                'id' => $id,
                'date' => $startTime,
                'calendar' => $calendarType,
                'extra' => isset($originalAppointment) ? "Original: '{$originalAppointment['title']}'" : '',
            ]);

            return ['success' => true, 'message' => 'Cita actualizada con éxito'];
        }

        return ['success' => false, 'message' => 'Error al actualizar la cita'];
    }

    public function deleteAppointment($id)
    {
        // Obtener datos de la cita antes de eliminarla para el historial
        $appointmentToDelete = $this->appointmentModel->getAppointmentById($id);

        // Eliminar la cita
        $result = $this->appointmentModel->deleteAppointment($id);

        if ($result) {
            // Registrar la acción en el historial del usuario
            if ($appointmentToDelete) {
                // Determinar el nombre del calendario para el historial
                $calendarName = '';
                switch ($appointmentToDelete['calendar_type']) {
                    case 'estetico':
                        $calendarName = 'Estético';
                        break;
                    case 'veterinario':
                        $calendarName = 'Veterinario';
                        break;
                    default:
                        $calendarName = 'General';
                }

                $this->userModel->updateUserHistory($this->currentUser['id'], "Eliminó una cita: '{$appointmentToDelete['title']}' del calendario $calendarName", [
                    'id' => $id,
                    'date' => $appointmentToDelete['start_time'],
                    'calendar' => $appointmentToDelete['calendar_type'],
                ]);
            } else {
                $this->userModel->updateUserHistory($this->currentUser['id'], "Eliminó una cita (ID: $id)");
            }

            return ['success' => true, 'message' => 'Cita eliminada con éxito'];
        }

        return ['success' => false, 'message' => 'Error al eliminar la cita'];
    }

    public function getAllUsers()
    {
        return $this->userModel->getAllUsers();
    }
}
