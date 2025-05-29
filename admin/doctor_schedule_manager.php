<?php
include_once "header.php";
include_once "../includes/init.php";
require_once "../includes/schedule_functions.php";
require_once "../includes/appointment_functions.php";
date_default_timezone_set('Asia/Yekaterinburg');

$docid = isset($_GET['docid']) ? intval($_GET['docid']) : 0;
if ($docid === 0) {
    header("Location: doctors.php");
    exit();
}

// Получаем информацию о враче
$doctor_query = $database->prepare("SELECT * FROM doctor WHERE docid = ?");
$doctor_query->bind_param("i", $docid);
$doctor_query->execute();
$doctor = $doctor_query->get_result()->fetch_assoc();

if (!$doctor) {
    header("Location: doctors.php");
    exit();
}

// Получаем специальности врача
$specialties_query = $database->prepare("
    SELECT s.id, s.sname 
    FROM specialties s 
    JOIN doctor_specialty ds ON s.id = ds.specialty_id 
    WHERE ds.docid = ?
");
$specialties_query->bind_param("i", $docid);
$specialties_query->execute();
$doctor_specialties = $specialties_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Текущая дата и неделя для отображения
$current_week = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_week)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($current_week)));

// Навигация
$prev_week = date('Y-m-d', strtotime('-1 week', strtotime($week_start)));
$next_week = date('Y-m-d', strtotime('+1 week', strtotime($week_start)));

// Получаем дни недели
$week_days = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime($week_start . " +$i days"));
    $week_days[] = [
        'date' => $date,
        'day_name' => date('l', strtotime($date)),
        'day_num' => date('j', strtotime($date)),
        'is_today' => $date === date('Y-m-d')
    ];
}

// Временные слоты (8:00 - 17:00) с интервалом 15 минут
$time_slots = [];
for ($hour = 8; $hour < 17; $hour++) {
    for ($minute = 0; $minute < 60; $minute += 15) {
        $time_slots[] = sprintf('%02d:%02d:00', $hour, $minute);
    }
}
// Добавляем последний слот 17:00
$time_slots[] = '17:00:00';

// Получаем существующие записи расписания для этой недели
$schedule_query = $database->prepare("
    SELECT s.*, a.appoid, a.status as appointment_status, p.pname, p.ptel, spec.sname as specialty_name
    FROM schedule s
    LEFT JOIN appointment a ON s.scheduleid = a.scheduleid AND a.status != 'canceled'
    LEFT JOIN patient p ON a.pid = p.pid
    LEFT JOIN specialties spec ON s.specialty_id = spec.id
    WHERE s.docid = ? AND s.scheduledate BETWEEN ? AND ?
    ORDER BY s.scheduledate, s.scheduletime
");
$schedule_query->bind_param("iss", $docid, $week_start, $week_end);
$schedule_query->execute();
$schedule_result = $schedule_query->get_result();

$schedule_data = [];
while ($row = $schedule_result->fetch_assoc()) {
    $date = $row['scheduledate'];
    $time = $row['scheduletime'];
    
    if (!isset($schedule_data[$date])) {
        $schedule_data[$date] = [];
    }
    
    if (!isset($schedule_data[$date][$time])) {
        $schedule_data[$date][$time] = [
            'scheduleid' => $row['scheduleid'],
            'specialty_id' => $row['specialty_id'],
            'specialty_name' => $row['specialty_name'],
            'appointments' => []
        ];
    }
    
    if ($row['appoid']) {
        $schedule_data[$date][$time]['appointments'][] = [
            'appoid' => $row['appoid'],
            'pname' => $row['pname'],
            'ptel' => $row['ptel'],
            'status' => $row['appointment_status']
        ];
    }
}

// Обработка создания нового слота расписания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_schedule'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $specialty_id = $_POST['specialty_id'] ?: null;
    
    // Проверяем, не существует ли уже такой слот
    $check_query = $database->prepare("SELECT scheduleid FROM schedule WHERE docid = ? AND scheduledate = ? AND scheduletime = ?");
    $check_query->bind_param("iss", $docid, $date, $time);
    $check_query->execute();
    
    if ($check_query->get_result()->num_rows === 0) {
        // Создаем новый слот
        $insert_query = $database->prepare("INSERT INTO schedule (docid, specialty_id, scheduledate, scheduletime, status) VALUES (?, ?, ?, ?, 1)");
        $insert_query->bind_param("iiss", $docid, $specialty_id, $date, $time);
        if ($insert_query->execute()) {
            $success_message = "Слот расписания создан успешно";
        } else {
            $error_message = "Ошибка при создании слота расписания";
        }
    } else {
        $error_message = "Слот расписания уже существует на это время";
    }
    
    // Перенаправляем для избежания повторной отправки формы
    header("Location: doctor_schedule_manager.php?docid=$docid&week=$current_week" . (isset($success_message) ? "&success=created" : "&error=exists"));
    exit();
}

