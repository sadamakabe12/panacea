<?php
// admin/audit_log.php
include("header.php");
require_once("../includes/medical_records_functions.php");

// Параметры фильтрации и пагинации
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$filter_user_type = isset($_GET['user_type']) && in_array($_GET['user_type'], ['admin', 'doctor', 'patient']) ? $_GET['user_type'] : null;
$filter_action = isset($_GET['action']) && in_array($_GET['action'], ['view', 'create', 'update', 'export', 'delete']) ? $_GET['action'] : null;
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Подготовка фильтров
$filters = [];
if ($filter_user_type) $filters['user_type'] = $filter_user_type;
if ($filter_action) $filters['action'] = $filter_action;
if ($filter_date_from) $filters['date_from'] = $filter_date_from;
if ($filter_date_to) $filters['date_to'] = $filter_date_to;

// Получение журнала аудита с учетом фильтров и пагинации
$audit_logs = get_admin_audit_log($database, $limit, $offset, $filters);

// Получение общего количества записей для пагинации
$total_logs = get_filtered_audit_log_count($database, $filters);
$total_pages = ceil($total_logs / $limit);
?>

<!-- Заголовок и информация -->
<div>
    <div class="dashboard-title" style="margin-left: 20px;">
        <h3>Журнал аудита медицинских записей</h3>
    </div>

    <!-- Фильтры -->
    <div style="margin: 20px; background-color: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h4>Фильтры</h4>
        <form action="" method="GET" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <div>
                <label for="user_type">Тип пользователя:</label>
                <select name="user_type" id="user_type" class="input-text" style="width: 100%;">
                    <option value="">Все пользователи</option>
                    <option value="admin" <?php echo ($filter_user_type === 'admin') ? 'selected' : ''; ?>>Администраторы</option>
                    <option value="doctor" <?php echo ($filter_user_type === 'doctor') ? 'selected' : ''; ?>>Врачи</option>
                    <option value="patient" <?php echo ($filter_user_type === 'patient') ? 'selected' : ''; ?>>Пациенты</option>
                </select>
            </div>
            <div>
                <label for="action">Действие:</label>
                <select name="action" id="action" class="input-text" style="width: 100%;">
                    <option value="">Все действия</option>
                    <option value="view" <?php echo ($filter_action === 'view') ? 'selected' : ''; ?>>Просмотр</option>
                    <option value="create" <?php echo ($filter_action === 'create') ? 'selected' : ''; ?>>Создание</option>
                    <option value="update" <?php echo ($filter_action === 'update') ? 'selected' : ''; ?>>Обновление</option>
                    <option value="export" <?php echo ($filter_action === 'export') ? 'selected' : ''; ?>>Экспорт</option>
                    <option value="delete" <?php echo ($filter_action === 'delete') ? 'selected' : ''; ?>>Удаление</option>
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
                <a href="audit_log.php" class="btn-primary-soft btn" style="padding: 10px 20px;">Сбросить</a>
            </div>
        </form>
    </div>

    <!-- Таблица журнала аудита -->
    <div style="margin: 20px; background-color: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h4>Журнал аудита</h4>
        <p>Всего записей: <?php echo $total_logs; ?></p>
        <table class="styled-table" style="width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата и время</th>
                    <th>Тип пользователя</th>
                    <th>ID пользователя</th>
                    <th>Пациент</th>
                    <th>Врач</th>
                    <th>Действие</th>
                    <th>IP адрес</th>
                    <th>Детали</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($audit_logs)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">Записи не найдены</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($audit_logs as $log): ?>
                        <tr>
                            <td><?php echo $log['audit_id']; ?></td>
                            <td><?php echo date('d.m.Y H:i:s', strtotime($log['timestamp'])); ?></td>
                            <td>
                                <?php 
                                    switch($log['user_type']) {
                                        case 'admin': echo 'Администратор'; break;
                                        case 'doctor': echo 'Врач'; break;
                                        case 'patient': echo 'Пациент'; break;
                                        default: echo $log['user_type'];
                                    }
                                ?>
                            </td>
                            <td><?php echo $log['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($log['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($log['doctor_name']); ?></td>
                            <td>
                                <?php 
                                    switch($log['action']) {
                                        case 'view': echo 'Просмотр'; break;
                                        case 'create': echo 'Создание'; break;
                                        case 'update': echo 'Обновление'; break;
                                        case 'export': echo 'Экспорт'; break;
                                        case 'delete': echo 'Удаление'; break;
                                        default: echo $log['action'];
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?: 'Не определен'); ?></td>
                            <td>
                                <?php if ($log['action_details']): ?>
                                    <button type="button" class="btn-primary-soft btn" style="padding: 5px 10px;" 
                                            onclick="showDetails('<?php echo htmlspecialchars(json_encode($log['action_details']), ENT_QUOTES); ?>')">
                                        Подробности
                                    </button>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
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
                <a href="?page=<?php echo $page-1; ?><?php echo $filter_user_type ? '&user_type='.$filter_user_type : ''; ?><?php echo $filter_action ? '&action='.$filter_action : ''; ?><?php echo $filter_date_from ? '&date_from='.$filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to='.$filter_date_to : ''; ?>" class="btn-primary-soft btn" style="padding: 5px 10px;">&laquo; Назад</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="btn-primary btn" style="padding: 5px 10px;"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $filter_user_type ? '&user_type='.$filter_user_type : ''; ?><?php echo $filter_action ? '&action='.$filter_action : ''; ?><?php echo $filter_date_from ? '&date_from='.$filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to='.$filter_date_to : ''; ?>" class="btn-primary-soft btn" style="padding: 5px 10px;"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo $filter_user_type ? '&user_type='.$filter_user_type : ''; ?><?php echo $filter_action ? '&action='.$filter_action : ''; ?><?php echo $filter_date_from ? '&date_from='.$filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to='.$filter_date_to : ''; ?>" class="btn-primary-soft btn" style="padding: 5px 10px;">Вперед &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно для деталей -->
<div id="detailsModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: white; margin: 15% auto; padding: 20px; border-radius: 10px; width: 60%; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
        <span style="float: right; cursor: pointer; font-size: 28px; font-weight: bold;" onclick="closeModal()">&times;</span>
        <h4>Детали операции</h4>
        <div id="detailsContent" style="margin-top: 20px;"></div>
    </div>
</div>

<script>
    function showDetails(details) {
        try {
            const parsedDetails = JSON.parse(details);
            let detailsHtml = '';
            
            if (typeof parsedDetails === 'object') {
                // Если это объект, отображаем его поля
                for (const key in parsedDetails) {
                    detailsHtml += `<p><strong>${key}:</strong> ${parsedDetails[key]}</p>`;
                }
            } else {
                // Иначе отображаем как текст
                detailsHtml = `<p>${parsedDetails}</p>`;
            }
            
            document.getElementById('detailsContent').innerHTML = detailsHtml;
        } catch (e) {
            // Если не JSON, просто выводим как текст
            document.getElementById('detailsContent').innerHTML = `<p>${details}</p>`;
        }
        
        document.getElementById('detailsModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }
    
    // Закрытие модального окна при клике вне его
    window.onclick = function(event) {
        const modal = document.getElementById('detailsModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
</script>

<?php include("footer.php"); ?>
