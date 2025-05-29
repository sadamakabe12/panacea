<?php
$pageTitle = 'Медицинские записи пациентов';
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'd') {
    header("location: ../login.php");
    exit();
}

include_once "../includes/init.php";
include_once "../includes/medical_records_functions.php";
include_once "../includes/patient_functions.php";
include "header-doctor.php";

$useremail = $_SESSION["user"];
$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["docid"];
$username = $userfetch["docname"];

date_default_timezone_set('Asia/Yekaterinburg');
$today = date('Y-m-d');

// Параметры фильтрации и пагинации
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$filter_patient = isset($_GET['patient']) ? intval($_GET['patient']) : null;
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
$filter_status = isset($_GET['status']) ? $_GET['status'] : null;

// Получение статистики врача
$doctor_stats = [
    'total_records' => 0,
    'recent_records' => 0,
    'completed_records' => 0,
    'in_progress_records' => 0
];

// Статистика общая
$sql = "SELECT COUNT(*) as total FROM medical_records WHERE doctor_id = ?";
$stmt = $database->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$doctor_stats['total_records'] = $result->fetch_assoc()['total'];

// Недавние записи (за последний месяц)
$sql = "SELECT COUNT(*) as recent FROM medical_records WHERE doctor_id = ? AND record_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
$stmt = $database->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$doctor_stats['recent_records'] = $result->fetch_assoc()['recent'];

// Завершенные записи
$sql = "SELECT COUNT(*) as completed FROM medical_records WHERE doctor_id = ? AND is_finalized = 1";
$stmt = $database->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$doctor_stats['completed_records'] = $result->fetch_assoc()['completed'];

// Записи в процессе
$sql = "SELECT COUNT(*) as in_progress FROM medical_records WHERE doctor_id = ? AND is_finalized = 0";
$stmt = $database->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$doctor_stats['in_progress_records'] = $result->fetch_assoc()['in_progress'];

// Получение записей с фильтрацией
$where_clauses = ["mr.doctor_id = ?"];
$params = [$userid];
$types = "i";

if ($filter_patient) {
    $where_clauses[] = "mr.patient_id = ?";
    $params[] = $filter_patient;
    $types .= "i";
}