// Обработка удаления слота расписания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    $scheduleid = intval($_POST['scheduleid']);
    
    // Проверяем, есть ли активные записи
    $appointments_check = $database->prepare("SELECT COUNT(*) as count FROM appointment WHERE scheduleid = ? AND status NOT IN ('canceled', 'completed')");
    $appointments_check->bind_param("i", $scheduleid);
    $appointments_check->execute();
    $appointments_count = $appointments_check->get_result()->fetch_assoc()['count'];
    
    if ($appointments_count > 0) {
        $error_message = "Нельзя удалить слот с активными записями";
    } else {
        // Удаляем слот
        $delete_query = $database->prepare("DELETE FROM schedule WHERE scheduleid = ?");
        $delete_query->bind_param("i", $scheduleid);
        if ($delete_query->execute()) {
            $success_message = "Слот расписания удален";
        } else {
            $error_message = "Ошибка при удалении слота";
        }
    }
    
    header("Location: doctor_schedule_manager.php?docid=$docid&week=$current_week" . (isset($success_message) ? "&success=deleted" : "&error=active_appointments"));
    exit();
}

// Получаем сообщения из URL
$success_message = '';
$error_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created': $success_message = 'Слот расписания создан успешно'; break;
        case 'deleted': $success_message = 'Слот расписания удален'; break;
        case 'updated': $success_message = 'Слот расписания обновлен'; break;
    }
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'exists': $error_message = 'Слот расписания уже существует на это время'; break;
        case 'active_appointments': $error_message = 'Нельзя удалить слот с активными записями'; break;
    }
}

// Русские названия дней недели
$day_names_ru = [
    'Monday' => 'Понедельник',
    'Tuesday' => 'Вторник', 
    'Wednesday' => 'Среда',
    'Thursday' => 'Четверг',
    'Friday' => 'Пятница',
    'Saturday' => 'Суббота',
    'Sunday' => 'Воскресенье'
];
?>

<link rel="stylesheet" href="../css/schedule-management.css">

