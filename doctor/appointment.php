<?php
$pageTitle = 'Менеджер записей на сегодня';
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'd') {
    header("location: ../login.php");
    exit();
}

include_once "../includes/init.php";
include_once "../includes/schedule_functions.php";
require_once "../includes/appointment_functions.php";
include "header-doctor.php";

$useremail = $_SESSION["user"];
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["docid"];
$username = $userfetch["docname"];

date_default_timezone_set('Asia/Yekaterinburg');
$today = date('Y-m-d');

// Обработка POST-запроса для обновления статуса записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appoid = intval($_POST['appoid']);
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? null;
    
    if (update_appointment_status($database, $appoid, $status, $notes)) {
        $successMessage = 'Статус записи успешно обновлен';
    } else {
        $errorMessage = 'Произошла ошибка при обновлении статуса записи';
    }
}

// Обработка отметки прихода пациента
if (isset($_GET['action']) && $_GET['action'] === 'mark_arrived' && isset($_GET['id'])) {
    $appointmentId = intval($_GET['id']);
    if (update_appointment_status($database, $appointmentId, 'confirmed')) {
        $successMessage = "Пациент отмечен как пришедший";
    } else {
        $errorMessage = "Ошибка при отметке прихода пациента";
    }
}

// Обработка отмены записи
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $appointmentId = intval($_GET['id']);
    if (update_appointment_status($database, $appointmentId, 'canceled')) {
        $successMessage = "Запись успешно отменена";
    } else {
        $errorMessage = "Ошибка при отмене записи";
    }
}

// Обработка отметки неявки
if (isset($_GET['action']) && $_GET['action'] === 'mark_no_show' && isset($_GET['id'])) {
    $appointmentId = intval($_GET['id']);
    if (update_appointment_status($database, $appointmentId, 'no_show')) {
        $successMessage = "Пациент отмечен как не явившийся";
    } else {
        $errorMessage = "Ошибка при отметке неявки";
    }
}

// Получение записей на сегодня
$appointments = get_doctor_appointments_by_date($database, $userid, $today);

// Массив статусов записей для отображения
$appointment_statuses = [
    'scheduled' => 'Запланирован',
    'confirmed' => 'Подтвержден',
    'completed' => 'Завершен',
    'canceled' => 'Отменен',
    'no_show' => 'Неявка'
];

// Цвета для статусов
$status_colors = [
    'scheduled' => '#2196F3',
    'confirmed' => '#4CAF50',
    'completed' => '#9E9E9E',
    'canceled' => '#F44336',
    'no_show' => '#FF9800'
];

// Получение расписания врача на сегодня
$day_of_week = date('N', strtotime($today));
$schedule = get_doctor_schedule_by_day($database, $userid, $day_of_week);

// Проверяем, есть ли исключения на сегодня
$exceptions = get_doctor_schedule_exceptions($database, $userid, $today, $today);
$has_exception = false;
$exception_info = '';

foreach ($exceptions as $exception) {
    if ($exception['start_time'] === null && $exception['end_time'] === null) {
        $has_exception = true;
        $exception_info = "Весь день - " . $exception['exception_type'];
        break;
    }
}

// Получение доступных слотов для добавления новых записей
$available_slots = [];
if (!$has_exception && $schedule) {
    $available_slots = get_available_appointment_slots($database, $userid, $today);
}
?>

