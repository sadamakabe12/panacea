/**
 * Modern Tooltip System для медицинского портала
 * Обеспечивает профессиональное отображение всплывающих подсказок с плавными анимациями
 * Решает проблему исчезновения при наведении курсора между кнопкой и подсказкой
 *
 * @version 1.0.1
 * @author Copilot
 * @description Оптимизированная система tooltip с поддержкой всплывающих подсказок
 */

/**
 * Инициализирует систему tooltip на странице
 * @param {string} containerSelector - CSS селектор для контейнеров с tooltip
 * @param {string} tooltipSelector - CSS селектор для tooltip элементов
 * @param {string} buttonSelector - CSS селектор для кнопок, вызывающих tooltip (по умолчанию - button)
 * @param {boolean} debug - Включить вывод отладочной информации в консоль
 */
window.initTooltipSystem = function(containerSelector = '.tooltip-container', tooltipSelector = '.doctor-tooltip', buttonSelector = 'button', debug = false) {
    // Функция для отладки
    function log(message) {
        if (debug) {
            console.log(`[Tooltip]: ${message}`);
        }
    }
    
    log(`Initializing tooltip system with selectors: ${containerSelector}, ${tooltipSelector}, ${buttonSelector}`);
    
    // Создаем функцию инициализации, которую будем вызывать когда DOM готов
    function initTooltips() {
        const tooltipContainers = document.querySelectorAll(containerSelector);
        let activeTooltip = null;
        let hideTimeout = null;
        
        // Глобальный трекер для определения, находится ли курсор над любым из связанных элементов
        let isHoveringRelatedElement = false;
    
        // Функция для скрытия tooltip с улучшенной проверкой
        function hideTooltip(tooltip, force = false) {
            if (!force && isHoveringRelatedElement) return;
            
            clearTimeout(hideTimeout);
            tooltip.classList.remove('active');
            tooltip.classList.add('hiding');
            
            setTimeout(() => {
                if (tooltip.classList.contains('hiding')) {
                    tooltip.classList.remove('hiding');
                    tooltip.style.visibility = 'hidden';
                    tooltip.style.opacity = '0';
                    tooltip.style.transform = tooltip.classList.contains('position-bottom') ? 
                        'translateY(-8px) scale(0.98)' : 'translateY(8px) scale(0.98)';
                    tooltip.style.pointerEvents = 'none';
                    
                    if (activeTooltip === tooltip) activeTooltip = null;
                }
            }, 200);
        }
    
        // Функция для позиционирования tooltip с улучшенным определением позиции
        function adjustTooltipPosition(container, tooltip) {
            const rect = container.getBoundingClientRect();
            const tooltipHeight = tooltip.offsetHeight;
            const tooltipWidth = tooltip.offsetWidth;
            const viewportHeight = window.innerHeight;
            const viewportWidth = window.innerWidth;
            
            // Определяем, достаточно ли места для отображения tooltip сверху
            const showBelow = rect.top < tooltipHeight + 20;
            
            // Центрируем tooltip относительно кнопки
            let leftPos = rect.left + (rect.width / 2) - (tooltipWidth / 2);
            
            // Корректируем позицию, если tooltip выходит за пределы экрана
            if (leftPos < 10) {
                // Если выходит слева, смещаем стрелку
                tooltip.style.setProperty('--arrow-left', `${rect.left + rect.width/2 - 10}px`);
                leftPos = 10;
            } else if (leftPos + tooltipWidth > viewportWidth - 10) {
                // Если выходит справа, смещаем tooltip и стрелку
                leftPos = viewportWidth - tooltipWidth - 10;
                tooltip.style.setProperty('--arrow-left', `${rect.left + rect.width/2 - leftPos}px`);
            } else {
                // Стрелка по центру по умолчанию
                tooltip.style.setProperty('--arrow-left', '50%');
            }
            
            // Применяем позиционирование
            tooltip.style.left = `${leftPos}px`;
            tooltip.style.right = 'auto';
            
            if (showBelow) {
                // Отображаем tooltip снизу с небольшим отступом
                tooltip.style.top = `${rect.bottom + 15}px`;
                tooltip.style.bottom = 'auto';
                tooltip.classList.add('position-bottom');
                tooltip.classList.remove('position-top');
            } else {
                // Отображаем tooltip сверху
                tooltip.style.top = 'auto';
                tooltip.style.bottom = `${viewportHeight - rect.top + 15}px`;
                tooltip.classList.add('position-top');
                tooltip.classList.remove('position-bottom');
            }
        }
    
        // Инициализация и улучшенные обработчики событий для каждого tooltip
        tooltipContainers.forEach((container, index) => {
            // Уникальный идентификатор для отслеживания связи между контейнером и tooltip
            const containerId = `tooltip-container-${index}`;
            container.dataset.containerId = containerId;
            
            const tooltip = container.querySelector(tooltipSelector);
            if (!tooltip) return; // Пропускаем, если tooltip не найден
            
            tooltip.dataset.containerId = containerId;
            const button = container.querySelector(buttonSelector);
            if (!button) return; // Пропускаем, если кнопка не найдена
            
            // Начальное состояние tooltip
            tooltip.style.visibility = 'hidden';
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'translateY(8px) scale(0.98)';
            tooltip.style.pointerEvents = 'none';
    
            // Улучшенная функция показа tooltip
            function showTooltip() {
                // Очищаем любые существующие таймеры
                if (hideTimeout) clearTimeout(hideTimeout);
                
                // Закрываем любой другой активный tooltip
                if (activeTooltip && activeTooltip !== tooltip) {
                    hideTooltip(activeTooltip, true);
                }
                
                // Активируем текущий tooltip
                tooltip.classList.add('active');
                tooltip.classList.remove('hiding');
                tooltip.style.visibility = 'visible';
                tooltip.style.opacity = '1';
                tooltip.style.transform = 'translateY(0) scale(1)';
                tooltip.style.pointerEvents = 'auto';
                activeTooltip = tooltip;
                
                // Перемещаем tooltip в body для корректного позиционирования
                if (tooltip.parentNode !== document.body) {
                    document.body.appendChild(tooltip);
                }
                
                // Настраиваем позицию
                adjustTooltipPosition(container, tooltip);
            }
            
            // Обработчик клика на кнопку
            button.addEventListener('click', e => {
                e.preventDefault();
                e.stopPropagation();
                
                if (tooltip.classList.contains('active')) {
                    hideTooltip(tooltip, true);
                } else {
                    showTooltip();
                }
            });
            
            // Улучшенная система отслеживания наведения курсора
            const trackHoverState = (element, isEntering) => {
                return () => {
                    isHoveringRelatedElement = isEntering;
                    
                    if (isEntering) {
                        // При наведении отменяем любые таймеры скрытия
                        if (hideTimeout) {
                            clearTimeout(hideTimeout);
                            hideTimeout = null;
                        }
                        
                        // Если tooltip активен и мы наводим на связанный элемент, не делаем ничего
                        if (activeTooltip === tooltip) {
                            tooltip.classList.remove('hiding');
                        }
                    } else {
                        // Устанавливаем таймер для скрытия tooltip при отведении курсора
                        // Достаточное время для перемещения с кнопки на tooltip или обратно
                        hideTimeout = setTimeout(() => {
                            // Перепроверяем состояние при скрытии
                            if (!isHoveringRelatedElement && activeTooltip === tooltip) {
                                hideTooltip(tooltip);
                            }
                        }, 300);
                    }
                };
            };
            
            // Отслеживаем наведение на контейнер
            container.addEventListener('mouseenter', trackHoverState(container, true));
            container.addEventListener('mouseleave', trackHoverState(container, false));
            
            // Отслеживаем наведение на кнопку
            button.addEventListener('mouseenter', trackHoverState(button, true));
            button.addEventListener('mouseleave', trackHoverState(button, false));
            
            // Отслеживаем наведение на tooltip
            tooltip.addEventListener('mouseenter', trackHoverState(tooltip, true));
            tooltip.addEventListener('mouseleave', trackHoverState(tooltip, false));
            
            // Эффект нажатия на кнопку для улучшения отзывчивости UI
            button.addEventListener('mousedown', () => button.style.transform = 'scale(0.97)');
            button.addEventListener('mouseup', () => button.style.transform = 'scale(1)');        });
        
        // Закрытие при клике вне tooltip
        document.addEventListener('click', e => {
            if (!activeTooltip) return;
            
            // Проверяем, был ли клик внутри tooltip или связанного контейнера
            let clickedInsideTooltip = activeTooltip.contains(e.target);
            
            if (!clickedInsideTooltip) {
                // Проверяем, был ли клик внутри связанного контейнера
                tooltipContainers.forEach(container => {
                    if (container.dataset.containerId === activeTooltip.dataset.containerId && 
                       (container.contains(e.target) || container.querySelector(buttonSelector)?.contains(e.target))) {
                        clickedInsideTooltip = true;
                    }
                });
            }
            
            // Если клик был вне tooltip и связанных элементов, скрываем tooltip
            if (!clickedInsideTooltip) {
                hideTooltip(activeTooltip, true);
            }
        });
        
        // Обновление позиции при изменении размера окна
        window.addEventListener('resize', () => {
            if (!activeTooltip) return;
            
            // Находим связанный контейнер для активного tooltip
            tooltipContainers.forEach(container => {
                if (container.dataset.containerId === activeTooltip.dataset.containerId) {
                    // Обновляем позицию tooltip при изменении размера окна
                    adjustTooltipPosition(container, activeTooltip);
                }
            });
        });
        
        // Добавляем обработчик клавиши Escape для закрытия tooltip
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && activeTooltip) {
                hideTooltip(activeTooltip, true);
            }
        });
        
        log('Tooltip system successfully initialized');    }
    
    // Определяем, когда инициализировать систему tooltip
    if (document.readyState === 'loading') {
        log('DOM still loading, waiting for DOMContentLoaded event');
        document.addEventListener('DOMContentLoaded', initTooltips);
    } else {
        log('DOM already loaded, initializing tooltips immediately');
        initTooltips();
    }
};

// Проверка на дублирование объявления функции глобально
if (typeof window !== 'undefined') {
    // Глобальная функция уже объявлена выше в начале файла как window.initTooltipSystem
    console.log('[Tooltip]: System ready to use. Call initTooltipSystem() to activate tooltips.');
}
