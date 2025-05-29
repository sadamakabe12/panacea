<?php 
include "header.php";
require_once "../includes/appointment_functions.php";
require_once "../includes/schedule_functions.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"]=="" || $_SESSION['usertype']!='p') {
    header("location: ../login.php");
    exit;
}
$useremail = $_SESSION["user"];

// Get patient data using the function from appointment_functions.php
$userfetch = get_patient_by_email($database, $useremail);
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

date_default_timezone_set('Asia/Yekaterinburg');
$today = date('Y-m-d');

// Обрабатываем запрос на выбор даты
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;
$selected_specialty = isset($_GET['specialty']) ? intval($_GET['specialty']) : null;

// Получаем специальности для фильтра
$specialties_query = $database->query("SELECT DISTINCT specialty_id, sname FROM specialties ORDER BY sname");
$specialties = [];
while ($row = $specialties_query->fetch_assoc()) {
    $specialties[$row['specialty_id']] = $row['sname'];
}

// Получаем список доступных врачей на выбранную дату
$available_doctors = get_available_doctors_for_date($database, $selected_date, $selected_specialty);

// Обрабатываем запрос на запись к врачу
$booking_success = false;
$booking_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $docid = intval($_POST['docid']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $duration = intval($_POST['duration']);
    $reason = $_POST['reason'] ?? '';
    
    // Создаем запись на прием
    $result = create_appointment($database, $docid, $userid, $appointment_date, $appointment_time, $duration, $reason);
    
    if ($result) {
        $booking_success = true;
        $appointment_id = $result;
          // Отправляем уведомление о подтверждении записи
        require_once "../includes/notification_functions.php";
        
        // Проверяем и инициализируем систему уведомлений если нужно
        initialize_notification_system($database);
        
        // Получаем предпочтительный метод уведомления пациента
        $query = "SELECT notification_preference FROM patient WHERE pid = ?";
        $stmt = $database->prepare($query);
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $notification_method = $row['notification_preference'] ?? 'email';
        
        // Не отправляем уведомление, если пациент выбрал 'none'
        if ($notification_method != 'none') {
            send_appointment_confirmation($database, $appointment_id, $notification_method);
        }
    } else {
        $booking_error = true;
    }
}
?>

