<?php
/**
 * Функции для шаблонизации интерфейса врача
 */

/**
 * Генерирует стандартный заголовок страницы для врача
 */
function renderDoctorPageHeader($title, $showBackButton = true, $backUrl = 'javascript:history.back()') {
    date_default_timezone_set('Asia/Yekaterinburg');
    $today = date('Y-m-d');
    
    $backButton = $showBackButton ? 
        '<a href="' . $backUrl . '"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>' 
        : '';
    
    return '
    <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;margin-top:25px;">
        <tr>
            <td width="13%">' . $backButton . '</td>
            <td>
                <p style="font-size: 23px;padding-left:12px;font-weight: 600;">' . htmlspecialchars($title) . '</p>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
                <p class="heading-sub12" style="padding: 0;margin: 0;">' . $today . '</p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
    </table>';
}

/**
 * Генерирует фильтр по дате
 */
function renderDateFilter($formAction = '', $selectedDate = '', $filterText = 'Фильтр') {
    return '
    <tr>
        <td colspan="4" style="padding-top:0px;width: 100%;">
            <center>
                <table class="filter-container" border="0">
                    <tr>
                        <td width="10%"></td>
                        <td width="5%" style="text-align: center;">Дата:</td>
                        <td width="30%">
                            <form action="' . $formAction . '" method="post">
                                <input type="date" name="sheduledate" id="date" class="input-text filter-container-items" style="margin: 0;width: 95%;" value="' . htmlspecialchars($selectedDate) . '">
                        </td>
                        <td width="12%">
                            <input type="submit" name="filter" value="' . htmlspecialchars($filterText) . '" class="btn-primary-soft btn button-icon btn-filter" style="padding: 15px; margin:0;width:100%">
                            </form>
                        </td>
                    </tr>
                </table>
            </center>
        </td>
    </tr>';
}

/**
 * Генерирует стандартную таблицу с данными
 */
function renderDataTable($headers, $data, $emptyMessage = 'Нет данных для отображения', $showAllButtonText = null, $showAllButtonUrl = null) {
    $headerCells = '';
    foreach ($headers as $header) {
        $headerCells .= '<th class="table-headin">' . htmlspecialchars($header) . '</th>';
    }
    
    $rows = '';
    if (empty($data)) {
        $colspan = count($headers);
        $showAllButton = $showAllButtonText && $showAllButtonUrl ? 
            '<a class="non-style-link" href="' . $showAllButtonUrl . '"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; ' . htmlspecialchars($showAllButtonText) . ' &nbsp;</button></a>' 
            : '';
        
        $rows = '<tr><td colspan="' . $colspan . '"><br><br><br><br><center><img src="../img/notfound.svg" width="25%"><br><p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">' . htmlspecialchars($emptyMessage) . '</p>' . $showAllButton . '</center><br><br><br><br></td></tr>';
    } else {
        foreach ($data as $row) {
            $rows .= '<tr>';
            foreach ($row as $cell) {
                $rows .= '<td>' . $cell . '</td>';
            }
            $rows .= '</tr>';
        }
    }
    
    return '
    <tr>
        <td colspan="4">
            <center>
                <div class="abc scroll">
                    <table width="93%" class="sub-table scrolldown" border="0">
                        <thead>
                            <tr>' . $headerCells . '</tr>
                        </thead>
                        <tbody>' . $rows . '</tbody>
                    </table>
                </div>
            </center>
        </td>
    </tr>';
}

/**
 * Генерирует действия для строки таблицы
 */
function renderRowActions($actions) {
    $actionButtons = '<div style="display:flex;justify-content: center;">';
    
    foreach ($actions as $action) {
        $actionButtons .= '<a href="' . $action['url'] . '" class="non-style-link">';
        $actionButtons .= '<button class="' . $action['class'] . '" style="' . $action['style'] . '">';
        $actionButtons .= '<font class="tn-in-text">' . htmlspecialchars($action['text']) . '</font>';
        $actionButtons .= '</button></a>';
        if (count($actions) > 1) {
            $actionButtons .= '&nbsp;';
        }
    }
    
    $actionButtons .= '</div>';
    return $actionButtons;
}

/**
 * Генерирует карточки статистики для главной страницы
 */
