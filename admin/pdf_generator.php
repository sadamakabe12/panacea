<?php
// admin/pdf_generator.php - Класс для генерации PDF-файлов

class PDFGenerator {
    private $content = '';
    private $title = '';
    private $pageWidth = 210; // A4 в мм
    private $pageHeight = 297; // A4 в мм
    private $marginLeft = 20;
    private $marginRight = 20;
    private $marginTop = 30;
    private $marginBottom = 30;
    private $currentY = 0;
    private $pages = [];
    private $footer = '';
    
    /**
     * Конструктор класса
     * @param string $title Заголовок PDF-файла
     */
    public function __construct($title = 'Медицинская карта') {
        $this->title = $title;
        $this->startNewPage();
    }
    
    /**
     * Добавляет новую страницу
     */
    private function startNewPage() {
        $this->currentY = $this->marginTop;
        $this->content = "<div style=\"font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.5; position: relative; width: 100%;\">";
        
        // Добавляем заголовок
        $this->content .= "<div style=\"text-align: center; font-size: 16pt; font-weight: bold; margin-bottom: 20px;\">" . 
                          htmlspecialchars($this->title) . 
                          "</div>";
    }
    
    /**
     * Добавляет раздел с заголовком
     * @param string $title Заголовок раздела
     */
    public function addSection($title) {
        $this->content .= "<div style=\"font-size: 14pt; font-weight: bold; margin-top: 15px; margin-bottom: 10px; border-bottom: 1px solid #333;\">" . 
                          htmlspecialchars($title) . 
                          "</div>";
    }
    
    /**
     * Добавляет подраздел
     * @param string $title Заголовок подраздела
     */
    public function addSubsection($title) {
        $this->content .= "<div style=\"font-size: 12pt; font-weight: bold; margin-top: 10px; margin-bottom: 5px;\">" . 
                          htmlspecialchars($title) . 
                          "</div>";
    }
    
    /**
     * Добавляет абзац текста
     * @param string $text Текст абзаца
     */
    public function addParagraph($text) {
        $this->content .= "<div style=\"margin-bottom: 5px;\">" . 
                          nl2br(htmlspecialchars($text)) . 
                          "</div>";
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
     */
    public function output() {
        $this->content .= "</div>";
        $this->pages[] = $this->content;
        
        $html = "<!DOCTYPE html>
                <html>
                <head>
                    <meta charset=\"utf-8\">
                    <title>" . htmlspecialchars($this->title) . "</title>
                    <style>
                        @page {
                            size: A4;
                            margin: {$this->marginTop}mm {$this->marginRight}mm {$this->marginBottom}mm {$this->marginLeft}mm;
                        }
                        body {
                            font-family: Arial, sans-serif;
                            font-size: 11pt;
                            line-height: 1.5;
                        }
                        .footer {
                            position: fixed;
                            bottom: 10mm;
                            width: 100%;
                            text-align: center;
                            font-size: 9pt;
                            color: #777;
                        }
                        .page-break {
                            page-break-after: always;
                        }
                    </style>
                </head>
                <body>";
        
        // Добавляем содержимое страниц
        for ($i = 0; $i < count($this->pages); $i++) {
            $html .= $this->pages[$i];
            if ($i < count($this->pages) - 1) {
                $html .= "<div class=\"page-break\"></div>";
            }
        }
        
        // Добавляем футер
        if ($this->footer) {
            $html .= "<div class=\"footer\">" . htmlspecialchars($this->footer) . "</div>";
        }
        
        $html .= "</body></html>";
        
        // Подключаем HTML-to-PDF конвертер mPDF, если он установлен
        if (class_exists('\\Mpdf\\Mpdf')) {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => $this->marginLeft,
                'margin_right' => $this->marginRight,
                'margin_top' => $this->marginTop,
                'margin_bottom' => $this->marginBottom
            ]);
            $mpdf->WriteHTML($html);
            $mpdf->Output();
        } else {
            // Если mPDF не установлен, выводим HTML с соответствующими заголовками
            // для печати в браузере
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename="' . $this->title . '.pdf"');
            echo $html;
        }
    }
}