<div class="schedule-manager">
    <!-- Сообщения -->
    <?php if ($success_message): ?>
        <div class="alert alert-success" id="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error" id="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>    <!-- Заголовок и кнопка возврата -->
    <div class="header-section">
        <div class="header-left">
            <a href="doctors.php">
                <button class="login-btn btn-primary-soft btn btn-icon-back back-button">
                    <span class="tn-in-text">Назад</span>
                </button>
            </a>
        </div>
        <div class="header-center">
            <h1 class="page-title">Управление расписанием</h1>
        </div>
        <div class="header-right">
            <p class="date-label">Сегодняшняя дата</p>
            <p class="heading-sub12 current-date">
                <?php echo date('d-m-Y'); ?>
            </p>
        </div>
        <div class="calendar-icon">
            <button class="btn-label calendar-btn">
                <img src="../img/calendar.svg" width="100%">
            </button>
        </div>
    </div>    <!-- Информация о враче -->
    <div class="doctor-info">
        <h3 class="doctor-name">
            <?php echo htmlspecialchars($doctor['docname']); ?>
        </h3>
        <div class="doctor-info-grid">
            <div class="doctor-contact">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($doctor['docemail']); ?></p>
                <p><strong>Телефон:</strong> <?php echo htmlspecialchars($doctor['doctel'] ?: 'Не указан'); ?></p>
            </div>
            <div class="doctor-specialties">
                <p><strong>Специальности:</strong></p>
                <div class="specialties-list">
                    <?php foreach ($doctor_specialties as $spec): ?>
                        <span class="specialty-badge"><?php echo htmlspecialchars($spec['sname']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Информация о рабочих часах -->
    <div class="working-hours-info">
        Рабочие часы клиники: 8:00 - 17:00 | Интервал записи: 15 минут
    </div>    <!-- Навигация по неделям -->
    <div class="week-navigation">
        <a href="?docid=<?php echo $docid; ?>&week=<?php echo $prev_week; ?>" class="week-nav-button">
            ← Предыдущая неделя
        </a>
        
        <div class="week-info">
            <h4>
                <?php echo date('d.m.Y', strtotime($week_start)); ?> - <?php echo date('d.m.Y', strtotime($week_end)); ?>
            </h4>
        </div>
        
        <a href="?docid=<?php echo $docid; ?>&week=<?php echo $next_week; ?>" class="week-nav-button">
            Следующая неделя →
        </a>
    </div>

    <!-- Сетка расписания -->
    <div class="schedule-grid">
        <!-- Заголовок с днями недели -->
        <div class="schedule-header">
            <div class="time-header">Время</div>            <?php foreach ($week_days as $day): ?>
                <div class="day-header <?php echo $day['is_today'] ? 'today' : ''; ?>">
                    <div class="day-name"><?php echo $day_names_ru[$day['day_name']]; ?></div>
                    <div class="day-number"><?php echo $day['day_num']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Строки с временными слотами -->
        <?php foreach ($time_slots as $time_slot): ?>
            <div class="schedule-row">
                <div class="time-slot">
                    <?php echo substr($time_slot, 0, 5); ?>
                </div>
                
                <?php foreach ($week_days as $day): ?>
                    <?php 
                    $has_slot = isset($schedule_data[$day['date']][$time_slot]);
                    $slot_data = $has_slot ? $schedule_data[$day['date']][$time_slot] : null;
                    $has_appointments = $has_slot && !empty($slot_data['appointments']);
                    ?>
                      <div class="schedule-cell <?php echo $has_slot ? 'has-slot' : ''; ?> <?php echo $has_appointments ? 'has-appointments' : ''; ?>"
                         onclick="<?php echo $has_slot ? 'openSlotDetails(\'' . $day['date'] . '\', \'' . $time_slot . '\')' : 'openCreateSlot(\'' . $day['date'] . '\', \'' . $time_slot . '\')'; ?>">
                        
                        <?php if ($has_slot): ?>
                            <div class="slot-info">
                                <?php if ($slot_data['specialty_name']): ?>
                                    <div class="specialty-tag"><?php echo htmlspecialchars($slot_data['specialty_name']); ?></div>
                                <?php endif; ?>
                                  <?php if ($has_appointments): ?>
                                    <div class="appointment-count">
                                        Записей: <?php echo count($slot_data['appointments']); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="slot-status-free">СВОБОДНО</div>
                                <?php endif; ?>
                            </div>                        <?php else: ?>
                            <div class="slot-empty">
                                Нажмите для создания
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Модальное окно для создания нового слота -->
<div id="create-slot-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('create-slot-modal')">&times;</span>
        <h3>Создать новый слот расписания</h3>
        
        <form method="post">
            <input type="hidden" name="create_schedule" value="1">
            <input type="hidden" name="date" id="create-date">
            <input type="hidden" name="time" id="create-time">
              <div class="form-group">
                <label class="form-label">Дата и время:</label>
                <div id="create-datetime-display" class="datetime-display"></div>
            </div>
              <div class="form-group">
                <label for="create-specialty" class="form-label">Специальность (необязательно):</label>
                <select name="specialty_id" id="create-specialty" class="form-control">
                    <option value="">Без специальности</option>
                    <?php foreach ($doctor_specialties as $spec): ?>
                        <option value="<?php echo $spec['id']; ?>"><?php echo htmlspecialchars($spec['sname']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('create-slot-modal')" class="btn btn-secondary">Отмена</button>
                <button type="submit" class="btn btn-success">Создать слот</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для просмотра слота -->
<div id="slot-details-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('slot-details-modal')">&times;</span>
        <h3>Детали слота расписания</h3>
        
        <div id="slot-details-content">
            <!-- Контент будет загружен через JavaScript -->
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования слота -->
<div id="edit-slot-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('edit-slot-modal')">&times;</span>
        <h3>Редактировать слот расписания</h3>
        
        <div>
            <input type="hidden" id="edit-schedule-id">
            <input type="hidden" id="edit-date">
            <input type="hidden" id="edit-time">
              <div class="form-group">
                <label class="form-label">Дата и время:</label>
                <div id="edit-datetime-display" class="datetime-display"></div>
            </div>
            
            <div class="form-group">
                <label for="edit-specialty" class="form-label">Специальность:</label>
                <select id="edit-specialty" class="form-control">
                    <option value="">Без специальности</option>
                    <?php foreach ($doctor_specialties as $spec): ?>
                        <option value="<?php echo $spec['id']; ?>"><?php echo htmlspecialchars($spec['sname']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('edit-slot-modal')" class="btn btn-secondary">Отмена</button>
                <button type="button" onclick="saveSlotEdit()" class="btn btn-primary">Сохранить изменения</button>
            </div>
        </div>
    </div>
</div>

<script>
// Автоскрытие уведомлений
setTimeout(function() {
    const alert = document.getElementById('alert');
    if (alert) {
        alert.style.display = 'none';
    }
}, 3000);

// Русские названия месяцев
const monthNames = {
    '01': 'января', '02': 'февраля', '03': 'марта', '04': 'апреля',
    '05': 'мая', '06': 'июня', '07': 'июля', '08': 'августа',
    '09': 'сентября', '10': 'октября', '11': 'ноября', '12': 'декабря'
};

const dayNames = {
    'Monday': 'понедельник', 'Tuesday': 'вторник', 'Wednesday': 'среду',
    'Thursday': 'четверг', 'Friday': 'пятницу', 'Saturday': 'субботу', 'Sunday': 'воскресенье'
};

function openCreateSlot(date, time) {
    document.getElementById('create-date').value = date;
    document.getElementById('create-time').value = time;
    
    // Правильно парсим дату
    const dateParts = date.split('-');
    const year = parseInt(dateParts[0]);
    const month = parseInt(dateParts[1]) - 1; // JavaScript months are 0-based
    const day = parseInt(dateParts[2]);
    
    const dateObj = new Date(year, month, day);
    const dayName = dayNames[dateObj.toLocaleDateString('en-US', { weekday: 'long' })];
    const dayNum = dateObj.getDate();
    const monthName = monthNames[String(dateObj.getMonth() + 1).padStart(2, '0')];
    const timeStr = time.substring(0, 5);
    
    document.getElementById('create-datetime-display').textContent = 
        `${dayName}, ${dayNum} ${monthName} в ${timeStr}`;
    
    document.getElementById('create-slot-modal').style.display = 'block';
}

function openSlotDetails(date, time) {
    // Загружаем информацию о слоте через AJAX
    const formData = new FormData();
    formData.append('action', 'get_slot_info');
    formData.append('docid', '<?php echo $docid; ?>');
    formData.append('date', date);
    formData.append('time', time);
    
    fetch('get_slot_details.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Ошибка: ' + data.error);
            return;
        }
        
        displaySlotDetails(data);
        document.getElementById('slot-details-modal').style.display = 'block';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при загрузке данных слота');
    });
}