function renderDashboardCard($title, $value, $iconPath, $cardClass = 'dashboard-items') {
    return '
    <div class="' . $cardClass . '">
        <div class="dashboard-card-content">
            <div class="h1-dashboard">' . htmlspecialchars($value) . '</div>
            <div class="h3-dashboard">' . htmlspecialchars($title) . '</div>
        </div>
        <div class="dashboard-card-icon">
            <div class="dashboard-icons">
                <img src="' . htmlspecialchars($iconPath) . '" alt="' . htmlspecialchars($title) . ' Icon">
            </div>
        </div>
    </div>';
}

/**
 * Генерирует модальное окно
 */
function renderModal($id, $title, $content, $actions = []) {
    $actionButtons = '';
    foreach ($actions as $action) {
        $actionButtons .= '<a href="' . $action['url'] . '" class="non-style-link">';
        $actionButtons .= '<button class="' . $action['class'] . '" style="' . $action['style'] . '">';
        $actionButtons .= '<font class="tn-in-text">' . htmlspecialchars($action['text']) . '</font>';
        $actionButtons .= '</button></a>';
    }
    
    return '
    <div id="' . $id . '" class="overlay">
        <div class="popup">
            <center>
                <h2>' . htmlspecialchars($title) . '</h2>
                <a class="close" href="javascript:void(0)" onclick="document.getElementById(\'' . $id . '\').style.display=\'none\'">&times;</a>
                <div class="content">
                    ' . $content . '
                </div>
                <div style="display: flex;justify-content: center;">
                    ' . $actionButtons . '
                </div>
                <br><br>
            </center>
        </div>
    </div>';
}

/**
 * Генерирует стандартный поиск
 */
function renderSearchForm($placeholder = 'Поиск...', $buttonText = 'Поиск', $datalistOptions = []) {
    $datalist = '';
    if (!empty($datalistOptions)) {
        $datalist = '<datalist id="search-options">';
        foreach ($datalistOptions as $option) {
            $datalist .= '<option value="' . htmlspecialchars($option) . '">';
        }
        $datalist .= '</datalist>';
    }
    
    return '
    <form action="" method="post" class="header-search">
        <input type="search" name="search" class="input-text header-searchbar" 
               placeholder="' . htmlspecialchars($placeholder) . '"' . 
               (!empty($datalistOptions) ? ' list="search-options"' : '') . '>&nbsp;&nbsp;
        ' . $datalist . '
        <input type="submit" name="submit" value="' . htmlspecialchars($buttonText) . '" 
               class="login-btn btn-primary-soft btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
    </form>';
}

/**
 * Генерирует уведомление
 */
function renderAlert($message, $type = 'success') {
    $alertClass = $type === 'error' ? 'alert-danger' : 'alert-success';
    $alertColor = $type === 'error' ? '#d73a49' : '#28a745';
    
    return '
    <div id="alert-message" style="background-color: ' . $alertColor . '; color: white; padding: 10px; text-align: center; margin: 10px 0; border-radius: 5px;">
        ' . htmlspecialchars($message) . '
    </div>
    <script>
        setTimeout(function() {
            document.getElementById("alert-message").style.display = "none";
        }, 3000);
    </script>';
}

/**
 * Форматирует время в удобочитаемый формат
 */
function formatTime($time) {
    return substr($time, 0, 5);
}

/**
 * Форматирует дату в удобочитаемый формат
 */
function formatDate($date, $format = 'd.m.Y') {
    return date($format, strtotime($date));
}

/**
 * Проверяет и возвращает безопасное значение
 */
function safeValue($value, $default = '') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}

/**
 * Генерирует breadcrumb навигацию
 */
function renderBreadcrumb($items) {
    $breadcrumb = '<nav style="margin: 10px 0;"><ol style="list-style: none; display: flex; padding: 0;">';
    
    foreach ($items as $index => $item) {
        $isLast = $index === count($items) - 1;
        
        if ($isLast) {
            $breadcrumb .= '<li style="color: #666;">' . htmlspecialchars($item['title']) . '</li>';
        } else {
            $breadcrumb .= '<li><a href="' . $item['url'] . '" style="color: #0066cc; text-decoration: none;">' . htmlspecialchars($item['title']) . '</a> / </li>';
        }
    }
    
    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}
?>
