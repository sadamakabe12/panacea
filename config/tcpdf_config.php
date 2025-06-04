<?php
// config/tcpdf_config.php - Конфигурация для TCPDF

// Константы для TCPDF
if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
    define('K_TCPDF_EXTERNAL_CONFIG', true);
}

// Путь к TCPDF
if (!defined('K_PATH_MAIN')) {
    define('K_PATH_MAIN', dirname(__FILE__) . '/../');
}

// URL главной директории
if (!defined('K_PATH_URL')) {
    define('K_PATH_URL', '/');
}

// Путь к шрифтам TCPDF
if (!defined('K_PATH_FONTS')) {
    define('K_PATH_FONTS', K_PATH_MAIN . 'vendor/tecnickcom/tcpdf/fonts/');
}

// Путь к кэшу шрифтов
if (!defined('K_PATH_CACHE')) {
    define('K_PATH_CACHE', K_PATH_MAIN . 'cache/');
}

// URL к шрифтам
if (!defined('K_PATH_URL_CACHE')) {
    define('K_PATH_URL_CACHE', K_PATH_URL . 'cache/');
}

// Путь к изображениям
if (!defined('K_PATH_IMAGES')) {
    define('K_PATH_IMAGES', K_PATH_MAIN . 'img/');
}

// Пустой файл изображения
if (!defined('K_BLANK_IMAGE')) {
    define('K_BLANK_IMAGE', K_PATH_IMAGES . '_blank.png');
}

// Высота ячейки по умолчанию
if (!defined('K_CELL_HEIGHT_RATIO')) {
    define('K_CELL_HEIGHT_RATIO', 1.25);
}

// Масштаб изображений по умолчанию
if (!defined('K_SMALL_RATIO')) {
    define('K_SMALL_RATIO', 2/3);
}

// Настройки для поддержки UTF-8
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}

if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
}

if (!defined('PDF_CREATOR')) {
    define('PDF_CREATOR', 'МИС Панацея');
}

if (!defined('PDF_AUTHOR')) {
    define('PDF_AUTHOR', 'Медицинская система Панацея');
}

if (!defined('PDF_HEADER_TITLE')) {
    define('PDF_HEADER_TITLE', 'Панацея');
}

if (!defined('PDF_HEADER_STRING')) {
    define('PDF_HEADER_STRING', 'Медицинская информационная система');
}

if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}

if (!defined('PDF_MARGIN_HEADER')) {
    define('PDF_MARGIN_HEADER', 5);
}

if (!defined('PDF_MARGIN_FOOTER')) {
    define('PDF_MARGIN_FOOTER', 10);
}

if (!defined('PDF_MARGIN_TOP')) {
    define('PDF_MARGIN_TOP', 27);
}

if (!defined('PDF_MARGIN_BOTTOM')) {
    define('PDF_MARGIN_BOTTOM', 25);
}

if (!defined('PDF_MARGIN_LEFT')) {
    define('PDF_MARGIN_LEFT', 15);
}

if (!defined('PDF_MARGIN_RIGHT')) {
    define('PDF_MARGIN_RIGHT', 15);
}

if (!defined('PDF_FONT_NAME_MAIN')) {
    define('PDF_FONT_NAME_MAIN', 'dejavusans');
}

if (!defined('PDF_FONT_SIZE_MAIN')) {
    define('PDF_FONT_SIZE_MAIN', 10);
}

if (!defined('PDF_FONT_NAME_DATA')) {
    define('PDF_FONT_NAME_DATA', 'dejavusans');
}

if (!defined('PDF_FONT_SIZE_DATA')) {
    define('PDF_FONT_SIZE_DATA', 8);
}

if (!defined('PDF_FONT_MONOSPACED')) {
    define('PDF_FONT_MONOSPACED', 'dejavusansmono');
}

if (!defined('PDF_IMAGE_SCALE_RATIO')) {
    define('PDF_IMAGE_SCALE_RATIO', 1.25);
}

// Создаем папку кэша, если она не существует
if (!file_exists(K_PATH_CACHE)) {
    mkdir(K_PATH_CACHE, 0755, true);
}
?>
