<?php
include_once "header.php";
include_once "../includes/init.php";
require_once "../includes/schedule_functions.php";
date_default_timezone_set('Asia/Yekaterinburg');
$today = date('Y-m-d');

// Обработка POST-запроса для добавления/обновления расписания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_schedule'])) {
    $docid = intval($_POST['docid']);
    $day_of_week = intval($_POST['day_of_week']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $break_start = !empty($_POST['break_start']) ? $_POST['break_start'] : null;
    $break_end = !empty($_POST['break_end']) ? $_POST['break_end'] : null;
    $appointment_duration = intval($_POST['appointment_duration']);
    $max_patients = intval($_POST['max_patients']);
    
    // Сохраняем расписание
    if (set_doctor_schedule($database, $docid, $day_of_week, $start_time, $end_time, $break_start, $break_end, $appointment_duration, $max_patients)) {
        header("Location: doctor_schedule.php?docid=$docid&action=saved");
        exit();
    } else {
        header("Location: doctor_schedule.php?docid=$docid&action=error");
        exit();
    }
}

// Обработка POST-запроса для удаления расписания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    $docid = intval($_POST['docid']);
    $day_of_week = intval($_POST['day_of_week']);
    
    // Удаляем расписание
    if (delete_doctor_schedule($database, $docid, $day_of_week)) {
        header("Location: doctor_schedule.php?docid=$docid&action=deleted");
        exit();
    } else {
        header("Location: doctor_schedule.php?docid=$docid&action=error");
        exit();
    }
}

// Обработка POST-запроса для добавления исключения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exception'])) {
    $docid = intval($_POST['docid']);
    $exception_date = $_POST['exception_date'];
    $exception_type = $_POST['exception_type'];
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $description = $_POST['description'] ?? null;
    
    // Проверка, если выбран весь день, то start_time и end_time должны быть null
    if (isset($_POST['all_day']) && $_POST['all_day'] == '1') {
        $start_time = null;
        $end_time = null;
    }
    
    // Добавляем исключение
    if (add_schedule_exception($database, $docid, $exception_date, $exception_type, $start_time, $end_time, $description)) {
        header("Location: doctor_schedule.php?docid=$docid&action=exception_added");
        exit();
    } else {
        header("Location: doctor_schedule.php?docid=$docid&action=error");
        exit();
    }
}

// Обработка POST-запроса для удаления исключения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_exception'])) {
    $docid = intval($_POST['docid']);
    $exception_id = intval($_POST['exception_id']);
    
    // Удаляем исключение
    if (delete_schedule_exception($database, $exception_id)) {
        header("Location: doctor_schedule.php?docid=$docid&action=exception_deleted");
        exit();
    } else {
        header("Location: doctor_schedule.php?docid=$docid&action=error");
        exit();
    }
}

// Получение списка врачей
$doctors_query = $database->query("SELECT docid, docname FROM doctor ORDER BY docname");

// Получение выбранного врача
$selected_docid = isset($_GET['docid']) ? intval($_GET['docid']) : 0;
$selected_doctor = null;

if ($selected_docid > 0) {
    $doctor_query = $database->prepare("SELECT * FROM doctor WHERE docid = ?");
    $doctor_query->bind_param("i", $selected_docid);
    $doctor_query->execute();
    $selected_doctor = $doctor_query->get_result()->fetch_assoc();
}

// Получение расписания врача
$schedule = [];
$exceptions = [];

if ($selected_docid > 0) {
    $schedule = get_doctor_full_schedule($database, $selected_docid);
    
    // Получаем исключения на ближайшие 3 месяца
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+3 months'));
    $exceptions = get_doctor_schedule_exceptions($database, $selected_docid, $start_date, $end_date);
}

// Массив дней недели для отображения
$days_of_week = [
    1 => 'Понедельник',
    2 => 'Вторник',
    3 => 'Среда',
    4 => 'Четверг',
    5 => 'Пятница',
    6 => 'Суббота',
    7 => 'Воскресенье'
];

