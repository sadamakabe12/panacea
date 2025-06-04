<?php
// admin/pdf_generator.php - Класс для генерации PDF-файлов с помощью TCPDF

// Подключаем autoloader для TCPDF
require_once(__DIR__ . '/../vendor/autoload.php');

// Определяем константы TCPDF, если они не определены
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
}
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}
if (!defined('PDF_IMAGE_SCALE_RATIO')) {
    define('PDF_IMAGE_SCALE_RATIO', 1.25);
}

class PDFGenerator {
    private $pdf;
    private $title = '';
    private $footer = '';
    private $marginLeft = 20;
    private $marginRight = 20;
    private $marginTop = 30;
    private $marginBottom = 30;
    
    /**
     * Конструктор класса
     * @param string $title Заголовок PDF-файла
     */
    public function __construct($title = 'Медицинская карта') {
        $this->title = $title;
        
        // Создаем новый экземпляр TCPDF
        $this->pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Устанавливаем информацию о документе
        $this->pdf->SetCreator('Панацея МИС');
        $this->pdf->SetAuthor('Медицинская информационная система');
        $this->pdf->SetTitle($this->title);
        $this->pdf->SetSubject('Медицинская карта');
        $this->pdf->SetKeywords('медицина, карта, пациент');
        
        // Устанавливаем поля
        $this->pdf->SetMargins($this->marginLeft, $this->marginTop, $this->marginRight);
        $this->pdf->SetHeaderMargin(5);
        $this->pdf->SetFooterMargin(10);
        
        // Устанавливаем автоматический разрыв страниц
        $this->pdf->SetAutoPageBreak(TRUE, $this->marginBottom);
        
        // Устанавливаем коэффициент масштабирования изображений
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Отключаем заголовок и футер по умолчанию
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Устанавливаем шрифт по умолчанию
        $this->pdf->SetFont('dejavusans', '', 11);
        
        // Добавляем первую страницу
        $this->pdf->AddPage();
        
        // Добавляем заголовок документа
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, $this->title, 0, 1, 'C');
        $this->pdf->Ln(5);
        $this->pdf->SetFont('dejavusans', '', 11);
    }
    
    /**
     * Добавляет раздел с заголовком
     * @param string $title Заголовок раздела
     */
    public function addSection($title) {
        $this->pdf->Ln(5);
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 8, $title, 0, 1, 'L');
        $this->pdf->Line($this->pdf->GetX(), $this->pdf->GetY(), $this->pdf->GetX() + 170, $this->pdf->GetY());
        $this->pdf->Ln(3);
        $this->pdf->SetFont('dejavusans', '', 11);
    }
    
    /**
     * Добавляет подраздел
     * @param string $title Заголовок подраздела
     */
    public function addSubsection($title) {
        $this->pdf->Ln(3);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 6, $title, 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 11);
    }
    
    /**
     * Добавляет абзац текста
     * @param string $text Текст абзаца
     */
    public function addParagraph($text) {
        $this->pdf->MultiCell(0, 5, $text, 0, 'L', 0, 1);
        $this->pdf->Ln(1);
    }
    
    /**
     * Добавляет футер к документу
     * @param string $text Текст футера
     */
    public function addFooter($text) {
        $this->footer = $text;
    }
      /**
     * Выводит PDF-файл
     * @param string $filename Имя файла (необязательно)
     * @param string $mode Режим вывода: D=download, F=file, I=inline
     */
    public function output($filename = null, $mode = 'D') {
        // Добавляем футер, если он задан
        if ($this->footer) {
            $this->pdf->Ln(10);
            $this->pdf->SetFont('dejavusans', 'I', 9);
            $this->pdf->Cell(0, 5, $this->footer, 0, 1, 'C');
        }
        
        // Определяем имя файла
        if (!$filename) {
            $filename = $this->title . '.pdf';
        }
        
        // Для режима файла используем абсолютный путь
        if ($mode === 'F') {
            $filename = __DIR__ . '/../' . basename($filename);
        }
        
        // Выводим PDF
        $this->pdf->Output($filename, $mode);
    }
}
