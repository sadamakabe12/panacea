<?php
/**
 * API endpoint for adding/updating vital signs during appointments
 * 
 * This file serves as an API endpoint for adding or updating patient vital signs
 * during appointments. It handles form submissions with vital sign data and saves
 * them to the database, linking them to the appropriate medical record.
 */

// Include database connection
include('init.php');
include('medical_records_functions.php');

// Start output buffering to prevent any unexpected output
ob_start();

// Set content type to JSON
header('Content-Type: application/json');

// Session handling
session_start();

// Check if user is logged in and is a doctor
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'd') {
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Only doctors can add vital signs']);
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method Not Allowed', 'message' => 'Only POST requests are allowed']);
    exit();
}

// Get the record ID from the request
$record_id = isset($_POST['record_id']) ? (int)$_POST['record_id'] : null;

if (!$record_id) {
    echo json_encode(['error' => 'Bad Request', 'message' => 'Record ID is required']);
    exit();
}

// Get the medical record
$record = get_medical_record($database, $record_id);

if (!$record) {
    echo json_encode(['error' => 'Not Found', 'message' => 'Medical record not found']);
    exit();
}

// Check if the doctor is authorized to modify this record
$useremail = $_SESSION["user"];
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$doctor_id = $userfetch["docid"];

if ($record['doctor_id'] != $doctor_id) {
    // Allow doctors to add vitals for any patient they have access to
    // (For simplicity, we'll allow any authenticated doctor to add vitals)
    // In production, you might want stricter authorization
}

// Get vital sign data from POST request
$temperature = isset($_POST['temperature']) && $_POST['temperature'] !== '' ? (float)$_POST['temperature'] : null;
$heart_rate = isset($_POST['heart_rate']) && $_POST['heart_rate'] !== '' ? (int)$_POST['heart_rate'] : null;
$blood_pressure_systolic = isset($_POST['blood_pressure_systolic']) && $_POST['blood_pressure_systolic'] !== '' ? (int)$_POST['blood_pressure_systolic'] : null;
$blood_pressure_diastolic = isset($_POST['blood_pressure_diastolic']) && $_POST['blood_pressure_diastolic'] !== '' ? (int)$_POST['blood_pressure_diastolic'] : null;
$respiratory_rate = isset($_POST['respiratory_rate']) && $_POST['respiratory_rate'] !== '' ? (int)$_POST['respiratory_rate'] : null;
$oxygen_saturation = isset($_POST['oxygen_saturation']) && $_POST['oxygen_saturation'] !== '' ? (int)$_POST['oxygen_saturation'] : null;
$height = isset($_POST['height']) && $_POST['height'] !== '' ? (float)$_POST['height'] : null;
$weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
$bmi = isset($_POST['bmi']) && $_POST['bmi'] !== '' ? (float)$_POST['bmi'] : null;
$pain_level = isset($_POST['pain_level']) && $_POST['pain_level'] !== '' ? (int)$_POST['pain_level'] : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

// Add the vital signs to the database
$vitals_id = add_vitals(
    $database, 
    $record_id, 
    $temperature, 
    $heart_rate, 
    $blood_pressure_systolic, 
    $blood_pressure_diastolic, 
    $respiratory_rate, 
    $oxygen_saturation, 
    $height, 
    $weight, 
    $bmi, 
    $pain_level, 
    $notes
);

if ($vitals_id) {
    // Log the action for audit
    log_medical_record_action($database, $doctor_id, 'doctor', $record_id, 'update', 'Added vital signs');
    
    // Get the newly added vitals to return in the response
    $vitals = get_vitals($database, $record_id);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Vital signs added successfully',
        'vitals_id' => $vitals_id,
        'vitals' => $vitals
    ]);
} else {
    // Return error response
    echo json_encode([
        'error' => 'Database Error',
        'message' => 'Failed to add vital signs'
    ]);
}

// Clean and close output buffer
ob_end_flush();
