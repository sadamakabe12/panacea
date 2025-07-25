# 4 ЭКСПЕРИМЕНТАЛЬНАЯ ЧАСТЬ
4.1 ТЕСТИРОВАНИЕ ПРИЛОЖЕНИЯ

Тестирование разработанной медицинской информационной системы "Панацея" произведено с помощью сквозного тестирования, позволившего проверить все сценарии вариантов использования МИС на корректность и отсутствие неожиданного поведения. Были протестированы как позитивные, так и негативные сценарии каждого варианта использования.

Сквозное тестирование является комплексным методом, который проверяет работу системы от начала до конца, имитируя реальные пользовательские сценарии и проверяя интеграцию всех компонентов системы. Данный подход позволяет выявить проблемы взаимодействия между различными модулями системы, проверить корректность обработки данных на всех этапах и убедиться в соответствии функциональности требованиям пользователей.

В ходе тестирования проверялись функциональные возможности для трех основных ролей пользователей: пациентов, врачей и администраторов. Каждый тест включает детальное описание шагов выполнения, ожидаемых результатов и альтернативных сценариев с указанием мест для размещения соответствующих иллюстраций.
4.1.1 Авторизация в системе
Цель теста: проверить корректность аутентификации и авторизации пользователя в медицинской информационной системе.
Шаги:
1) запустить приложение в веб-браузере;
2) ввести корректные почту и пароль существующего пользователя в соответствующие поля формы авторизации;
3) нажать кнопку «Войти».
Ожидаемый результат: система успешно аутентифицирует пользователя и перенаправляет его на главную страницу соответствующей роли (панель пациента, врача или администратора).
Другие возможные исходы: при вводе неверных учётных данных система отображает сообщение об ошибке "Неверная почта или пароль" и остается на странице авторизации (см. рисунок 4.1).
Варианты проверки:
1) авторизация с ролью «Пациент»;
2) авторизация с ролью «Врач»;
3) авторизация с ролью «Администратор»;
4) попытка авторизации с несуществующими учётными данными;
5) попытка авторизации с пустыми полями.
*Рисунок 4.1 – Сообщение об ошибке при неверных учётных данных*
4.1.2 Выход из учетной записи
Цель теста: проверить корректность завершения пользовательской сессии и выхода из системы.
Шаги:
1) выполнить авторизацию под любой учётной записью;
2) перейти на любую страницу личного кабинета;
3) нажать кнопку «Выйти» в интерфейсе системы.
Ожидаемый результат: система завершает пользовательскую сессию и перенаправляет пользователя на страницу авторизации.
Другие возможные исходы: в случае технических проблем может отображаться ошибка сервера (см. рисунок 4.2).
Варианты проверки:
1) выход из системы для роли «Пациент»;
2) выход из системы для роли «Врач»;
3) выход из системы для роли «Администратор»;
4) проверка недоступности защищённых страниц после выхода.
*Рисунок 4.2 – Страница авторизации после выхода из системы*
4.1.3 Регистрация нового пациента
Цель теста: проверить процесс создания новой учётной записи пациента в системе.
Шаги:
1) открыть главную страницу медицинской системы;
2) нажать ссылку «Зарегистрироваться»;
3) заполнить все обязательные поля формы регистрации (ФИО, почта, пароль, дата рождения, телефон);
4) нажать кнопку «Создать аккаунт».
Ожидаемый результат: система создает новую учётную запись пациента, сохраняет данные в базе данных и перенаправляет пользователя на страницу успешной регистрации или панель пациента.
Другие возможные исходы: при использовании уже существующей почты система отображает сообщение "Почта уже зарегистрирована в системе" (см. рисунок 4.3). При некорректном заполнении полей отображаются соответствующие сообщения валидации (см. рисунок 4.4).
Варианты проверки:
1) регистрация с корректными данными;
2) попытка регистрации с уже используемой почтой;
3) регистрация с некорректным форматом почты;
4) регистрация с пустыми обязательными полями;
5) регистрация с некорректной датой рождения.
*Рисунок 4.3 – Сообщение об ошибке при дублировании почты*
*Рисунок 4.4 – Сообщения валидации полей формы*
4.1.4 Восстановление пароля.
Цель теста: проверить корректность восстановления забытого пароля.
Шаги:
1) открыть страницу авторизации;
2) нажать на ссылку «Забыли пароль?»;
3) ввести почту в поле формы восстановления;
4) нажать кнопку «Восстановить пароль»;
5) перейти по ссылке из полученного письма;
6) ввести новый пароль и подтверждение;
7) нажать кнопку «Сохранить новый пароль».
Ожидаемый результат: система отправит ссылку для сброса пароля на почту, обновит пароль в БД и позволит войти с новым паролем.
Другие возможные исходы: если ввести несуществующую почту, система выведет сообщение об ошибке (рис. 4.4).
Варианты проверки:
1) ввести почту незарегистрированного пользователя.
*Рисунок 4.4 – Сообщение об ошибке при восстановлении пароля для несуществующей почты*
Другие возможные исходы: если заполнить не все поля формы или заполнить неверно, то система выведет сообщение с описанием ошибки (рис. 4.2-4.3).
4.1.5 Просмотр медицинских записей пациентом
Цель теста: проверить возможность пациента просматривать свои медицинские записи.
Шаги:
1) авторизоваться под учётной записью пациента;
2) перейти в раздел «Медицинские записи»;
3) просмотреть список доступных записей.
Ожидаемый результат: система отображает список всех медицинских записей пациента с указанием даты приёма, врача, диагноза и возможностью просмотра детальной информации.
Другие возможные исходы: если у пациента нет медицинских записей, система отображает соответствующее сообщение (см. рисунок 4.6).
Варианты проверки:
1) просмотр записей пациента с историей болезни;
2) просмотр для нового пациента без медицинских записей;
3) экспорт медицинской записи в PDF формат.
*Рисунок 4.6 – Список медицинских записей пациента*
4.1.6 Поиск врачей
Цель теста: проверить функциональность поиска врачей по различным критериям.
Шаги:
1) авторизоваться под учётной записью пациента;
2) перейти в раздел «Врачи»;
3) ввести критерии поиска (ФИО врача или специальность);
4) нажать кнопку «Найти».
Ожидаемый результат: система отображает отфильтрованный список врачей, соответствующих критериям поиска, с указанием специальностей и возможности записи на приём.
Другие возможные исходы: при отсутствии врачей по заданным критериям система отображает сообщение "Врачи не найдены" (см. рисунок 4.7).
Варианты проверки:
1) поиск по ФИО врача;
2) поиск по специальности;
3) поиск с некорректными данными;
4) просмотр всех врачей без фильтрации.
*Рисунок 4.7 – Результаты поиска врачей*
4.1.7 Запись на приём - выбор даты и врача
Цель теста: проверить процесс записи пациента на приём к врачу.
Шаги:
1) авторизоваться под учётной записью пациента;
2) перейти в раздел «Запись на приём»;
3) выбрать дату приёма в календаре;
4) выбрать специальность (опционально);
5) просмотреть список доступных врачей и свободных временных слотов.
Ожидаемый результат: система отображает доступных врачей выбранной специальности на указанную дату с временными слотами для записи.
Другие возможные исходы: если на выбранную дату нет доступных врачей, система предлагает выбрать другую дату (см. рисунок 4.8).
Варианты проверки:
1) выбор даты с доступными врачами;
2) выбор даты без доступных врачей;
3) фильтрация по специальности;
4) просмотр расписания на несколько дней вперёд.
*Рисунок 4.8 – Интерфейс выбора даты и врача для записи*
4.1.8 Подтверждение записи на приём
Цель теста: проверить финальный этап записи пациента на приём.
Шаги:
1) выполнить выбор врача и временного слота согласно тесту 4.1.7;
2) нажать кнопку «Записаться» на выбранное время;
3) заполнить причину обращения (опционально);
4) подтвердить запись нажатием кнопки «Подтвердить запись».
Ожидаемый результат: система создаёт запись в базе данных, генерирует уникальный номер записи и отображает подтверждение успешной записи с деталями приёма.
Другие возможные исходы: при попытке записи на уже занятое время система отображает ошибку "Время уже занято" (см. рисунок 4.9).
Варианты проверки:
1) успешное подтверждение записи;
2) попытка записи на занятое время;
3) отмена в процессе подтверждения;
4) запись с указанием причины обращения.
*Рисунок 4.9 – Подтверждение успешной записи на приём*
4.1.9 Просмотр истории записей
Цель теста: проверить возможность пациента просматривать историю своих записей к врачам.
Шаги:
1) авторизоваться под учётной записью пациента;
2) перейти в раздел «Мои записи»;
3) просмотреть список всех записей с фильтрацией по дате и статусу.
Ожидаемый результат: система отображает полный список записей пациента с указанием даты, времени, врача, статуса записи и возможностью отмены будущих записей.
Другие возможные исходы: для нового пациента без записей отображается соответствующее сообщение (см. рисунок 4.10).
Варианты проверки:
1) просмотр истории пациента с множественными записями;
2) фильтрация записей по дате;
3) отображение различных статусов записей (подтверждена, завершена, отменена);
4) просмотр для пациента без записей.
*Рисунок 4.10 – История записей пациента*
4.1.10 Отмена записи на приём
Цель теста: проверить возможность пациента отменять свои записи на приём.
Шаги:
1) авторизоваться под учётной записью пациента;
2) перейти в раздел «Мои записи»;
3) выбрать активную запись;
4) нажать кнопку «Отменить запись»;
5) подтвердить отмену в диалоговом окне.
Ожидаемый результат: система изменяет статус записи на "Отменена пациентом", освобождает временной слот для других пациентов и отображает подтверждение отмены.
Другие возможные исходы: при попытке отмены записи менее чем за 24 часа система может запросить дополнительное подтверждение (см. рисунок 4.11).
Варианты проверки:
1) отмена записи заблаговременно;
2) отмена записи за несколько часов до приёма;
3) попытка отмены завершённой записи;
4) отмена с последующей проверкой освобождения слота.
*Рисунок 4.11 – Подтверждение отмены записи*
4.1.11 Редактирование профиля пациента
Цель теста: проверить возможность пациента изменять свои персональные данные.
Шаги:
1) авторизоваться под учётной записью пациента;
2) перейти в раздел «Профиль» или «Настройки»;
3) изменить данные в доступных полях (телефон, адрес, дополнительная информация);
4) нажать кнопку «Сохранить изменения».
Ожидаемый результат: система обновляет данные пациента в базе данных и отображает подтверждение успешного сохранения изменений.
Другие возможные исходы: при попытке изменить почту на уже используемую система отображает ошибку валидации (см. рисунок 4.12).
Варианты проверки:
1) успешное обновление контактной информации;
2) попытка изменения почты на существующую;
3) обновление с некорректными данными;
4) отмена изменений без сохранения.
*Рисунок 4.12 – Интерфейс редактирования профиля пациента*
4.1.12 Поиск врачей по специальности
Цель теста: проверить специализированный поиск врачей по медицинским специальностям.
Шаги:
1) авторизоваться под учётной записью пациента;
2) перейти в раздел «Врачи»;
3) выбрать специальность из выпадающего списка;
4) нажать кнопку «Найти врачей».
Ожидаемый результат: система отображает список врачей выбранной специальности с указанием их квалификации, опыта работы и доступности для записи.
Другие возможные исходы: если врачей выбранной специальности нет, система отображает соответствующее сообщение (см. рисунок 4.13).
Варианты проверки:
1) поиск по популярным специальностям (терапевт, кардиолог);
2) поиск по редким специальностям;
3) просмотр информации о враче;
4) переход к записи на приём от карточки врача.
*Рисунок 4.13 – Список врачей по специальности*
4.1.13 Просмотр детальной информации о враче
Цель теста: проверить возможность просмотра подробной информации о враче перед записью.
Шаги:
1) авторизоваться под учётной записью пациента;
2) найти врача любым способом (поиск или просмотр списка);
3) нажать на ФИО врача или кнопку «Подробнее»;
4) просмотреть детальную информацию.
Ожидаемый результат: система отображает полную информацию о враче включая специальности, образование, опыт работы, расписание и отзывы пациентов (если доступны).
Другие возможные исходы: для врачей с неполной информацией некоторые поля могут быть не заполнены (см. рисунок 4.14).
Варианты проверки:
1) просмотр информации врача с полным профилем;
2) просмотр информации врача с минимальными данными;
3) переход к записи на приём из профиля врача;
4) возврат к списку врачей.
*Рисунок 4.14 – Детальная информация о враче*
4.1.14 Экспорт медицинской записи в PDF
Цель теста: проверить возможность пациента экспортировать свои медицинские записи в PDF формат.
Шаги:
1) авторизоваться под учётной записью пациента;
2) перейти в раздел «Медицинские записи»;
3) выбрать конкретную медицинскую запись;
4) нажать кнопку «Экспорт в PDF» или «Скачать».
Ожидаемый результат: система генерирует PDF документ с полной медицинской информацией из записи и предлагает его для скачивания.
Другие возможные исходы: при технических проблемах может отображаться ошибка генерации документа (см. рисунок 4.15).
Варианты проверки:
1) экспорт записи с полной медицинской информацией;
2) экспорт записи с минимальными данными;
3) проверка содержимого сгенерированного PDF;
4) экспорт нескольких записей подряд.
*Рисунок 4.15 – Процесс экспорта медицинской записи*
4.1.15 Просмотр медицинских записей пациента
Цель теста: проверить корректность отображения медицинских записей пациента.
Шаги:
1) авторизоваться под учётной записью пациента;
2) перейти в раздел «Медицинские записи»;
3) просмотреть список всех медицинских записей.
Ожидаемый результат: система выведет список всех медицинских записей пациента с указанием даты, врача, диагноза и назначений.
Другие возможные исходы: система может отображать записи с ограниченной детализацией в зависимости от настроек приватности (см. рисунок 4.16).
Варианты проверки:
1) просмотр завершённых медицинских записей;
2) просмотр записей в процессе создания;
3) сортировка записей по дате;
4) фильтрация по врачу или диагнозу.
*Рисунок 4.16 – Полный список медицинских записей пациента*
4.1.16 Просмотр записей на текущий день
Цель теста: проверить возможность врача просматривать свои записи на текущий день.
Шаги:
1) авторизоваться под учётной записью врача;
2) перейти в раздел «Приёмы» или на главную страницу;
3) просмотреть список записей на сегодня.
Ожидаемый результат: система отображает список всех записей врача на текущий день с указанием времени, ФИО пациента, статуса записи и причины обращения.
Другие возможные исходы: если на день нет записей, система отображает соответствующее сообщение (см. рисунок 4.17).
Варианты проверки:
1) просмотр дня с множественными записями;
2) просмотр дня без записей;
3) обновление статусов записей в реальном времени;
4) переход к созданию медицинской записи.
*Рисунок 4.17 – Список записей врача на текущий день*
4.1.17 Отметка прихода пациента
Цель теста: проверить возможность врача отмечать приход пациентов на приём.
Шаги:
1) авторизоваться под учётной записью врача;
2) открыть список записей на текущий день;
3) найти запись пациента;
4) нажать кнопку «Отметить приход» или изменить статус на «Пришёл».
Ожидаемый результат: система обновляет статус записи на "Пациент пришёл", отображает время прихода и делает доступными дополнительные действия (создание медицинской записи).
Другие возможные исходы: статус может автоматически изменяться в зависимости от времени (см. рисунок 4.18).
Варианты проверки:
1) отметка прихода в назначенное время;
2) отметка прихода с опозданием;
3) отметка прихода раньше времени;
4) массовая отметка нескольких пациентов.
*Рисунок 4.18 – Интерфейс отметки прихода пациента*
4.1.18 Отметка неявки пациента
Цель теста: проверить возможность врача отмечать неявку пациентов на приём.
Шаги:
1) авторизоваться под учётной записью врача;
2) открыть список записей на текущий день;
3) найти запись пациента, который не явился;
4) нажать кнопку «Отметить неявку»;
5) подтвердить действие в диалоговом окне.
Ожидаемый результат: система изменяет статус записи на "Не явился", освобождает временной слот и обновляет статистику неявок пациента.
Другие возможные исходы: система может предложить переназначить запись или отправить уведомление пациенту (см. рисунок 4.19).
Варианты проверки:
1) отметка неявки после истечения времени записи;
2) отметка неявки с добавлением комментария;
3) массовая обработка неявок;
4) отмена ошибочной отметки неявки.
*Рисунок 4.19 – Подтверждение отметки неявки пациента*
4.1.19 Создание медицинской записи
Цель теста: проверить процесс создания новой медицинской записи врачом.
Шаги:
1) авторизоваться под учётной записью врача;
2) выбрать пациента из списка записей или найти через поиск;
3) нажать кнопку «Создать медицинскую запись»;
4) заполнить форму записи (жалобы, анамнез, осмотр, диагноз, назначения);
5) сохранить запись.
Ожидаемый результат: система создаёт новую медицинскую запись в базе данных, привязывает её к пациенту и врачу, обновляет статус приёма на "Завершён".
Другие возможные исходы: запись может быть сохранена как черновик для последующего редактирования (см. рисунок 4.20).
Варианты проверки:
1) создание полной медицинской записи;
2) сохранение записи как черновика;
3) создание записи с назначениями и анализами;
4) создание записи с прикреплением файлов.
*Рисунок 4.20 – Форма создания медицинской записи*
4.1.20 Редактирование медицинской записи
Цель теста: проверить возможность врача редактировать существующие медицинские записи.
Шаги:
1) авторизоваться под учётной записью врача;
2) перейти в раздел «Медицинские записи»;
3) найти и выбрать запись для редактирования;
4) внести изменения в необходимые поля;
5) сохранить изменения.
Ожидаемый результат: система обновляет медицинскую запись, сохраняет изменения в базе данных и фиксирует информацию о редактировании (дата, время, автор изменений).
Другие возможные исходы: для финализированных записей может потребоваться специальное разрешение на редактирование (см. рисунок 4.21).
Варианты проверки:
1) редактирование черновика записи;
2) редактирование завершённой записи;
3) добавление новых диагнозов и назначений;
4) исправление ошибок в существующих данных.
*Рисунок 4.21 – Интерфейс редактирования медицинской записи*
4.1.21 Добавление результатов анализов
Цель теста: проверить функциональность добавления результатов лабораторных исследований.
Шаги:
1) авторизоваться под учётной записью врача;
2) открыть медицинскую запись пациента;
3) перейти в раздел «Лабораторные исследования»;
4) нажать кнопку «Добавить результат»;
5) заполнить данные анализа (название, результаты, референсные значения, дата);
6) сохранить информацию.
Ожидаемый результат: система добавляет результаты анализов к медицинской записи пациента с возможностью последующего просмотра и анализа динамики показателей.
Другие возможные исходы: система может автоматически выделять показатели, выходящие за пределы нормы (см. рисунок 4.22).
Варианты проверки:
1) добавление результатов общего анализа крови;
2) добавление биохимических показателей;
3) загрузка файлов с результатами;
4) редактирование ранее введённых результатов.
*Рисунок 4.22 – Форма добавления результатов анализов*
4.1.22 Просмотр пациентов врача
Цель теста: проверить возможность врача просматривать список своих пациентов.
Шаги:
1) авторизоваться под учётной записью врача;
2) перейти в раздел «Пациенты» или «База пациентов»;
3) просмотреть список пациентов с возможностью поиска и фильтрации.
Ожидаемый результат: система отображает список всех пациентов, которые когда-либо записывались к данному врачу, с базовой информацией и историей обращений.
Другие возможные исходы: для нового врача список может быть пустым (см. рисунок 4.23).
Варианты проверки:
1) просмотр полного списка пациентов;
2) поиск пациента по ФИО;
3) фильтрация по дате последнего обращения;
4) переход к медицинской карте пациента.
*Рисунок 4.23 – База пациентов врача*
4.1.23 Управление расписанием врача
Цель теста: проверить возможность врача управлять своим рабочим расписанием.
Шаги:
1) авторизоваться под учётной записью врача;
2) перейти в раздел «Моё расписание»;
3) просмотреть текущее расписание;
4) внести изменения (добавить/удалить рабочие часы, отметить выходные);
5) сохранить изменения.
Ожидаемый результат: система обновляет расписание врача, делает новые слоты доступными для записи пациентов и уведомляет о конфликтах с существующими записями.
Другие возможные исходы: при конфликтах с записями система предупреждает врача и предлагает варианты решения (см. рисунок 4.24).
Варианты проверки:
1) добавление новых рабочих часов;
2) удаление временных слотов без записей;
3) создание исключений (отпуск, больничный);
4) массовое обновление расписания на неделю.
*Рисунок 4.24 – Интерфейс управления расписанием врача*
4.1.24 Экспорт медицинской записи в PDF
Цель теста: проверить возможность врача экспортировать медицинские записи в PDF формат.
Шаги:
1) авторизоваться под учётной записью врача;
2) открыть медицинскую запись пациента;
3) нажать кнопку «Экспорт в PDF»;
4) выбрать параметры экспорта (полная запись или выборочные разделы);
5) сгенерировать и скачать документ.
Ожидаемый результат: система создаёт профессионально оформленный PDF документ с медицинской информацией, подходящий для передачи другим специалистам или архивирования.
Другие возможные исходы: PDF может содержать водяные знаки или подпись врача для подтверждения подлинности (см. рисунок 4.25).
Варианты проверки:
1) экспорт полной медицинской записи;
2) экспорт только диагнозов и назначений;
3) экспорт с результатами анализов;
4) массовый экспорт нескольких записей.
*Рисунок 4.25 – Настройки экспорта медицинской записи*
4.1.25 Настройки профиля врача
Цель теста: проверить возможность врача изменять свои профессиональные и контактные данные.
Шаги:
1) авторизоваться под учётной записью врача;
2) перейти в раздел «Профиль» или «Настройки»;
3) изменить доступные данные (телефон, почта, специальности, описание);
4) сохранить изменения.
Ожидаемый результат: система обновляет профиль врача, и изменения отражаются в поиске врачей для пациентов и административных интерфейсах.
Другие возможные исходы: некоторые поля могут требовать подтверждения администратора (см. рисунок 4.26).
Варианты проверки:
1) обновление контактной информации;
2) изменение описания деятельности;
3) добавление дополнительных специальностей;
4) изменение пароля доступа.
*Рисунок 4.26 – Профиль и настройки врача*
4.1.26 Просмотр панели управления администратора
Цель теста: проверить отображение общей статистики системы в панели администратора.
Шаги:
1) авторизоваться под учётной записью администратора;
2) открыть главную страницу административной панели;
3) просмотреть представленную статистику и аналитику.
Ожидаемый результат: система отображает ключевые показатели: общее количество врачей, пациентов, записей, медицинских записей, а также статистику за текущий период.
Другие возможные исходы: при первом запуске системы статистика может быть минимальной (см. рисунок 4.27).
Варианты проверки:
1) просмотр статистики в заполненной системе;
2) анализ динамики показателей;
3) проверка актуальности данных в реальном времени;
4) экспорт статистических отчётов.
*Рисунок 4.27 – Панель управления администратора*
4.1.27 Добавление нового врача
Цель теста: проверить процесс создания учётной записи врача администратором.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Врачи»;
3) нажать кнопку «Добавить врача»;
4) заполнить форму (ФИО, почту, телефон, пароль, специальности);
5) сохранить данные нового врача.
Ожидаемый результат: система создаёт новую учётную запись врача, отправляет уведомление на почту и добавляет врача в общую базу с возможностью назначения расписания.
Другие возможные исходы: при использовании существующей почты система отображает ошибку валидации (см. рисунок 4.28).
Варианты проверки:
1) добавление врача с уникальными данными;
2) попытка добавления с дублирующейся почтой;
3) добавление врача с несколькими специальностями;
4) массовое добавление врачей через импорт.
*Рисунок 4.28 – Форма добавления нового врача*
4.1.28 Редактирование данных врача
Цель теста: проверить возможность администратора изменять информацию о врачах.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Врачи»;
3) выбрать врача из списка;
4) нажать кнопку «Редактировать»;
5) внести изменения в данные врача;
6) сохранить изменения.
Ожидаемый результат: система обновляет информацию о враче, уведомляет врача об изменениях (если необходимо) и отражает обновления во всех связанных интерфейсах.
Другие возможные исходы: некоторые изменения могут требовать подтверждения самого врача (см. рисунок 4.29).
Варианты проверки:
1) изменение контактной информации;
2) добавление/удаление специальностей;
3) временная деактивация учётной записи;
4) сброс пароля врача.
*Рисунок 4.29 – Редактирование данных врача*
4.1.29 Удаление врача
Цель теста: проверить процесс удаления учётной записи врача из системы.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Врачи»;
3) выбрать врача для удаления;
4) нажать кнопку «Удалить»;
5) подтвердить удаление в диалоговом окне;
6) обработать связанные данные (записи, расписание).
Ожидаемый результат: система безопасно удаляет учётную запись врача, архивирует связанные медицинские записи и уведомляет пациентов с активными записями о необходимости их переназначения.
Другие возможные исходы: система может предложить деактивацию вместо полного удаления для сохранения медицинской истории (см. рисунок 4.30).
Варианты проверки:
1) удаление врача без активных записей;
2) удаление врача с активными записями;
3) обработка медицинских записей при удалении;
4) восстановление ошибочно удалённого врача.
*Рисунок 4.30 – Подтверждение удаления врача*
4.1.30 Добавление нового пациента
Цель теста: проверить возможность администратора создавать учётные записи пациентов.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Пациенты»;
3) нажать кнопку «Добавить пациента»;
4) заполнить регистрационную форму;
5) создать учётную запись пациента.
Ожидаемый результат: система создаёт новую учётную запись пациента, генерирует временный пароль и отправляет учётные данные на указанную почту.
Другие возможные исходы: пациент может быть добавлен без почты для последующей самостоятельной активации (см. рисунок 4.31).
Варианты проверки:
1) создание пациента с полными данными;
2) создание пациента с минимальной информацией;
3) массовый импорт пациентов;
4) создание учётной записи для ребёнка.
*Рисунок 4.31 – Форма добавления нового пациента*
4.1.31 Поиск и просмотр пациентов
Цель теста: проверить функциональность поиска и управления базой пациентов.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Пациенты»;
3) использовать различные критерии поиска (ФИО, почту, телефон);
4) применить фильтры для сортировки результатов;
5) просмотреть детальную информацию о пациентах.
Ожидаемый результат: система обеспечивает быстрый и точный поиск пациентов с возможностью просмотра их медицинской истории, активных записей и контактной информации.
Другие возможные исходы: для большой базы пациентов результаты могут отображаться с пагинацией (см. рисунок 4.32).
Варианты проверки:
1) поиск по точному совпадению ФИО;
2) поиск по частичному совпадению;
3) фильтрация по дате регистрации;
4) экспорт списка пациентов.
*Рисунок 4.32 – Интерфейс поиска и просмотра пациентов*
4.1.32 Управление расписанием врачей администратором
Цель теста: проверить корректность управления расписанием врачей клиники.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Управление расписанием»;
3) выбрать врача из списка;
4) создать или изменить расписание (рабочие часы, перерывы, длительность приёмов);
5) нажать кнопку «Сохранить расписание».
Ожидаемый результат: система обновляет расписание выбранного врача, синхронизирует изменения с системой записи пациентов и уведомляет врача об изменениях.
Другие возможные исходы: при конфликтах с существующими записями система предлагает варианты их разрешения (см. рисунок 4.33).
Варианты проверки:
1) создание стандартного еженедельного расписания;
2) добавление исключений и нерабочих дней;
3) массовое применение расписания для группы врачей;
4) координация расписаний разных специальностей.
*Рисунок 4.33 – Управление расписанием врачей*
4.1.33 Создание исключений в расписании
Цель теста: проверить функциональность создания исключений в рабочем расписании врачей.
Шаги:
1) авторизоваться под учётной записью администратора;
2) выбрать врача и открыть его расписание;
3) нажать кнопку «Добавить исключение»;
4) указать дату, время и причину исключения (отпуск, больничный, конференция);
5) сохранить исключение.
Ожидаемый результат: система блокирует указанные временные слоты, отменяет существующие записи с уведомлением пациентов и предлагает альтернативные варианты записи.
Другие возможные исходы: пациенты с записями в исключённое время получают автоматические уведомления о переносе (см. рисунок 4.34).
Варианты проверки:
1) создание однодневного исключения;
2) создание длительного исключения (отпуск);
3) экстренное исключение с немедленным уведомлением;
4) повторяющиеся исключения (еженедельные конференции).
*Рисунок 4.34 – Создание исключений в расписании*
4.1.34 Просмотр всех записей на приём
Цель теста: проверить возможность администратора просматривать все записи в системе.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Все записи»;
3) применить фильтры по дате, врачу, статусу;
4) просмотреть детальную информацию о записях;
5) выполнить операции управления записями.
Ожидаемый результат: система отображает полный список записей с возможностью фильтрации, поиска и выполнения административных действий (отмена, перенос, просмотр).
Другие возможные исходы: большой объём данных может требовать пагинации и дополнительных инструментов навигации (см. рисунок 4.35).
Варианты проверки:
1) просмотр записей за определённый период;
2) фильтрация по статусу записи;
3) поиск записей конкретного пациента;
4) экспорт отчёта по записям.
*Рисунок 4.35 – Общий список записей на приём*
4.1.35 Просмотр журнала аудита
Цель теста: проверить функциональность просмотра всех действий пользователей в системе.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Журнал аудита»;
3) применить фильтры по дате, пользователю, типу действия;
4) просмотреть детальную информацию о событиях;
5) экспортировать данные аудита при необходимости.
Ожидаемый результат: система отображает полную историю действий с указанием времени, пользователя, типа операции и затронутых данных для обеспечения безопасности и соответствия требованиям.
Другие возможные исходы: чувствительные операции могут быть дополнительно выделены или требовать специальных разрешений для просмотра (см. рисунок 4.37).
Варианты проверки:
1) просмотр действий конкретного пользователя;
2) анализ подозрительной активности;
3) отслеживание изменений медицинских записей;
4) контроль доступа к конфиденциальным данным.
*Рисунок 4.37 – Журнал аудита системы*
4.1.36 Управление специальностями
Цель теста: проверить функциональность управления медицинскими специальностями в системе.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Специальности»;
3) просмотреть список существующих специальностей;
4) добавить новую специальность или отредактировать существующую;
5) назначить специальности врачам.
Ожидаемый результат: система позволяет гибко управлять справочником специальностей, назначать их врачам и использовать для фильтрации при поиске и записи пациентов.
Другие возможные исходы: удаление специальности может потребовать переназначения врачей на другие специальности (см. рисунок 4.38).
Варианты проверки:
1) добавление новой специальности;
2) редактирование описания специальности;
3) назначение специальности врачу;
4) деактивация неактуальной специальности.
*Рисунок 4.38 – Управление медицинскими специальностями*
4.1.37 Создание детального расписания врача
Цель теста: проверить возможность создания индивидуализированного расписания для врача.
Шаги:
1) авторизоваться под учётной записью администратора;
2) выбрать врача и перейти к управлению его расписанием;
3) открыть календарное представление на неделю;
4) создать индивидуальные временные слоты для каждого рабочего дня;
5) настроить параметры приёма (длительность, перерывы);
6) сохранить детальное расписание.
Ожидаемый результат: система создаёт персонализированное расписание врача с учётом особенностей его работы, делает новые слоты доступными для записи пациентов.
Другие возможные исходы: система может предупреждать о конфликтах с общими настройками клиники (см. рисунок 4.39).
Варианты проверки:
1) создание расписания с переменной длительностью приёмов;
2) настройка специальных слотов для определённых процедур;
3) координация расписания с работой других врачей;
4) создание шаблона для копирования на другие недели.
*Рисунок 4.39 – Детальное расписание врача*
4.1.38 Навигация по неделям в расписании
Цель теста: проверить удобство навигации по календарю при планировании расписания.
Шаги:
1) авторизоваться под учётной записью администратора;
2) открыть расписание любого врача;
3) использовать элементы навигации для перехода между неделями;
4) просмотреть расписание на будущие периоды;
5) вернуться к текущей неделе.
Ожидаемый результат: система обеспечивает интуитивную навигацию по календарю с отображением количества записей, свободных слотов и возможностью быстрого перехода к нужному периоду.
Другие возможные исходы: система может ограничивать планирование на слишком отдалённые периоды (см. рисунок 4.40).
Варианты проверки:
1) переход к следующей/предыдущей неделе;
2) быстрый переход к конкретной дате;
3) просмотр статистики загрузки по неделям;
4) планирование расписания на месяц вперёд.
*Рисунок 4.40 – Навигация по календарю расписания*
4.1.39 Управление уведомлениями о записях
Цель теста: проверить настройку системы автоматических уведомлений.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Настройки уведомлений»;
3) настроить типы уведомлений почту или выключить;
4) установить временные интервалы для отправки;
5) настроить шаблоны сообщений;
6) активировать систему уведомлений.
Ожидаемый результат: система автоматически отправляет уведомления пациентам о предстоящих записях согласно настроенным параметрам и ведёт статистику доставки.
Другие возможные исходы: некоторые уведомления могут не доставляться из-за технических проблем, что отражается в логах (см. рисунок 4.41).
Варианты проверки:
1) настройка напоминаний за 24 часа;
2) настройка напоминаний за 2 часа;
3) отправка уведомлений об отмене записи;
4) персонализация сообщений по типам специальностей.
*Рисунок 4.41 – Настройки системы уведомлений*
4.1.40 Групповая отмена записей
Цель теста: проверить возможность массовой отмены записей администратором.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти к списку записей;
3) выбрать несколько записей для отмены;
4) нажать кнопку «Групповая отмена»;
5) указать причину отмены;
6) подтвердить массовую отмену.
Ожидаемый результат: система отменяет все выбранные записи, освобождает временные слоты, отправляет уведомления всем затронутым пациентам и логирует массовое действие.
Другие возможные исходы: система может потребовать дополнительного подтверждения для отмены большого количества записей (см. рисунок 4.42).
Варианты проверки:
1) отмена записей одного врача на день;
2) отмена всех записей в связи с чрезвычайной ситуацией;
3) выборочная отмена записей по критериям;
4) отмена с предложением альтернативных вариантов.
*Рисунок 4.42 – Интерфейс групповой отмены записей*
4.1.41 Экспорт данных для отчётности
Цель теста: проверить функциональность экспорта статистических данных и отчётов.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Отчёты и аналитика»;
3) выбрать тип отчёта (записи, доходы, статистика врачей);
4) настроить период и параметры экспорта;
5) сгенерировать и скачать отчёт.
Ожидаемый результат: система генерирует структурированный отчёт с требуемыми данными в выбранном формате, пригодный для анализа и представления руководству.
Другие возможные исходы: для больших объёмов данных генерация может занимать время, система уведомляет о готовности (см. рисунок 4.43).
Варианты проверки:
1) экспорт статистики записей за месяц;
2) отчёт по загруженности врачей;
3) анализ популярности специальностей;
4) отчёт по отменённым записям.
*Рисунок 4.43 – Интерфейс генерации отчётов*
4.1.42 Система напоминаний
Цель теста: проверить работу автоматической системы напоминаний пациентам.
Шаги:
1) настроить систему уведомлений согласно тесту 4.1.41;
2) создать тестовые записи на ближайшее время;
3) дождаться времени отправки напоминаний;
4) проверить статистику отправленных уведомлений;
5) убедиться в получении уведомлений пациентами.
Ожидаемый результат: система автоматически отправляет напоминания согласно расписанию, ведёт статистику доставки и обрабатывает ошибки отправки.
Другие возможные исходы: некоторые уведомления могут не доставляться, что должно отражаться в логах системы (см. рисунок 4.44).
Варианты проверки:
1) отправка напоминаний за 24 часа;
2) отправка экстренных уведомлений;
3) обработка недоставленных сообщений;
4) персонализация напоминаний по пациентам.
*Рисунок 4.44 – Статистика работы системы напоминаний*
4.1.43 Резервное копирование и восстановление
Цель теста: проверить функциональность создания резервных копий данных системы.
Шаги:
1) авторизоваться под учётной записью администратора;
2) перейти в раздел «Резервное копирование»;
3) настроить параметры копирования (полная/инкрементальная копия);
4) запустить процесс создания резервной копии;
5) проверить успешность создания копии;
6) при необходимости протестировать восстановление.
Ожидаемый результат: система создаёт надёжную резервную копию всех критически важных данных, сохраняет её в безопасном месте и предоставляет возможность восстановления.
Другие возможные исходы: процесс может занимать значительное время для больших баз данных, требуется мониторинг прогресса (см. рисунок 4.45).
Варианты проверки:
1) создание полной резервной копии;
2) создание инкрементальной копии;
3) автоматическое резервное копирование по расписанию;
4) тестовое восстановление из копии.
*Рисунок 4.45 – Интерфейс резервного копирования*