<div class="dash-body">
    <!-- Сообщение об успешной записи -->
    <?php if ($booking_success): ?>
        <div class="success-message" style="background-color: #4CAF50; color: white; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;">
            <h3 style="margin: 0;">Запись успешно создана!</h3>
            <p style="margin: 10px 0 0 0;">Ваша запись успешно создана. Вы можете просмотреть детали в разделе "Мои записи".</p>
            <a href="appointment.php" style="display: inline-block; margin-top: 10px; padding: 8px 15px; background-color: white; color: #4CAF50; text-decoration: none; border-radius: 3px;">Перейти к моим записям</a>
        </div>
    <?php endif; ?>
    
    <!-- Сообщение об ошибке -->
    <?php if ($booking_error): ?>
        <div class="error-message" style="background-color: #f44336; color: white; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;">
            <h3 style="margin: 0;">Ошибка при создании записи</h3>
            <p style="margin: 10px 0 0 0;">К сожалению, произошла ошибка при создании записи. Возможно, это время уже занято. Пожалуйста, выберите другое время или обратитесь в клинику.</p>
        </div>
    <?php endif; ?>

    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="index.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Запись на прием к врачу</p>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
                <p class="heading-sub12" style="padding: 0;margin: 0;">
                    <?php echo date('d-m-Y', strtotime($today)); ?>
                </p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
    </table>

    <div style="padding: 0 20px; margin-top: 20px;">
        <!-- Фильтр по дате и специальности -->
        <div class="booking-filter" style="background-color: #f5f5f5; border-radius: 5px; padding: 20px; margin-bottom: 30px;">
            <form action="" method="get" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                <div>
                    <label for="date" style="display: block; margin-bottom: 5px; font-weight: 600;">Дата приема</label>
                    <input type="date" name="date" id="date" value="<?php echo $selected_date; ?>" min="<?php echo $today; ?>" required style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                </div>
                
                <div>
                    <label for="specialty" style="display: block; margin-bottom: 5px; font-weight: 600;">Специальность (необязательно)</label>
                    <select name="specialty" id="specialty" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd; min-width: 200px;">
                        <option value="">Все специальности</option>
                        <?php foreach ($specialties as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo ($selected_specialty == $id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="btn-primary" style="padding: 8px 20px; border-radius: 5px; border: none; cursor: pointer; background-color: #2196F3; color: white;">Найти доступное время</button>
                </div>
            </form>
        </div>

        <!-- Список доступных врачей -->
        <div class="doctors-list">
            <h3>Доступные врачи на <?php echo date('d.m.Y', strtotime($selected_date)); ?></h3>
            
            <?php if (empty($available_doctors)): ?>
                <div class="no-doctors" style="text-align: center; padding: 50px; background-color: #f9f9f9; border-radius: 5px; margin-top: 20px;">
                    <img src="../img/notfound.svg" width="100" style="margin-bottom: 20px;" alt="Не найдено">
                    <p style="font-size: 18px; color: #555; margin-bottom: 10px;">На выбранную дату нет доступных врачей</p>
                    <p style="color: #777;">Пожалуйста, выберите другую дату или специальность</p>
                </div>
            <?php else: ?>
                <div class="doctors-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($available_doctors as $doctor_data): ?>
                        <div class="doctor-card" style="border: 1px solid #ddd; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <div class="doctor-info" style="padding: 15px; background-color: #f9f9f9; border-bottom: 1px solid #ddd;">
                                <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($doctor_data['doctor']['docname']); ?></h4>
                                
                                <!-- Здесь можно добавить специальность врача, если она доступна -->
                                <?php 
                                // Получаем специальности врача
                                $docid = $doctor_data['doctor']['docid'];
                                $spec_query = $database->prepare("SELECT s.sname FROM doctor_specialty ds
                                               JOIN specialties s ON ds.specialty_id = s.specialty_id
                                               WHERE ds.docid = ?");
                                $spec_query->bind_param("i", $docid);
                                $spec_query->execute();
                                $spec_result = $spec_query->get_result();
                                
                                $specialties = [];
                                while ($spec = $spec_result->fetch_assoc()) {
                                    $specialties[] = $spec['sname'];
                                }
                                
                                if (!empty($specialties)) {
                                    echo '<p style="margin: 0; color: #666;">'. htmlspecialchars(implode(', ', $specialties)) .'</p>';
                                }
                                ?>
                                
                                <p style="margin: 10px 0 0 0; color: #666;">Часы работы: 
                                    <?php echo substr($doctor_data['schedule']['start_time'], 0, 5); ?> - 
                                    <?php echo substr($doctor_data['schedule']['end_time'], 0, 5); ?>
                                </p>
                                <p style="margin: 5px 0 0 0; color: #666;">Длительность приема: 
                                    <?php echo $doctor_data['schedule']['appointment_duration']; ?> мин.
                                </p>
                            </div>
                            
                            <div class="available-slots" style="padding: 15px; max-height: 200px; overflow-y: auto;">
                                <h5 style="margin: 0 0 10px 0;">Доступное время:</h5>
                                
                                <?php if (empty($doctor_data['available_slots'])): ?>
                                    <p style="color: #888;">Нет доступных слотов на выбранную дату</p>
                                <?php else: ?>
                                    <div class="slots-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px;">
                                        <?php foreach ($doctor_data['available_slots'] as $slot): ?>
                                            <button type="button" onclick="selectTimeSlot(<?php echo $docid; ?>, '<?php echo $selected_date; ?>', '<?php echo $slot['time']; ?>', <?php echo $slot['duration']; ?>)" class="time-slot" style="padding: 8px; border-radius: 3px; border: 1px solid #2196F3; background-color: white; cursor: pointer; color: #2196F3; transition: all 0.2s;">
                                                <?php echo $slot['time']; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модальное окно для подтверждения записи -->
<div id="booking-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <span class="close" onclick="closeModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <h3 style="margin-top: 0;">Подтверждение записи</h3>
        
        <div id="booking-details" style="margin-bottom: 20px;">
            <!-- Детали записи будут заполнены через JavaScript -->
        </div>
        
        <form id="booking-form" action="" method="post">
            <input type="hidden" name="docid" id="booking-docid">
            <input type="hidden" name="appointment_date" id="booking-date">
            <input type="hidden" name="appointment_time" id="booking-time">
            <input type="hidden" name="duration" id="booking-duration">
            
            <div class="form-row" style="margin-bottom: 15px;">
                <label for="reason" style="display: block; margin-bottom: 5px; font-weight: 600;">Причина обращения:</label>
                <textarea name="reason" id="reason" rows="3" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;"></textarea>
            </div>
            
            <div class="form-actions" style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn-cancel" style="padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; background-color: #f5f5f5; color: #333; margin-right: 10px;">Отмена</button>
                <button type="submit" name="book_appointment" class="btn-primary" style="padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; background-color: #4CAF50; color: white;">Подтвердить запись</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Функция для выбора временного слота
    function selectTimeSlot(docid, date, time, duration) {
        // Получаем данные о враче
        var doctorName = getDoctorName(docid);
        
        // Заполняем детали записи
        var detailsHtml = `
            <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                <p style="margin: 5px 0;"><strong>Врач:</strong> ${doctorName}</p>
                <p style="margin: 5px 0;"><strong>Дата:</strong> ${formatDate(date)}</p>
                <p style="margin: 5px 0;"><strong>Время:</strong> ${time}</p>
                <p style="margin: 5px 0;"><strong>Длительность приема:</strong> ${duration} мин.</p>
            </div>
        `;
        
        document.getElementById('booking-details').innerHTML = detailsHtml;
        
        // Заполняем скрытые поля формы
        document.getElementById('booking-docid').value = docid;
        document.getElementById('booking-date').value = date;
        document.getElementById('booking-time').value = time;
        document.getElementById('booking-duration').value = duration;
        
        // Показываем модальное окно
        document.getElementById('booking-modal').style.display = 'block';
    }
    
    // Функция для получения имени врача по ID
    function getDoctorName(docid) {
        <?php
        echo "var doctors = {";
        foreach ($available_doctors as $doctor) {
            echo "{$doctor['doctor']['docid']}: '".addslashes($doctor['doctor']['docname'])."',";
        }
        echo "};";
        ?>
        
        return doctors[docid] || 'Неизвестный врач';
    }
    
    // Функция для форматирования даты
    function formatDate(dateString) {
        var date = new Date(dateString);
        var day = date.getDate();
        var month = date.getMonth() + 1;
        var year = date.getFullYear();
        
        day = (day < 10) ? '0' + day : day;
        month = (month < 10) ? '0' + month : month;
        
        return day + '.' + month + '.' + year;
    }
    
    // Функция для закрытия модального окна
    function closeModal() {
        document.getElementById('booking-modal').style.display = 'none';
    }
    
    // Закрытие модального окна при клике вне его области
    window.onclick = function(event) {
        var modal = document.getElementById('booking-modal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    
    // Стилизация временных слотов при наведении
    document.addEventListener('DOMContentLoaded', function() {
        var timeSlots = document.querySelectorAll('.time-slot');
        
        timeSlots.forEach(function(slot) {
            slot.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#2196F3';
                this.style.color = 'white';
            });
            
            slot.addEventListener('mouseout', function() {
                this.style.backgroundColor = 'white';
                this.style.color = '#2196F3';
            });
        });
    });
</script>

<?php include "footer.php"; ?>