function displaySlotDetails(data) {
    const timeStr = data.slot_time.substring(0, 5);
    
    // Правильно парсим дату
    const dateParts = data.slot_date.split('-');
    const year = parseInt(dateParts[0]);
    const month = parseInt(dateParts[1]) - 1; // JavaScript months are 0-based
    const day = parseInt(dateParts[2]);
    
    const dateObj = new Date(year, month, day);
    const dayName = dayNames[dateObj.toLocaleDateString('en-US', { weekday: 'long' })];
    const dayNum = dateObj.getDate();
    const monthName = monthNames[String(dateObj.getMonth() + 1).padStart(2, '0')];
    
    let content = `
        <h3>Информация о слоте: ${dayName}, ${dayNum} ${monthName} в ${timeStr}</h3>
        <hr>
    `;
    
    if (data.has_schedule) {
        const schedule = data.schedule;
        content += `
            <div class="schedule-info">
                <p><strong>Специальность:</strong> ${schedule.specialty_name || 'Не указана'}</p>
                <p><strong>Статус:</strong> Активный</p>
            </div>
            
            <div class="appointments-section">
                <h4>Записи на прием (${data.appointment_count || 0})</h4>
        `;
        
        if (data.appointments && data.appointments.length > 0) {
            content += '<div class="appointments-list">';
            data.appointments.forEach(app => {
                const statusClass = app.status === 'confirmed' ? 'confirmed' : 
                                   app.status === 'completed' ? 'completed' : 'scheduled';
                content += `
                    <div class="appointment-item ${statusClass}">
                        <div class="patient-info">
                            <strong>${app.pname}</strong>
                            <span class="phone">${app.ptel}</span>
                        </div>
                        <div class="appointment-status">
                            <span class="status-badge status-${app.status}">${getStatusText(app.status)}</span>
                        </div>
                    </div>
                `;
            });
            content += '</div>';
        } else {
            content += '<p class="no-appointments">Записей на этот слот нет</p>';
        }
        
        content += `
            </div>
            
            <div class="slot-actions">
                <button class="btn btn-primary" onclick="editScheduleSlot(${schedule.scheduleid}, '${data.slot_date}', '${data.slot_time}')">
                    Редактировать
                </button>
                <button class="btn btn-danger" onclick="deleteScheduleSlot(${schedule.scheduleid})">
                    Удалить слот
                </button>
            </div>
        `;
    } else {
        content += `
            <p>Слот расписания не создан</p>
            <button class="btn btn-success" onclick="closeModal('slot-details-modal'); openCreateSlot('${data.slot_date}', '${data.slot_time}')">
                Создать слот
            </button>
        `;
    }
    
    document.getElementById('slot-details-content').innerHTML = content;
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Закрытие модальных окон при клике вне их области
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}