if ($filter_date_from) {
    $where_clauses[] = "DATE(mr.record_date) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if ($filter_date_to) {
    $where_clauses[] = "DATE(mr.record_date) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

if ($filter_status !== null) {
    if ($filter_status === 'completed') {
        $where_clauses[] = "mr.is_finalized = 1";
    } else if ($filter_status === 'in_progress') {
        $where_clauses[] = "mr.is_finalized = 0";
    }
}

$where_clause = implode(" AND ", $where_clauses);

$sql = "SELECT mr.*, 
               p.pname as patient_name, 
               p.pemail as patient_email,
               p.ptel as patient_phone,
               (SELECT COUNT(*) FROM diagnoses WHERE record_id = mr.record_id) as diagnoses_count,
               (SELECT COUNT(*) FROM prescriptions WHERE record_id = mr.record_id) as prescriptions_count,
               (SELECT COUNT(*) FROM lab_tests WHERE record_id = mr.record_id) as lab_tests_count
        FROM medical_records mr
        JOIN patient p ON mr.patient_id = p.pid
        WHERE $where_clause
        ORDER BY mr.record_date DESC
        LIMIT ?, ?";

$params[] = $offset;
$params[] = $limit;
$types .= "ii";

$stmt = $database->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$medical_records = [];
while ($row = $result->fetch_assoc()) {
    $medical_records[] = $row;
}

// Получение общего количества записей для пагинации
$count_sql = "SELECT COUNT(*) as total 
              FROM medical_records mr
              JOIN patient p ON mr.patient_id = p.pid
              WHERE $where_clause";

$count_params = array_slice($params, 0, -2); // Убираем limit и offset
$count_types = substr($types, 0, -2);

$stmt = $database->prepare($count_sql);
if (!empty($count_params)) {
    $stmt->bind_param($count_types, ...$count_params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_records = $result->fetch_assoc()['total'];

$total_pages = ceil($total_records / $limit);

// Получение списка пациентов врача для фильтра
$patients_sql = "SELECT DISTINCT p.pid, p.pname 
                FROM patient p 
                JOIN medical_records mr ON p.pid = mr.patient_id 
                WHERE mr.doctor_id = ? 
                ORDER BY p.pname";
$stmt = $database->prepare($patients_sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}
?>

<div class="dash-body">
    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">
                <a href="index.php">
                    <button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                        <font class="tn-in-text">Назад</font>
                    </button>
                </a>
            </td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Медицинские записи пациентов</p>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                    Сегодняшняя дата
                </p>
                <p class="heading-sub12" style="padding: 0;margin: 0;"><?php echo date('d.m.Y'); ?></p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;">
                    <img src="../img/calendar.svg" width="100%">
                </button>
            </td>
        </tr>
        
        <!-- Статистические карточки -->
        <tr>
            <td colspan="4">
                <div class="dashboard-stats" style="display: flex; margin: 20px; gap: 15px;">
                    <div class="stat-card" style="flex: 1; background-color: #f0f8ff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h4 style="margin: 0 0 10px 0; color: #2a7de1;">Всего записей</h4>
                        <p style="font-size: 28px; font-weight: 600; color: #2a7de1; margin: 0;"><?php echo $doctor_stats['total_records']; ?></p>
                    </div>
                    <div class="stat-card" style="flex: 1; background-color: #f0fff0; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h4 style="margin: 0 0 10px 0; color: #2ea44f;">За последний месяц</h4>
                        <p style="font-size: 28px; font-weight: 600; color: #2ea44f; margin: 0;"><?php echo $doctor_stats['recent_records']; ?></p>
                    </div>
                    <div class="stat-card" style="flex: 1; background-color: #fffbf0; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h4 style="margin: 0 0 10px 0; color: #d4a853;">Завершенных</h4>
                        <p style="font-size: 28px; font-weight: 600; color: #d4a853; margin: 0;"><?php echo $doctor_stats['completed_records']; ?></p>
                    </div>
                    <div class="stat-card" style="flex: 1; background-color: #fff0f5; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h4 style="margin: 0 0 10px 0; color: #e67e22;">В процессе</h4>
                        <p style="font-size: 28px; font-weight: 600; color: #e67e22; margin: 0;"><?php echo $doctor_stats['in_progress_records']; ?></p>
                    </div>
                </div>
            </td>
        </tr>
        
        <!-- Фильтры -->
        <tr>
            <td colspan="4">
                <div class="filters" style="margin: 20px; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h4 style="margin-top: 0;">Фильтры</h4>
                    <form action="" method="GET" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; align-items: end;">
                        <div>
                            <label for="patient" style="display: block; font-weight: bold; margin-bottom: 5px;">Пациент:</label>
                            <select name="patient" id="patient" class="input-text" style="width: 100%; padding: 8px;">
                                <option value="">Все пациенты</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['pid']; ?>" <?php echo $filter_patient == $patient['pid'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['pname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="date_from" style="display: block; font-weight: bold; margin-bottom: 5px;">Дата с:</label>
                            <input type="date" name="date_from" id="date_from" class="input-text" style="width: 100%; padding: 8px;" value="<?php echo htmlspecialchars($filter_date_from ?? ''); ?>">
                        </div>
                        
                        <div>
                            <label for="date_to" style="display: block; font-weight: bold; margin-bottom: 5px;">Дата по:</label>
                            <input type="date" name="date_to" id="date_to" class="input-text" style="width: 100%; padding: 8px;" value="<?php echo htmlspecialchars($filter_date_to ?? ''); ?>">
                        </div>
                        
                        <div>
                            <label for="status" style="display: block; font-weight: bold; margin-bottom: 5px;">Статус:</label>
                            <select name="status" id="status" class="input-text" style="width: 100%; padding: 8px;">
                                <option value="">Все статусы</option>
                                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Завершена</option>
                                <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>В процессе</option>
                            </select>
                        </div>
                        
                        <div style="grid-column: span 4; display: flex; gap: 10px;">
                            <button type="submit" class="btn-primary btn" style="padding: 10px 20px;">Применить фильтры</button>
                            <a href="medical_records.php" class="btn-primary-soft btn" style="padding: 10px 20px; text-decoration: none;">Сбросить</a>
                        </div>
                    </form>
                </div>
            </td>
        </tr>
        
        <!-- Список медицинских записей -->
        <tr>
            <td colspan="4">
                <div class="records-container" style="margin: 20px; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h4>Список медицинских записей (<?php echo $total_records; ?>)</h4>
                    
                    <?php if (empty($medical_records)): ?>
                        <div class="no-records" style="text-align: center; padding: 50px 0;">
                            <img src="../img/notfound.svg" width="15%" style="opacity: 0.5;">
                            <p style="margin-top: 20px; font-size: 18px; color: #666;">Медицинские записи не найдены</p>
                        </div>
                    <?php else: ?>
                        <table class="styled-table" style="width: 100%; margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Дата</th>
                                    <th>Пациент</th>
                                    <th>Основная жалоба</th>
                                    <th>Диагнозы</th>
                                    <th>Рецепты</th>
                                    <th>Тесты</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medical_records as $record): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo $record['record_id']; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($record['record_date'])); ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($record['patient_name']); ?></strong><br>
                                            <small style="color: #666;"><?php echo htmlspecialchars($record['patient_email']); ?></small>
                                        </div>
                                    </td>
                                    <td style="max-width: 200px;">
                                        <?php 
                                        $complaint = $record['chief_complaint'];
                                        echo mb_strlen($complaint) > 80 ? htmlspecialchars(mb_substr($complaint, 0, 80)) . '...' : htmlspecialchars($complaint);
                                        ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge" style="background-color: #17a2b8; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;">
                                            <?php echo $record['diagnoses_count']; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge" style="background-color: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;">
                                            <?php echo $record['prescriptions_count']; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge" style="background-color: #ffc107; color: #212529; padding: 3px 8px; border-radius: 12px; font-size: 12px;">
                                            <?php echo $record['lab_tests_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($record['is_finalized']): ?>
                                            <span class="status-badge completed" style="padding: 5px 12px; border-radius: 15px; color: white; background-color: #28a745; font-size: 12px;">
                                                Завершена
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge in-progress" style="padding: 5px 12px; border-radius: 15px; color: #212529; background-color: #ffc107; font-size: 12px;">
                                                В процессе
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <a href="medical_record.php?record_id=<?php echo $record['record_id']; ?>&patient_id=<?php echo $record['patient_id']; ?>" 
                                               class="btn-action" style="padding: 6px 12px; border-radius: 5px; text-decoration: none; background-color: #007bff; color: white; font-size: 11px;">
                                                Просмотр
                                            </a>
                                            <a href="export_pdf.php?record_id=<?php echo $record['record_id']; ?>" 
                                               class="btn-action" style="padding: 6px 12px; border-radius: 5px; text-decoration: none; background-color: #dc3545; color: white; font-size: 11px;">
                                                PDF
                                            </a>
                                            <a href="patient.php?id=<?php echo $record['patient_id']; ?>" 
                                               class="btn-action" style="padding: 6px 12px; border-radius: 5px; text-decoration: none; background-color: #6f42c1; color: white; font-size: 11px;">
                                                Пациент
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Пагинация -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination" style="margin-top: 20px; text-align: center;">
                            <?php 
                            $current_params = $_GET;
                            
                            // Кнопка "Предыдущая"
                            if ($page > 1): 
                                $current_params['page'] = $page - 1;
                                $prev_url = '?' . http_build_query($current_params);
                            ?>
                                <a href="<?php echo $prev_url; ?>" class="btn-primary-soft btn" style="margin: 0 5px; padding: 8px 15px; text-decoration: none;">« Предыдущая</a>
                            <?php endif; ?>
                            
                            <!-- Номера страниц -->
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                                $current_params['page'] = $i;
                                $page_url = '?' . http_build_query($current_params);
                            ?>
                                <a href="<?php echo $page_url; ?>" 
                                   class="btn-<?php echo $i == $page ? 'primary' : 'primary-soft'; ?> btn" 
                                   style="margin: 0 2px; padding: 8px 12px; text-decoration: none;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <!-- Кнопка "Следующая" -->
                            <?php 
                            if ($page < $total_pages): 
                                $current_params['page'] = $page + 1;
                                $next_url = '?' . http_build_query($current_params);
                            ?>
                                <a href="<?php echo $next_url; ?>" class="btn-primary-soft btn" style="margin: 0 5px; padding: 8px 15px; text-decoration: none;">Следующая »</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
</div>

<style>
.styled-table {
    border-collapse: collapse;
    width: 100%;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
    border-radius: 10px;
    overflow: hidden;
}

.styled-table thead tr {
    background-color: #007bff;
    color: #ffffff;
    text-align: left;
}

.styled-table th,
.styled-table td {
    padding: 12px 8px;
    border-bottom: 1px solid #dddddd;
    font-size: 14px;
}

.styled-table tbody tr {
    background-color: #f8f9fa;
}

.styled-table tbody tr:nth-of-type(even) {
    background-color: #ffffff;
}

.styled-table tbody tr:hover {
    background-color: #e9ecef;
    cursor: pointer;
}

.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-action {
    transition: all 0.2s ease;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.filters {
    border-left: 4px solid #007bff;
}

.records-container {
    border-left: 4px solid #28a745;
}

.badge {
    font-weight: bold;
    min-width: 20px;
    display: inline-block;
}

.pagination a {
    transition: all 0.2s ease;
}

.pagination a:hover {
    transform: translateY(-1px);
}
</style>

<?php include "footer.php"; ?>
