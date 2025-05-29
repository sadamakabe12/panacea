# Система современных tooltip для медицинского сайта

Этот документ описывает, как использовать систему всплывающих подсказок (tooltips) на страницах вашего медицинского сайта.

## Описание

Система tooltip представляет собой модульный JavaScript компонент, который обеспечивает:

- Профессиональное отображение всплывающих подсказок с плавными анимациями
- Корректную работу при наведении курсора на подсказки
- Умное позиционирование с учетом размеров экрана и границ страницы
- Легкую интеграцию в любые страницы сайта

## Подключение на страницу

### 1. Подключите CSS стили

В вашем `<head>` добавьте ссылку на стили:

```html
<link rel="stylesheet" href="../css/tooltip.css">
```

### 2. Добавьте HTML структуру для tooltip

```html
<div class="tooltip-container">
    <button class="btn-primary-soft btn button-icon">Открыть подсказку</button>
    <div class="doctor-tooltip position-top">
        <div class="tooltip-header">Заголовок подсказки</div>
        <div class="tooltip-content">
            <div class="tooltip-row tooltip-name">
                <span class="tooltip-label">Метка:</span>
                <span class="tooltip-value">Значение</span>
            </div>
            <!-- Другие строки с данными -->
        </div>
    </div>
</div>
```

### 3. Подключите JavaScript

Перед закрывающим тегом `</body>` добавьте:

```html
<script src="../js/tooltip.js"></script>
<script>
    // Инициализируем tooltip систему для текущей страницы
    initTooltipSystem();
</script>
```

## Дополнительные настройки

Вы можете настроить селекторы для различных элементов tooltip:

```javascript
initTooltipSystem(
    '.my-tooltip-container',  // Селектор контейнера
    '.my-tooltip-class',      // Селектор tooltip
    '.my-button-class'        // Селектор кнопки
);
```

## Поддерживаемые классы стилей

- `.tooltip-container` - Контейнер для tooltip
- `.doctor-tooltip` - Сам tooltip
- `.position-top` - Tooltip отображается сверху кнопки
- `.position-bottom` - Tooltip отображается снизу кнопки
- `.tooltip-header` - Заголовок tooltip
- `.tooltip-content` - Содержимое tooltip
- `.tooltip-row` - Строка с данными
- `.tooltip-label` - Метка в строке данных
- `.tooltip-value` - Значение в строке данных

## Примеры использования

### Простой tooltip на кнопке

```html
<div class="tooltip-container">
    <button class="btn-primary">Показать информацию</button>
    <div class="doctor-tooltip">
        <div class="tooltip-header">Информация</div>
        <div class="tooltip-content">
            <p>Это простой tooltip с информацией</p>
        </div>
    </div>
</div>
```

### Tooltip с данными пациента

```html
<div class="tooltip-container">
    <button class="btn-primary">Данные пациента</button>
    <div class="doctor-tooltip">
        <div class="tooltip-header">Информация о пациенте</div>
        <div class="tooltip-content">
            <div class="tooltip-row tooltip-name">
                <span class="tooltip-label">Имя:</span>
                <span class="tooltip-value">Иванов Иван Иванович</span>
            </div>
            <div class="tooltip-row tooltip-email">
                <span class="tooltip-label">Email:</span>
                <span class="tooltip-value">ivanov@example.com</span>
            </div>
            <div class="tooltip-row tooltip-phone">
                <span class="tooltip-label">Телефон:</span>
                <span class="tooltip-value">+7 (999) 123-45-67</span>
            </div>
        </div>
    </div>
</div>
```

## Примечания

- Система автоматически обрабатывает переходы курсора между кнопкой и tooltip
- При клике вне tooltip или нажатии ESC, tooltip скрывается
- Tooltip автоматически позиционируется в зависимости от положения на странице