// Функция для получения текста статуса на русском
function getStatusText(status) {
    const statusTexts = {
        'scheduled': 'Запланирован',
        'confirmed': 'Подтвержден',
        'completed': 'Завершен',
        'canceled': 'Отменен',
        'no_show': 'Не явился'
    };
    return statusTexts[status] || status;
}

// Функция для редактирования слота расписания
function editScheduleSlot(scheduleId, date, time) {
    // Закрываем модальное окно деталей
    closeModal('slot-details-modal');
    
    // Заполняем форму редактирования
    document.getElementById('edit-schedule-id').value = scheduleId;
    document.getElementById('edit-date').value = date;
    document.getElementById('edit-time').value = time;
    
    // Правильно парсим дату для отображения
    const dateParts = date.split('-');
    const year = parseInt(dateParts[0]);
    const month = parseInt(dateParts[1]) - 1;
    const day = parseInt(dateParts[2]);
    
    const dateObj = new Date(year, month, day);
    const dayName = dayNames[dateObj.toLocaleDateString('en-US', { weekday: 'long' })];
    const dayNum = dateObj.getDate();
    const monthName = monthNames[String(dateObj.getMonth() + 1).padStart(2, '0')];
    const timeStr = time.substring(0, 5);
    
    document.getElementById('edit-datetime-display').textContent = 
        `${dayName}, ${dayNum} ${monthName} в ${timeStr}`;
    
    // Загружаем текущие данные слота для заполнения формы
    const formData = new FormData();
    formData.append('action', 'get_slot_info');
    formData.append('docid', '<?php echo $docid; ?>');
    formData.append('date', date);
    formData.append('time', time);
    
    fetch('get_slot_details.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.schedule && data.schedule.specialty_id) {
            document.getElementById('edit-specialty').value = data.schedule.specialty_id;
        }
    })
    .catch(error => {
        console.error('Error loading slot data:', error);
    });
    
    // Показываем модальное окно редактирования
    document.getElementById('edit-slot-modal').style.display = 'block';
}

// Функция для удаления слота
function deleteScheduleSlot(scheduleid) {
    if (confirm('Вы уверены, что хотите удалить этот слот расписания?')) {
        const formData = new FormData();
        formData.append('action', 'delete_schedule');
        formData.append('schedule_id', scheduleid);
        
        fetch('get_slot_details.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Перезагружаем страницу для обновления расписания
            } else {
                alert('Ошибка: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при удалении слота');
        });
    }
}

// Функция для сохранения изменений слота
function saveSlotEdit() {
    const scheduleId = document.getElementById('edit-schedule-id').value;
    const specialtyId = document.getElementById('edit-specialty').value;
    
    const formData = new FormData();
    formData.append('action', 'update_schedule');
    formData.append('schedule_id', scheduleId);
    formData.append('specialty_id', specialtyId);
    
    fetch('get_slot_details.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('edit-slot-modal');
            location.reload(); // Перезагружаем страницу для обновления расписания
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при сохранении изменений');
    });
}
</script>

<?php include "footer.php"; ?>
