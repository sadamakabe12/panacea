<?php
$pageTitle = 'Мое расписание';
include "header-doctor.php";

// Настройка временной зоны
date_default_timezone_set('Asia/Yekaterinburg');

// Получаем текущую неделю или неделю из параметра
$currentWeek = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($currentWeek)));
$weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($currentWeek)));

// Навигация по неделям
$prevWeek = date('Y-m-d', strtotime('-1 week', strtotime($weekStart)));
$nextWeek = date('Y-m-d', strtotime('+1 week', strtotime($weekStart)));

// Получаем расписание на текущую неделю
$result = getDoctorSchedule($database, $userid, $weekStart, $weekEnd);
$schedules = [];

while ($row = $result->fetch_assoc()) {
    $date = $row['scheduledate'];
    $time = $row['scheduletime'];
    $schedules[$date][$time] = $row;
}

// Русские названия дней недели
$dayNamesRu = [
    'Monday' => 'Понедельник',
    'Tuesday' => 'Вторник', 
    'Wednesday' => 'Среда',
    'Thursday' => 'Четверг',
    'Friday' => 'Пятница',
    'Saturday' => 'Суббота',
    'Sunday' => 'Воскресенье'
];

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

// Функция для форматирования даты на русском языке
function formatDateRu($date, $monthNamesRu) {
    $day = date('j', strtotime($date));
    $monthEn = date('F', strtotime($date));
    $year = date('Y', strtotime($date));
    $month = $monthNamesRu[$monthEn];
    return "$day $month $year";
}

// Генерируем дни недели
$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days", strtotime($weekStart)));
    $dayNameEn = date('l', strtotime($date));
    $weekDays[] = [
        'date' => $date,
        'dayName' => $dayNamesRu[$dayNameEn],
        'dayNumber' => date('j', strtotime($date)),
        'isToday' => $date === date('Y-m-d')
    ];
}

// Временные слоты (8:00 - 17:00) с интервалом 15 минут
$timeSlots = [];
for ($hour = 8; $hour < 18; $hour++) {
    // Для 17 часа добавляем только слот 17:00
    if ($hour == 17) {
        $timeSlots[] = sprintf('%02d:00:00', $hour);
        break;
    }
    $timeSlots[] = sprintf('%02d:00:00', $hour);
    $timeSlots[] = sprintf('%02d:15:00', $hour);
    $timeSlots[] = sprintf('%02d:30:00', $hour);
    $timeSlots[] = sprintf('%02d:45:00', $hour);
}
?>

<style>
.schedule-container {
    margin: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.schedule-header {
    background: linear-gradient(135deg, #0ad84f 0%, #11af80 100%);
    color: white;
    padding: 20px;
    text-align: center;
}

.week-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
}

.week-navigation button {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
}

.week-navigation button:hover {
    background: rgba(255,255,255,0.4);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.schedule-grid {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    border-collapse: collapse;
}

.time-header, .day-header {
    background: #f8f9fa;
    padding: 15px 10px;
    font-weight: 600;
    border: 1px solid #e9ecef;
    text-align: center;
}

.day-header {
    background: #D8EBFA;
}

.day-header.today {
    background: linear-gradient(135deg, #0ad84f 0%, #11af80 100%);
    color: white;
}

.time-slot {
    padding: 8px;
    border: 1px solid #e9ecef;
    text-align: center;
    font-size: 12px;
    color: #666;
}

.schedule-cell {
    border: 1px solid #e9ecef;
    padding: 5px;
    min-height: 40px;
    position: relative;
    cursor: pointer;
    transition: background-color 0.2s;
}

.schedule-cell:hover {
    background-color: #f0f0f0;
}

.schedule-cell.has-appointment {
    background-color: #e8f5e8;
    border-left: 4px solid #0ad84f;
}

.schedule-cell.has-appointment:hover {
    background-color: #d4edda;
}

.appointment-info {
    font-size: 11px;
    color: #0edd87;
    font-weight: 500;
    text-align: center;
    padding: 2px;
}

.week-info {
    font-size: 18px;
    font-weight: 300;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: none;
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.modal-close:hover {
    color: #333;
}
</style>

<div class="dash-body">
    <?php echo renderDoctorPageHeader('Мое расписание'); ?>
    
    <div class="schedule-container">
        <div class="schedule-header">
            <div class="week-navigation">
                <a href="?week=<?php echo $prevWeek; ?>">
                    <button>← Предыдущая неделя</button>
                </a>                <div class="week-info">
                    <?php echo formatDateRu($weekStart, $monthNamesRu) . ' - ' . formatDateRu($weekEnd, $monthNamesRu); ?>
                </div>
                <a href="?week=<?php echo $nextWeek; ?>">
                    <button>Следующая неделя →</button>
                </a>
            </div>
        </div>
        
        <div class="schedule-grid">
            <!-- Заголовок времени -->
            <div class="time-header">Время</div>
            
            <!-- Заголовки дней недели -->
            <?php foreach ($weekDays as $day): ?>
                <div class="day-header<?php echo $day['isToday'] ? ' today' : ''; ?>">
                    <div><?php echo $day['dayName']; ?></div>
                    <div><?php echo $day['dayNumber']; ?></div>
                </div>
            <?php endforeach; ?>
            
            <!-- Временные слоты и ячейки расписания -->
            <?php foreach ($timeSlots as $timeSlot): ?>
                <div class="time-slot"><?php echo substr($timeSlot, 0, 5); ?></div>
                
                <?php foreach ($weekDays as $day): ?>
                    <?php 
                    $hasAppointment = isset($schedules[$day['date']][$timeSlot]);
                    $appointmentData = $hasAppointment ? $schedules[$day['date']][$timeSlot] : null;
                    ?>
                    <div class="schedule-cell<?php echo $hasAppointment ? ' has-appointment' : ''; ?>"
                         onclick="<?php echo $hasAppointment ? 'showAppointmentDetails(' . $appointmentData['scheduleid'] . ')' : ''; ?>">
                        <?php if ($hasAppointment): ?>
                            <div class="appointment-info">
                                <?php if ($appointmentData['booked_slots'] > 0): ?>
                                    Занято (<?php echo $appointmentData['booked_slots']; ?>)
                                <?php else: ?>
                                    Свободно
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Модальное окно для деталей записи -->
<div id="appointmentModal" class="modal-overlay">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div class="modal-header">Детали записи</div>
        <div id="modalBody">
            <!-- Контент будет загружен через JavaScript -->
        </div>
    </div>
</div>

<script>
function showAppointmentDetails(scheduleId) {
    const modal = document.getElementById('appointmentModal');
    const modalBody = document.getElementById('modalBody');
    
    // Показываем модальное окно
    modal.style.display = 'flex';
    
    // Загружаем данные через AJAX
    fetch('get_schedule_details.php?id=' + scheduleId)
        .then(response => response.text())
        .then(data => {
            modalBody.innerHTML = data;
        })
        .catch(error => {
            modalBody.innerHTML = '<p>Ошибка загрузки данных</p>';
        });
}

function closeModal() {
    document.getElementById('appointmentModal').style.display = 'none';
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    const modal = document.getElementById('appointmentModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php include "footer.php"; ?>