<?php
// admin/medical_records.php
include("header.php");
require_once("../includes/medical_records_functions.php");

// Параметры фильтрации и пагинации
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$filter_patient = isset($_GET['patient']) ? intval($_GET['patient']) : null;
$filter_doctor = isset($_GET['doctor']) ? intval($_GET['doctor']) : null;
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Получение статистики по медицинским записям
$total_records = get_total_medical_records_count($database);
$total_diagnoses = get_total_diagnoses_count($database);
$total_prescriptions = get_total_prescriptions_count($database);

// Получение списка медицинских записей с учетом фильтров и пагинации
$records = get_all_medical_records_admin(
    $database, 
    $filter_patient, 
    $filter_doctor, 
    $filter_date_from, 
    $filter_date_to, 
    $limit, 
    $offset
);

// Получение общего количества записей по фильтру для пагинации
$total_filtered_records = get_filtered_medical_records_count(
    $database, 
    $filter_patient, 
    $filter_doctor, 
    $filter_date_from, 
    $filter_date_to
);

$total_pages = ceil($total_filtered_records / $limit);

// Получение списка врачей и пациентов для фильтров
$doctors = get_all_doctors_simple($database);
$patients = get_all_patients_simple($database);
?>

<!-- Заголовок и статистика -->
<div>
    <div class="dashboard-title" style="margin-left: 20px;">
        <h3>Управление медицинскими записями</h3>
    </div>

    <!-- Статистика -->
    <div class="dashboard-stats" style="display: flex; margin: 20px; gap: 15px;">
        <div class="stat-card" style="flex: 1; background-color: #f0f8ff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h4>Всего медицинских записей</h4>
            <p style="font-size: 24px; font-weight: 600; color: #2a7de1;"><?php echo $total_records; ?></p>
        </div>
        <div class="stat-card" style="flex: 1; background-color: #f0fff0; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h4>Всего поставленных диагнозов</h4>
            <p style="font-size: 24px; font-weight: 600; color: #2ea44f;"><?php echo $total_diagnoses; ?></p>
        </div>
        <div class="stat-card" style="flex: 1; background-color: #fff0f5; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h4>Всего выписанных рецептов</h4>
            <p style="font-size: 24px; font-weight: 600; color: #e6399b;"><?php echo $total_prescriptions; ?></p>
        </div>
    </div>

    <!-- Фильтры -->
    <div style="margin: 20px; background-color: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h4>Фильтры</h4>
        <form action="" method="GET" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <div>
                <label for="patient">Пациент:</label>
                <select name="patient" id="patient" class="input-text" style="width: 100%;">
                    <option value="">Все пациенты</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo $patient['pid']; ?>" <?php echo ($filter_patient == $patient['pid']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($patient['pname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="doctor">Врач:</label>
                <select name="doctor" id="doctor" class="input-text" style="width: 100%;">
                    <option value="">Все врачи</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo $doctor['docid']; ?>" <?php echo ($filter_doctor == $doctor['docid']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($doctor['docname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="date_from">Дата от:</label>
                <input type="date" name="date_from" id="date_from" class="input-text" style="width: 100%;" value="<?php echo $filter_date_from; ?>">
            </div>
            <div>
                <label for="date_to">Дата до:</label>
                <input type="date" name="date_to" id="date_to" class="input-text" style="width: 100%;" value="<?php echo $filter_date_to; ?>">
            </div>
            <div style="grid-column: span 2; text-align: center;">
                <button type="submit" class="btn-primary btn" style="padding: 10px 20px;">Применить фильтры</button>
                <a href="medical_records.php" class="btn-primary-soft btn" style="padding: 10px 20px;">Сбросить</a>
            </div>
        </form>
    </div>

    <!-- Таблица медицинских записей -->
    <div style="margin: 20px; background-color: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h4>Список медицинских записей</h4>
        <table class="styled-table" style="width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Пациент</th>
                    <th>Врач</th>
                    <th>Диагнозы</th>
                    <th>Рецепты</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">Медицинские записи не найдены</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?php echo $record['record_id']; ?></td>
                            <td><?php echo date('d.m.Y', strtotime($record['record_date'])); ?></td>
                            <td><?php echo htmlspecialchars($record['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                            <td><?php echo $record['diagnoses_count']; ?></td>
                            <td><?php echo $record['prescriptions_count']; ?></td>
                            <td>
                                <?php if ($record['is_finalized']): ?>
                                    <span style="color: #2ea44f;">Завершена</span>
                                <?php else: ?>
                                    <span style="color: #e67e22;">В процессе</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="view_medical_record.php?id=<?php echo $record['record_id']; ?>" class="btn-primary-soft btn button-icon" style="padding: 5px 10px;" title="Просмотр">
                                        <i class="fa fa-eye"></i> Просмотр
                                    </a>
                                    <a href="export_pdf.php?id=<?php echo $record['record_id']; ?>" class="btn-primary-soft btn button-icon" style="padding: 5px 10px; background-color: #f8f9fa;" title="Экспорт PDF">
                                        <i class="fa fa-file-pdf"></i> PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Пагинация -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination" style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?><?php echo $filter_patient ? '&patient='.$filter_patient : ''; ?><?php echo $filter_doctor ? '&doctor='.$filter_doctor : ''; ?><?php echo $filter_date_from ? '&date_from='.$filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to='.$filter_date_to : ''; ?>" class="btn-primary-soft btn" style="padding: 5px 10px;">&laquo; Назад</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="btn-primary btn" style="padding: 5px 10px;"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $filter_patient ? '&patient='.$filter_patient : ''; ?><?php echo $filter_doctor ? '&doctor='.$filter_doctor : ''; ?><?php echo $filter_date_from ? '&date_from='.$filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to='.$filter_date_to : ''; ?>" class="btn-primary-soft btn" style="padding: 5px 10px;"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo $filter_patient ? '&patient='.$filter_patient : ''; ?><?php echo $filter_doctor ? '&doctor='.$filter_doctor : ''; ?><?php echo $filter_date_from ? '&date_from='.$filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to='.$filter_date_to : ''; ?>" class="btn-primary-soft btn" style="padding: 5px 10px;">Вперед &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include("footer.php"); ?>
