<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'd') {
    echo '<p style="color: red;">Доступ запрещен</p>';
    exit();
}

// Настройка временной зоны
date_default_timezone_set('Asia/Yekaterinburg');

include_once "../includes/init.php";
include_once "../includes/schedule_functions.php";
include_once "../includes/doctor_advanced_functions.php";

// Русские названия месяцев
$monthNamesRu = [
    'January' => 'января',
    'February' => 'февраля',
    'March' => 'марта',
    'April' => 'апреля',
    'May' => 'мая',
    'June' => 'июня',
    'July' => 'июля',
    'August' => 'августа',
    'September' => 'сентября',
    'October' => 'октября',
    'November' => 'ноября',
    'December' => 'декабря'
];

// Русские названия дней недели
$dayNamesRu = [
    'Monday' => 'понедельник',
    'Tuesday' => 'вторник', 
    'Wednesday' => 'среду',
    'Thursday' => 'четверг',
    'Friday' => 'пятницу',
    'Saturday' => 'субботу',
    'Sunday' => 'воскресенье'
];

// Функция для форматирования даты на русском языке
function formatDateRu($date, $monthNamesRu, $dayNamesRu) {
    $day = date('j', strtotime($date));
    $monthEn = date('F', strtotime($date));
    $dayEn = date('l', strtotime($date));
    $year = date('Y', strtotime($date));
    $month = $monthNamesRu[$monthEn];
    $dayName = $dayNamesRu[$dayEn];
    return "$day $month $year ($dayName)";
}

$useremail = $_SESSION["user"];
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["docid"];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<p style="color: red;">Неверный ID расписания</p>';
    exit();
}

$schedule_id = intval($_GET['id']);

// Получаем детали расписания
$sql = "SELECT s.*, d.docname, 
               COUNT(a.appoid) as total_appointments,
               COUNT(CASE WHEN a.status != 'canceled' THEN a.appoid END) as active_appointments
        FROM schedule s
        INNER JOIN doctor d ON s.docid = d.docid
        LEFT JOIN appointment a ON s.scheduleid = a.scheduleid
        WHERE s.scheduleid = ? AND s.docid = ?
        GROUP BY s.scheduleid";

$stmt = $database->prepare($sql);
$stmt->bind_param("ii", $schedule_id, $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<p style="color: red;">Расписание не найдено</p>';
    exit();
}

$schedule = $result->fetch_assoc();

// Получаем список записей на это время
$appointments_sql = "SELECT a.*, p.pname, p.ptel, p.pemail, s.scheduletime
                     FROM appointment a
                     INNER JOIN patient p ON a.pid = p.pid
                     INNER JOIN schedule s ON a.scheduleid = s.scheduleid
                     WHERE a.scheduleid = ?
                     ORDER BY a.appodate DESC, s.scheduletime ASC";

$appointments_stmt = $database->prepare($appointments_sql);
$appointments_stmt->bind_param("i", $schedule_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();

$appointment_statuses = [
    'scheduled' => 'Запланировано',
    'confirmed' => 'Подтверждено',
    'in_progress' => 'В процессе',
    'completed' => 'Завершено',
    'canceled' => 'Отменено',
    'no_show' => 'Не явился'
];

$status_colors = [
    'scheduled' => '#2196F3',
    'confirmed' => '#4CAF50',
    'in_progress' => '#FF9800',
    'completed' => '#9C27B0',
    'canceled' => '#F44336',
    'no_show' => '#795548'
];
?>

<div class="schedule-details">
    <!-- Информация о расписании -->
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <h4 style="margin: 0 0 10px 0; color: #333;">Информация о слоте расписания</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
            <div>
                <strong>Дата:</strong> <?php echo date('d.m.Y', strtotime($schedule['scheduledate'])); ?>
            </div>
            <div>
                <strong>Время:</strong> <?php echo substr($schedule['scheduletime'], 0, 5); ?>
            </div>
            <div>
                <strong>Врач:</strong> <?php echo htmlspecialchars($schedule['docname']); ?>
            </div>
            <div>
                <strong>Всего записей:</strong> <?php echo $schedule['total_appointments']; ?>
            </div>
            <div>
                <strong>Активных записей:</strong> <?php echo $schedule['active_appointments']; ?>
            </div>
        </div>
    </div>

    <!-- Список записей -->
    <?php if ($appointments_result->num_rows > 0): ?>
        <div style="margin-bottom: 15px;">
            <h4 style="margin: 0 0 15px 0; color: #333;">Записи пациентов</h4>
            
            <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 10px; border-left: 4px solid <?php echo $status_colors[$appointment['status']]; ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <div>
                            <h5 style="margin: 0 0 5px 0; color: #333;">
                                <?php echo htmlspecialchars($appointment['pname']); ?>
                            </h5>
                            <p style="margin: 0; color: #666; font-size: 14px;">
                                <strong>Номер записи:</strong> <?php echo $appointment['apponum']; ?>
                            </p>
                        </div>
                        <span style="background-color: <?php echo $status_colors[$appointment['status']]; ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">
                            <?php echo $appointment_statuses[$appointment['status']]; ?>
                        </span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 10px;">
                        <div>
                            <strong>Телефон:</strong> <?php echo htmlspecialchars($appointment['ptel']); ?>
                        </div>
                        <div>
                            <strong>Email:</strong> <?php echo htmlspecialchars($appointment['pemail']); ?>
                        </div>
                        <div>
                            <strong>Дата записи:</strong> <?php echo date('d.m.Y', strtotime($appointment['appodate'])); ?>
                        </div>
                        <div>
                            <strong>Время приема:</strong> <?php echo substr($appointment['scheduletime'], 0, 5); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($appointment['reason'])): ?>
                        <div style="margin-top: 10px; padding: 8px; background-color: #f0f0f0; border-radius: 3px;">
                            <strong>Причина визита:</strong> <?php echo nl2br(htmlspecialchars($appointment['reason'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($appointment['notes'])): ?>
                        <div style="margin-top: 10px; padding: 8px; background-color: #e3f2fd; border-radius: 3px;">
                            <strong>Заметки врача:</strong> <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 10px; text-align: right;">
                        <a href="appointments.php?date=<?php echo $schedule['scheduledate']; ?>" 
                           style="padding: 6px 12px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 3px; font-size: 12px;">
                            Подробнее
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 20px; color: #666;">
            <p>На это время нет записей пациентов</p>
            <p style="font-size: 14px; margin: 0;">Это свободное время в расписании</p>
        </div>
    <?php endif; ?>
    
    <!-- Быстрые действия -->
    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; text-align: center;">
        <a href="appointments.php?date=<?php echo $schedule['scheduledate']; ?>" 
           style="display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;">
            Управление записями
        </a>
        <a href="schedule.php" 
           style="display: inline-block; padding: 10px 20px; background-color: #607D8B; color: white; text-decoration: none; border-radius: 5px;">
            Вернуться к расписанию
        </a>
    </div>
</div>