// Массив типов исключений
$exception_types = [
    'vacation' => 'Отпуск',
    'sick_leave' => 'Больничный',
    'personal' => 'Личное',
    'custom' => 'Другое'
];

// Проверяем, есть ли сообщение для отображения
$action_message = '';
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'saved':
            $action_message = 'Расписание сохранено';
            break;
        case 'deleted':
            $action_message = 'Расписание удалено';
            break;
        case 'exception_added':
            $action_message = 'Исключение добавлено';
            break;
        case 'exception_deleted':
            $action_message = 'Исключение удалено';
            break;
        case 'error':
            $action_message = 'Произошла ошибка';
            break;
    }
}
?>

<div class="dash-body">
    <?php if (!empty($action_message)): ?>
    <div class="alert-message" id="alert-message" style="background-color: #4CAF50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center;">
        <?php echo $action_message; ?>
    </div>
    <script>
        setTimeout(function() {
            document.getElementById('alert-message').style.display = 'none';
        }, 3000);
    </script>
    <?php endif; ?>

    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="doctors.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Управление расписанием врачей</p>
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
        <!-- Выбор врача -->
        <div class="doctor-selection" style="margin-bottom: 30px;">
            <form action="" method="get">
                <label for="docid" style="font-weight: 600; margin-right: 10px;">Выбрать врача:</label>
                <select name="docid" id="docid" style="padding: 8px; width: 300px; border-radius: 5px; border: 1px solid #ddd;" onchange="this.form.submit()">
                    <option value="0">Выберите врача</option>
                    <?php while ($doctor = $doctors_query->fetch_assoc()): ?>
                        <option value="<?php echo $doctor['docid']; ?>" <?php echo ($selected_docid == $doctor['docid']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($doctor['docname']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <?php if ($selected_doctor): ?>
            <div class="schedule-tabs" style="margin-bottom: 20px;">
                <ul style="list-style: none; display: flex; padding: 0; margin: 0; border-bottom: 1px solid #ddd;">
                    <li style="margin-right: 10px;">
                        <a href="#weekly-schedule" class="tab-link active" onclick="showTab('weekly-schedule', this)" style="padding: 10px 15px; display: inline-block; text-decoration: none; color: #333; font-weight: 600; border-bottom: 2px solid #2196F3;">Еженедельное расписание</a>
                    </li>
                    <li>
                        <a href="#exceptions" class="tab-link" onclick="showTab('exceptions', this)" style="padding: 10px 15px; display: inline-block; text-decoration: none; color: #333;">Исключения (отпуск, больничный)</a>
                    </li>
                </ul>
            </div>

            <!-- Вкладка с еженедельным расписанием -->
            <div id="weekly-schedule" class="tab-content" style="display: block;">
                <h3>Еженедельное расписание для <?php echo htmlspecialchars($selected_doctor['docname']); ?></h3>
                
                <div class="schedule-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($days_of_week as $day_num => $day_name): ?>
                        <?php
                        // Поиск расписания для этого дня
                        $day_schedule = null;
                        foreach ($schedule as $sch) {
                            if ($sch['day_of_week'] == $day_num) {
                                $day_schedule = $sch;
                                break;
                            }
                        }
                        ?>
                        <div class="day-schedule" style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; background-color: <?php echo $day_schedule ? '#f9f9f9' : '#fff'; ?>;">
                            <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;"><?php echo $day_name; ?></h4>
                            
                            <?php if ($day_schedule): ?>
                                <div class="schedule-details" style="margin-bottom: 15px;">
                                    <p style="margin: 5px 0;"><strong>Время работы:</strong> <?php echo substr($day_schedule['start_time'], 0, 5); ?> - <?php echo substr($day_schedule['end_time'], 0, 5); ?></p>
                                    
                                    <?php if ($day_schedule['break_start'] && $day_schedule['break_end']): ?>
                                        <p style="margin: 5px 0;"><strong>Перерыв:</strong> <?php echo substr($day_schedule['break_start'], 0, 5); ?> - <?php echo substr($day_schedule['break_end'], 0, 5); ?></p>
                                    <?php else: ?>
                                        <p style="margin: 5px 0;"><strong>Перерыв:</strong> Нет</p>
                                    <?php endif; ?>
                                    
                                    <p style="margin: 5px 0;"><strong>Длительность приема:</strong> <?php echo $day_schedule['appointment_duration']; ?> мин.</p>
                                    
                                    <?php if ($day_schedule['max_patients'] > 0): ?>
                                        <p style="margin: 5px 0;"><strong>Макс. пациентов:</strong> <?php echo $day_schedule['max_patients']; ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="schedule-actions" style="display: flex; justify-content: space-between; margin-top: 10px;">
                                    <button type="button" onclick="editSchedule(<?php echo $day_num; ?>, '<?php echo $day_schedule['start_time']; ?>', '<?php echo $day_schedule['end_time']; ?>', '<?php echo $day_schedule['break_start']; ?>', '<?php echo $day_schedule['break_end']; ?>', <?php echo $day_schedule['appointment_duration']; ?>, <?php echo $day_schedule['max_patients']; ?>)" class="btn-primary-soft" style="padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; background-color: #2196F3; color: white;">Изменить</button>
                                    
                                    <form action="" method="post" onsubmit="return confirm('Вы уверены, что хотите удалить расписание на <?php echo $day_name; ?>?');">
                                        <input type="hidden" name="docid" value="<?php echo $selected_docid; ?>">
                                        <input type="hidden" name="day_of_week" value="<?php echo $day_num; ?>">
                                        <button type="submit" name="delete_schedule" class="btn-delete" style="padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; background-color: #f44336; color: white;">Удалить</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <p style="color: #888; margin: 15px 0;">Расписание не задано</p>
                                <button type="button" onclick="addSchedule(<?php echo $day_num; ?>)" class="btn-primary-soft" style="padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; background-color: #2196F3; color: white;">Добавить расписание</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Вкладка с исключениями -->
            <div id="exceptions" class="tab-content" style="display: none;">
                <h3>Исключения в расписании для <?php echo htmlspecialchars($selected_doctor['docname']); ?></h3>
                
                <div class="add-exception" style="margin-bottom: 30px; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">
                    <h4 style="margin-top: 0;">Добавить исключение</h4>
                    <form action="" method="post">
                        <input type="hidden" name="docid" value="<?php echo $selected_docid; ?>">
                        
                        <div class="form-row" style="display: flex; flex-wrap: wrap; margin-bottom: 15px;">
                            <div style="flex: 1; min-width: 200px; margin-right: 15px;">
                                <label for="exception_date" style="display: block; margin-bottom: 5px; font-weight: 600;">Дата</label>
                                <input type="date" name="exception_date" id="exception_date" min="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                            </div>
                            
                            <div style="flex: 1; min-width: 200px; margin-right: 15px;">
                                <label for="exception_type" style="display: block; margin-bottom: 5px; font-weight: 600;">Тип исключения</label>
                                <select name="exception_type" id="exception_type" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                                    <?php foreach ($exception_types as $value => $label): ?>
                                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div style="flex: 1; min-width: 200px;">
                                <label for="all_day" style="display: block; margin-bottom: 5px; font-weight: 600;">На весь день?</label>
                                <div style="margin-top: 8px;">
                                    <input type="checkbox" name="all_day" id="all_day" value="1" onchange="toggleTimeInputs(this.checked)">
                                    <label for="all_day">Да, на весь день</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row time-inputs" style="display: flex; flex-wrap: wrap; margin-bottom: 15px;">
                            <div style="flex: 1; min-width: 200px; margin-right: 15px;">
                                <label for="start_time" style="display: block; margin-bottom: 5px; font-weight: 600;">Время начала</label>
                                <input type="time" name="start_time" id="start_time" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                            </div>
                            
                            <div style="flex: 1; min-width: 200px;">
                                <label for="end_time" style="display: block; margin-bottom: 5px; font-weight: 600;">Время окончания</label>
                                <input type="time" name="end_time" id="end_time" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                            </div>
                        </div>
                        
                        <div class="form-row" style="margin-bottom: 15px;">
                            <label for="description" style="display: block; margin-bottom: 5px; font-weight: 600;">Описание (необязательно)</label>
                            <textarea name="description" id="description" rows="3" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;"></textarea>
                        </div>
                        
                        <button type="submit" name="add_exception" class="btn-primary" style="padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; background-color: #4CAF50; color: white;">Добавить исключение</button>
                    </form>
                </div>
                
                <div class="exceptions-list">
                    <h4>Текущие исключения</h4>
                    
                    <?php if (empty($exceptions)): ?>
                        <p style="color: #888;">Нет исключений в расписании</p>
                    <?php else: ?>
                        <table class="sub-table" border="0" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd; background-color: #f2f2f2;">Дата</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd; background-color: #f2f2f2;">Тип</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd; background-color: #f2f2f2;">Время</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd; background-color: #f2f2f2;">Описание</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd; background-color: #f2f2f2;">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exceptions as $exception): ?>
                                    <tr>
                                        <td style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;"><?php echo date('d.m.Y', strtotime($exception['exception_date'])); ?></td>
                                        <td style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;"><?php echo $exception_types[$exception['exception_type']]; ?></td>
                                        <td style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">
                                            <?php if ($exception['start_time'] && $exception['end_time']): ?>
                                                <?php echo substr($exception['start_time'], 0, 5); ?> - <?php echo substr($exception['end_time'], 0, 5); ?>
                                            <?php else: ?>
                                                Весь день
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($exception['description'] ?? ''); ?></td>
                                        <td style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">
                                            <form action="" method="post" onsubmit="return confirm('Вы уверены, что хотите удалить это исключение?');" style="display: inline;">
                                                <input type="hidden" name="docid" value="<?php echo $selected_docid; ?>">
                                                <input type="hidden" name="exception_id" value="<?php echo $exception['exception_id']; ?>">
                                                <button type="submit" name="delete_exception" class="btn-delete" style="padding: 5px 10px; border-radius: 3px; border: none; cursor: pointer; background-color: #f44336; color: white;">Удалить</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="no-doctor-selected" style="text-align: center; padding: 50px; background-color: #f9f9f9; border-radius: 5px;">
                <p style="font-size: 18px; color: #555;">Пожалуйста, выберите врача для управления его расписанием</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно для добавления/изменения расписания -->
<div id="schedule-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <span class="close" onclick="closeModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <h3 id="modal-title" style="margin-top: 0;">Добавить расписание</h3>
        
        <form action="" method="post" id="schedule-form">
            <input type="hidden" name="docid" value="<?php echo $selected_docid; ?>">
            <input type="hidden" name="day_of_week" id="day_of_week">
            
            <div class="form-row" style="display: flex; margin-bottom: 15px;">
                <div style="flex: 1; margin-right: 15px;">
                    <label for="start_time_modal" style="display: block; margin-bottom: 5px; font-weight: 600;">Время начала работы</label>
                    <input type="time" name="start_time" id="start_time_modal" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                </div>
                
                <div style="flex: 1;">
                    <label for="end_time_modal" style="display: block; margin-bottom: 5px; font-weight: 600;">Время окончания работы</label>
                    <input type="time" name="end_time" id="end_time_modal" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                </div>
            </div>
            
            <div class="form-row" style="display: flex; margin-bottom: 15px;">
                <div style="flex: 1; margin-right: 15px;">
                    <label for="break_start_modal" style="display: block; margin-bottom: 5px; font-weight: 600;">Время начала перерыва</label>
                    <input type="time" name="break_start" id="break_start_modal" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                </div>
                
                <div style="flex: 1;">
                    <label for="break_end_modal" style="display: block; margin-bottom: 5px; font-weight: 600;">Время окончания перерыва</label>
                    <input type="time" name="break_end" id="break_end_modal" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                </div>
            </div>
            
            <div class="form-row" style="display: flex; margin-bottom: 15px;">
                <div style="flex: 1; margin-right: 15px;">
                    <label for="appointment_duration" style="display: block; margin-bottom: 5px; font-weight: 600;">Длительность приема (мин.)</label>
                    <input type="number" name="appointment_duration" id="appointment_duration" min="5" max="240" value="30" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                </div>
                
                <div style="flex: 1;">
                    <label for="max_patients" style="display: block; margin-bottom: 5px; font-weight: 600;">Макс. количество пациентов (0 - без ограничений)</label>
                    <input type="number" name="max_patients" id="max_patients" min="0" value="0" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                </div>
            </div>
            
            <div class="form-actions" style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn-cancel" style="padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; background-color: #f5f5f5; color: #333; margin-right: 10px;">Отмена</button>
                <button type="submit" name="set_schedule" class="btn-primary" style="padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; background-color: #4CAF50; color: white;">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Показ/скрытие вкладок
    function showTab(tabId, link) {
        // Скрываем все вкладки
        var tabs = document.getElementsByClassName('tab-content');
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].style.display = 'none';
        }
        
        // Убираем активный класс у всех ссылок
        var links = document.getElementsByClassName('tab-link');
        for (var i = 0; i < links.length; i++) {
            links[i].style.borderBottom = 'none';
        }
        
        // Показываем выбранную вкладку
        document.getElementById(tabId).style.display = 'block';
        
        // Делаем ссылку активной
        link.style.borderBottom = '2px solid #2196F3';
    }
    
    // Открытие модального окна для добавления расписания
    function addSchedule(dayOfWeek) {
        document.getElementById('modal-title').textContent = 'Добавить расписание на ' + getDayName(dayOfWeek);
        document.getElementById('day_of_week').value = dayOfWeek;
        document.getElementById('start_time_modal').value = '09:00';
        document.getElementById('end_time_modal').value = '18:00';
        document.getElementById('break_start_modal').value = '13:00';
        document.getElementById('break_end_modal').value = '14:00';
        document.getElementById('appointment_duration').value = '30';
        document.getElementById('max_patients').value = '0';
        
        document.getElementById('schedule-modal').style.display = 'block';
    }
    
    // Открытие модального окна для редактирования расписания
    function editSchedule(dayOfWeek, startTime, endTime, breakStart, breakEnd, appointmentDuration, maxPatients) {
        document.getElementById('modal-title').textContent = 'Изменить расписание на ' + getDayName(dayOfWeek);
        document.getElementById('day_of_week').value = dayOfWeek;
        document.getElementById('start_time_modal').value = startTime.substr(0, 5);
        document.getElementById('end_time_modal').value = endTime.substr(0, 5);
        document.getElementById('break_start_modal').value = breakStart ? breakStart.substr(0, 5) : '';
        document.getElementById('break_end_modal').value = breakEnd ? breakEnd.substr(0, 5) : '';
        document.getElementById('appointment_duration').value = appointmentDuration;
        document.getElementById('max_patients').value = maxPatients;
        
        document.getElementById('schedule-modal').style.display = 'block';
    }
    
    // Закрытие модального окна
    function closeModal() {
        document.getElementById('schedule-modal').style.display = 'none';
    }
    
    // Получение названия дня недели
    function getDayName(dayOfWeek) {
        var days = {
            1: 'Понедельник',
            2: 'Вторник',
            3: 'Среда',
            4: 'Четверг',
            5: 'Пятница',
            6: 'Суббота',
            7: 'Воскресенье'
        };
        
        return days[dayOfWeek];
    }
    
    // Включение/отключение полей времени при выборе "Весь день"
    function toggleTimeInputs(isAllDay) {
        var startTimeInput = document.getElementById('start_time');
        var endTimeInput = document.getElementById('end_time');
        var timeInputsContainer = document.querySelector('.time-inputs');
        
        if (isAllDay) {
            timeInputsContainer.style.display = 'none';
            startTimeInput.required = false;
            endTimeInput.required = false;
        } else {
            timeInputsContainer.style.display = 'flex';
            startTimeInput.required = true;
            endTimeInput.required = true;
        }
    }
    
    // Закрытие модального окна при клике вне его области
    window.onclick = function(event) {
        var modal = document.getElementById('schedule-modal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

<?php include "footer.php"; ?>