<div class="dash-body">
    <!-- Сообщения об успехе/ошибке -->
    <?php if (isset($successMessage)): ?>
        <div class="alert-message success" style="background-color: #4CAF50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center;">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
        <div class="alert-message error" style="background-color: #f44336; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center;">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <!-- Заголовок страницы -->
    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="index.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Записи на сегодня</p>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
                <p class="heading-sub12" style="padding: 0;margin: 0;">
                    <?php echo date('d-m-Y'); ?>
                </p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
    </table>

    <div style="padding: 0 20px; margin-top: 20px;">
        <!-- Информация о расписании на сегодня -->
        <div class="day-schedule-info" style="margin-bottom: 30px; padding: 15px; background-color: #f9f9f9; border-radius: 5px;">
            <h3 style="margin-top: 0; margin-bottom: 15px;">Расписание на <?php echo date('d.m.Y', strtotime($today)); ?></h3>
            
            <?php if ($has_exception): ?>
                <div style="padding: 10px; background-color: #ffebee; border-radius: 5px; border-left: 4px solid #f44336;">
                    <p style="margin: 0; font-weight: 600;">Исключение в расписании: <?php echo $exception_info; ?></p>
                </div>
            <?php elseif ($schedule): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div>
                        <p style="margin: 5px 0;"><strong>Время работы:</strong> <?php echo substr($schedule['start_time'], 0, 5); ?> - <?php echo substr($schedule['end_time'], 0, 5); ?></p>
                        
                        <?php if ($schedule['break_start'] && $schedule['break_end']): ?>
                            <p style="margin: 5px 0;"><strong>Перерыв:</strong> <?php echo substr($schedule['break_start'], 0, 5); ?> - <?php echo substr($schedule['break_end'], 0, 5); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <p style="margin: 5px 0;"><strong>Длительность приема:</strong> <?php echo $schedule['appointment_duration']; ?> мин.</p>
                        
                        <?php if ($schedule['max_patients'] > 0): ?>
                            <p style="margin: 5px 0;"><strong>Макс. количество пациентов:</strong> <?php echo $schedule['max_patients']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: #888;">Нет расписания на сегодня</p>
            <?php endif; ?>
        </div>

        <!-- Статистика на сегодня -->
        <div class="today-stats" style="margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                <?php
                $total_count = count($appointments);
                $confirmed_count = 0;
                $completed_count = 0;
                $no_show_count = 0;
                $canceled_count = 0;
                
                foreach ($appointments as $apt) {
                    switch ($apt['status']) {
                        case 'confirmed': $confirmed_count++; break;
                        case 'completed': $completed_count++; break;
                        case 'no_show': $no_show_count++; break;
                        case 'canceled': $canceled_count++; break;
                    }
                }
                ?>
                
                <div style="background-color: #e3f2fd; padding: 15px; border-radius: 5px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #1976d2;"><?php echo $total_count; ?></div>
                    <div style="color: #666;">Всего записей</div>
                </div>
                
                <div style="background-color: #e8f5e8; padding: 15px; border-radius: 5px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #4caf50;"><?php echo $confirmed_count; ?></div>
                    <div style="color: #666;">Подтверждено</div>
                </div>
                
                <div style="background-color: #f3e5f5; padding: 15px; border-radius: 5px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #9c27b0;"><?php echo $completed_count; ?></div>
                    <div style="color: #666;">Завершено</div>
                </div>
                
                <div style="background-color: #fff3e0; padding: 15px; border-radius: 5px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #ff9800;"><?php echo $no_show_count; ?></div>
                    <div style="color: #666;">Неявки</div>
                </div>
            </div>
        </div>

        <!-- Список записей на сегодня -->
        <div class="appointments-list">
            <h3>Записи на сегодня (<?php echo count($appointments); ?>)</h3>
            
            <?php if (empty($appointments)): ?>
                <div style="text-align: center; padding: 50px; background-color: #f9f9f9; border-radius: 5px; margin-top: 20px;">
                    <img src="../img/notfound.svg" width="100" style="margin-bottom: 20px;" alt="Не найдено">
                    <p style="font-size: 18px; color: #555; margin-bottom: 10px;">На сегодня нет записей</p>
                    <p style="color: #777;">Отличный день для отдыха или работы с документами!</p>
                </div>
            <?php else: ?>
                <div class="appointments-timeline" style="margin-top: 20px;">
                    <?php 
                    // Сортируем записи по времени
                    usort($appointments, function($a, $b) {
                        return strtotime($a['appotime']) - strtotime($b['appotime']);
                    });
                      foreach ($appointments as $appointment):                        $current_time = new DateTime();
                        // Create proper DateTime object with date + time
                        $appointment_datetime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['appotime']);
                        // Only show "СЕЙЧАС" if appointment is happening right now (within 30 minutes window)
                        $time_diff = ($current_time->getTimestamp() - $appointment_datetime->getTimestamp()) / 60; // minutes
                        $appointment_status = $appointment['status'] ?? '';
                        $is_current = $time_diff >= 0 && $time_diff <= 30 && $appointment_status != 'completed';
                    ?>                          <?php 
                    // Ensure status exists and is valid, default to 'scheduled' if empty or invalid
                    $appointment_status = $appointment['status'] ?? '';
                    $safe_status = !empty($appointment_status) && isset($status_colors[$appointment_status]) 
                                  ? $appointment_status 
                                  : 'scheduled';
                    ?>
                    <div class="appointment-card" style="padding: 20px; background-color: <?php echo $is_current ? '#fffde7' : '#fff'; ?>; border-radius: 8px; border-left: 5px solid <?php echo $status_colors[$safe_status]; ?>; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <!-- Время и статус -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <div>
                                    <span style="font-size: 20px; font-weight: 600; color: #333;"><?php echo substr($appointment['appotime'], 0, 5); ?></span>
                                    <?php if ($is_current): ?>
                                        <span style="margin-left: 10px; background-color: #ff9800; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">СЕЙЧАС</span>
                                    <?php endif; ?>
                                </div>                                <div>
                                    <span style="background-color: <?php echo $status_colors[$safe_status]; ?>; color: white; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 500;">
                                        <?php echo $appointment_statuses[$safe_status]; ?>
                                    </span>
                                </div>
                            </div>
                              <!-- Информация о пациенте -->
                            <div style="margin-bottom: 15px;">
                                <h4 style="margin: 0 0 8px 0; color: #333; font-size: 18px;"><?php echo htmlspecialchars($appointment['patient_name']); ?></h4>
                                
                                <!-- Специальность приема -->
                                <?php if (!empty($appointment['specialty_name'])): ?>
                                    <div style="margin-bottom: 10px;">
                                        <span style="background-color: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                            📋 <?php echo htmlspecialchars($appointment['specialty_name']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                                    <div>
                                        <p style="margin: 2px 0; color: #666;"><strong>Телефон:</strong> <?php echo htmlspecialchars($appointment['patient_phone']); ?></p>
                                        <p style="margin: 2px 0; color: #666;"><strong>Номер записи:</strong> <?php echo $appointment['apponum']; ?></p>
                                    </div>
                                    <div>
                                        <p style="margin: 2px 0; color: #666;"><strong>Длительность:</strong> <?php echo $appointment['duration']; ?> мин.</p>
                                        <p style="margin: 2px 0; color: #666;"><strong>Дата записи:</strong> <?php echo date('d.m.Y', strtotime($appointment['appodate'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Причина визита -->
                            <?php if (!empty($appointment['reason'])): ?>
                                <div style="margin-bottom: 15px; padding: 10px; background-color: #f0f0f0; border-radius: 5px;">
                                    <p style="margin: 0; color: #666;"><strong>Причина визита:</strong> <?php echo htmlspecialchars($appointment['reason']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Заметки -->
                            <?php if (!empty($appointment['notes'])): ?>
                                <div style="margin-bottom: 15px; padding: 10px; background-color: #e3f2fd; border-radius: 5px;">
                                    <p style="margin: 0; color: #666;"><strong>Заметки:</strong> <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                                </div>
                            <?php endif; ?>
                              <!-- Кнопки действий -->
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                <!-- Отметить приход -->
                                <?php if ($safe_status == 'scheduled'): ?>
                                    <a href="?action=mark_arrived&id=<?php echo $appointment['appoid']; ?>" onclick="return confirm('Отметить пациента как пришедшего?')" class="btn-action" style="padding: 8px 15px; border-radius: 5px; text-decoration: none; background-color: #4CAF50; color: white; font-size: 12px;">
                                        Отметить приход
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Обновить статус -->
                                <button type="button" onclick="openUpdateModal(<?php echo $appointment['appoid']; ?>, '<?php echo $appointment['status']; ?>', '<?php echo addslashes($appointment['notes'] ?? ''); ?>')" class="btn-action" style="padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; background-color: #2196F3; color: white; font-size: 12px;">
                                    Обновить статус
                                </button>
                                
                                <!-- Медкарта -->
                                <a href="medical_record.php?appointment_id=<?php echo $appointment['appoid']; ?>&patient_id=<?php echo $appointment['pid']; ?>" class="btn-action" style="padding: 8px 15px; border-radius: 5px; text-decoration: none; background-color: #9C27B0; color: white; font-size: 12px;">
                                    Медкарта
                                </a>
                                
                                <!-- Отметить неявку -->
                                <?php if ($appointment['status'] != 'completed' && $appointment['status'] != 'canceled' && $appointment['status'] != 'no_show'): ?>
                                    <a href="?action=mark_no_show&id=<?php echo $appointment['appoid']; ?>" onclick="return confirm('Отметить пациента как не явившегося?')" class="btn-action" style="padding: 8px 15px; border-radius: 5px; text-decoration: none; background-color: #FF9800; color: white; font-size: 12px;">
                                        Не явился
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Отменить -->
                                <?php if ($appointment['status'] != 'completed' && $appointment['status'] != 'canceled'): ?>
                                    <a href="?action=cancel&id=<?php echo $appointment['appoid']; ?>" onclick="return confirm('Отменить запись?')" class="btn-action" style="padding: 8px 15px; border-radius: 5px; text-decoration: none; background-color: #F44336; color: white; font-size: 12px;">
                                        Отменить
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Доступные слоты для записи новых пациентов -->
        <?php if (!empty($available_slots)): ?>
            <div class="available-slots" style="margin-top: 40px; padding: 20px; background-color: #f0f8ff; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #1976d2;">Свободные слоты для записи</h3>
                <p style="color: #666; margin-bottom: 15px;">Доступное время для записи новых пациентов на сегодня:</p>
                
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($available_slots as $slot): ?>
                        <div style="padding: 8px 12px; background-color: #e3f2fd; border-radius: 5px; border: 1px solid #2196F3;">
                            <span style="color: #1976d2; font-weight: 500;"><?php echo substr($slot['time'], 0, 5); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 15px;">
                    <a href="schedule.php" style="padding: 10px 20px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">
                        Управление расписанием
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно для обновления статуса записи -->
<div id="update-status-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <span class="close" onclick="closeModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <h3 style="margin-top: 0;">Обновить статус записи</h3>
        
        <form action="" method="post" id="update-status-form">
            <input type="hidden" name="appoid" id="update-appoid">
            
            <div class="form-row" style="margin-bottom: 15px;">
                <label for="status" style="display: block; margin-bottom: 5px; font-weight: 600;">Статус записи:</label>
                <select name="status" id="update-status" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                    <?php foreach ($appointment_statuses as $value => $label): ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row" style="margin-bottom: 15px;">
                <label for="notes" style="display: block; margin-bottom: 5px; font-weight: 600;">Заметки:</label>
                <textarea name="notes" id="update-notes" rows="4" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;"></textarea>
            </div>
            
            <div class="form-actions" style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn-cancel" style="padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; background-color: #f5f5f5; color: #333; margin-right: 10px;">Отмена</button>
                <button type="submit" name="update_status" class="btn-primary" style="padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; background-color: #4CAF50; color: white;">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Автоматическое скрытие сообщений через 3 секунды
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-message');
        alerts.forEach(alert => {
            alert.style.display = 'none';
        });
    }, 3000);
    
    // Функция для открытия модального окна обновления статуса
    function openUpdateModal(appoid, status, notes) {
        document.getElementById('update-appoid').value = appoid;
        document.getElementById('update-status').value = status;
        document.getElementById('update-notes').value = notes;
        
        document.getElementById('update-status-modal').style.display = 'block';
    }
    
    // Функция для закрытия модального окна
    function closeModal() {
        document.getElementById('update-status-modal').style.display = 'none';
    }
    
    // Закрытие модального окна при клике вне его области
    window.onclick = function(event) {
        var modal = document.getElementById('update-status-modal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    
    // Автообновление страницы каждые 5 минут для актуализации данных
    setTimeout(function() {
        location.reload();
    }, 300000); // 5 минут
</script>

<?php include "footer.php"; ?>