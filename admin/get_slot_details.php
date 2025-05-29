<?php
session_start();

if (!isset($_SESSION["user"]) || $_SESSION["usertype"] != 'a') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include("../includes/init.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$action = $_POST['action'] ?? '';
$docid = $_POST['docid'] ?? '';
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';

if (!$docid || !$date || !$time) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

try {
    switch ($action) {
        case 'get_slot_info':
            getSlotInfo($database, $docid, $date, $time);
            break;
        case 'create_schedule':
            createScheduleSlot($database, $_POST);
            break;
        case 'update_schedule':
            updateScheduleSlot($database, $_POST);
            break;
        case 'delete_schedule':
            deleteScheduleSlot($database, $_POST);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function getSlotInfo($database, $docid, $date, $time) {
    // Check if there's a schedule for this slot
    $schedule_query = "SELECT s.*, sp.sname as specialty_name 
                       FROM schedule s 
                       LEFT JOIN specialties sp ON s.specialty_id = sp.id 
                       WHERE s.docid = ? AND s.scheduledate = ? AND s.scheduletime = ? AND s.status = 1";
    
    $stmt = $database->prepare($schedule_query);
    $stmt->bind_param("sss", $docid, $date, $time);
    $stmt->execute();
    $schedule_result = $stmt->get_result();
    $schedule = $schedule_result->fetch_assoc();
    
    $response = [
        'slot_time' => $time,
        'slot_date' => $date,
        'has_schedule' => !empty($schedule),
        'schedule' => $schedule
    ];
    
    if ($schedule) {
        // Get appointments for this schedule slot
        $appointment_query = "SELECT a.*, p.pname, p.ptel, p.pemail 
                             FROM appointment a 
                             INNER JOIN patient p ON a.pid = p.pid 
                             WHERE a.scheduleid = ? AND a.status != 'canceled'
                             ORDER BY a.created_at";
        
        $stmt = $database->prepare($appointment_query);
        $stmt->bind_param("i", $schedule['scheduleid']);
        $stmt->execute();
        $appointment_result = $stmt->get_result();
        
        $appointments = [];
        while ($appointment = $appointment_result->fetch_assoc()) {
            $appointments[] = $appointment;
        }
        
        $response['appointments'] = $appointments;
        $response['appointment_count'] = count($appointments);
    }
    
    // Get doctor's specialties for selection
    $specialties_query = "SELECT s.id, s.sname 
                         FROM specialties s 
                         INNER JOIN doctor_specialty ds ON s.id = ds.specialty_id 
                         WHERE ds.docid = ?";
    
    $stmt = $database->prepare($specialties_query);
    $stmt->bind_param("i", $docid);
    $stmt->execute();
    $specialties_result = $stmt->get_result();
    
    $specialties = [];
    while ($specialty = $specialties_result->fetch_assoc()) {
        $specialties[] = $specialty;
    }
    
    $response['doctor_specialties'] = $specialties;
    
    echo json_encode($response);
}

function createScheduleSlot($database, $data) {
    $docid = $data['docid'];
    $date = $data['date'];
    $time = $data['time'];
    $specialty_id = $data['specialty_id'] ?? null;
    
    // Check if slot already exists
    $check_query = "SELECT scheduleid FROM schedule WHERE docid = ? AND scheduledate = ? AND scheduletime = ? AND status = 1";
    $stmt = $database->prepare($check_query);
    $stmt->bind_param("sss", $docid, $date, $time);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        http_response_code(400);
        echo json_encode(['error' => 'Расписание для этого времени уже существует']);
        return;
    }
    
    // Create new schedule slot
    $insert_query = "INSERT INTO schedule (docid, specialty_id, scheduledate, scheduletime, status) VALUES (?, ?, ?, ?, 1)";
    $stmt = $database->prepare($insert_query);
    $stmt->bind_param("siss", $docid, $specialty_id, $date, $time);
    
    if ($stmt->execute()) {
        $schedule_id = $database->insert_id;
        
        // Get the created schedule with specialty name
        $get_query = "SELECT s.*, sp.sname as specialty_name 
                     FROM schedule s 
                     LEFT JOIN specialties sp ON s.specialty_id = sp.id 
                     WHERE s.scheduleid = ?";
        $stmt = $database->prepare($get_query);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $created_schedule = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Расписание успешно создано',
            'schedule' => $created_schedule
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка при создании расписания']);
    }
}

function updateScheduleSlot($database, $data) {
    $schedule_id = $data['schedule_id'];
    $specialty_id = $data['specialty_id'] ?? null;
    
    $update_query = "UPDATE schedule SET specialty_id = ? WHERE scheduleid = ?";
    $stmt = $database->prepare($update_query);
    $stmt->bind_param("ii", $specialty_id, $schedule_id);
    
    if ($stmt->execute()) {
        // Get updated schedule
        $get_query = "SELECT s.*, sp.sname as specialty_name 
                     FROM schedule s 
                     LEFT JOIN specialties sp ON s.specialty_id = sp.id 
                     WHERE s.scheduleid = ?";
        $stmt = $database->prepare($get_query);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $updated_schedule = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Расписание успешно обновлено',
            'schedule' => $updated_schedule
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка при обновлении расписания']);
    }
}

function deleteScheduleSlot($database, $data) {
    $schedule_id = $data['schedule_id'];
    
    // Check if there are appointments
    $check_query = "SELECT COUNT(*) as count FROM appointment WHERE scheduleid = ? AND status != 'canceled'";
    $stmt = $database->prepare($check_query);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Нельзя удалить расписание с активными записями']);
        return;
    }
    
    // Delete schedule
    $delete_query = "UPDATE schedule SET status = 0 WHERE scheduleid = ?";
    $stmt = $database->prepare($delete_query);
    $stmt->bind_param("i", $schedule_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Расписание успешно удалено'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка при удалении расписания']);
    }
}
?>
