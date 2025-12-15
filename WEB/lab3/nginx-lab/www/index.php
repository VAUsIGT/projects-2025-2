<?php
session_start();

// Обработка очистки сессии при переходе на form.html
if (basename($_SERVER['PHP_SELF']) == 'index.php' && isset($_GET['clear_session'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Обработка очистки cookies
if (isset($_GET['clear_cookies'])) {
    // Устанавливаем время жизни в прошлое для удаления cookies
    setcookie("taxi_name", "", time() - 3600, "/");
    setcookie("taxi_email", "", time() - 3600, "/");
    setcookie("taxi_passengers", "", time() - 3600, "/");
    setcookie("taxi_tariff", "", time() - 3600, "/");
    setcookie("taxi_car_type", "", time() - 3600, "/");
    setcookie("taxi_last_order", "", time() - 3600, "/");
    setcookie("taxi_order_history", "", time() - 3600, "/");
    header("Location: index.php");
    exit();
}

// Чтение данных из cookies
$cookie_name = isset($_COOKIE['taxi_name']) ? htmlspecialchars($_COOKIE['taxi_name']) : '';
$cookie_email = isset($_COOKIE['taxi_email']) ? htmlspecialchars($_COOKIE['taxi_email']) : '';
$cookie_passengers = isset($_COOKIE['taxi_passengers']) ? htmlspecialchars($_COOKIE['taxi_passengers']) : '';
$cookie_tariff = isset($_COOKIE['taxi_tariff']) ? htmlspecialchars($_COOKIE['taxi_tariff']) : '';
$cookie_car_type = isset($_COOKIE['taxi_car_type']) ? htmlspecialchars($_COOKIE['taxi_car_type']) : '';
$cookie_last_order = isset($_COOKIE['taxi_last_order']) ? htmlspecialchars($_COOKIE['taxi_last_order']) : '';
$cookie_order_history = isset($_COOKIE['taxi_order_history']) ? json_decode($_COOKIE['taxi_order_history'], true) : [];

// Преобразование значений для отображения
$tariff_names = [
    'economy' => 'Эконом',
    'comfort' => 'Комфорт',
    'business' => 'Бизнес',
    'premium' => 'Премиум'
];

$car_type_names = [
    'sedan' => 'Седан',
    'minivan' => 'Минивэн',
    'suv' => 'Внедорожник'
];

$cookie_tariff_name = isset($tariff_names[$cookie_tariff]) ? $tariff_names[$cookie_tariff] : $cookie_tariff;
$cookie_car_type_name = isset($car_type_names[$cookie_car_type]) ? $car_type_names[$cookie_car_type] : $cookie_car_type;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ такси</title>
    <style>
        /* Анимация появления формы */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            animation: fadeIn 1s ease-out;
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .error-container {
            background-color: #ffeaea;
            border: 2px solid #e74c3c;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease-out;
        }
        
        .error-container h3 {
            color: #e74c3c;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .error-container ul {
            margin: 0;
            padding-left: 20px;
            color: #c0392b;
        }
        
        .error-container li {
            margin-bottom: 5px;
        }
        
        .success-message {
            background-color: #d4edda;
            border: 2px solid #28a745;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #155724;
            animation: fadeIn 0.5s ease-out;
        }
        
        .session-data, .cookie-data {
            background-color: #f0f8ff;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .cookie-data {
            background-color: #fff8e1;
            border-color: #ffc107;
        }
        
        .session-data h3, .cookie-data h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        
        .session-data ul, .cookie-data ul {
            list-style-type: none;
            padding-left: 0;
        }
        
        .session-data li, .cookie-data li {
            margin-bottom: 8px;
            padding: 5px;
            background-color: white;
            border-radius: 3px;
        }
        
        .cookie-history {
            background-color: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .cookie-history h4 {
            color: #2e7d32;
            margin-top: 0;
        }
        
        .cookie-history-item {
            background-color: white;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 3px;
            border-left: 4px solid #4caf50;
        }
        
        .links {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .links a {
            display: inline-block;
            margin: 0 10px;
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .links a:hover {
            background-color: #2980b9;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #34495e;
        }
        
        /* Границы и фон для полей */
        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="datetime-local"],
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        /* Стили для полей с ошибкой */
        input.error-field,
        select.error-field {
            border-color: #e74c3c;
            background-color: #ffeaea;
        }
        
        input[type="checkbox"],
        input[type="radio"] {
            margin-right: 10px;
        }
        
        .checkbox-group,
        .radio-group {
            margin: 15px 0;
        }
        
        .checkbox-group label,
        .radio-group label {
            display: inline-flex;
            align-items: center;
            margin-right: 20px;
            font-weight: normal;
            cursor: pointer;
        }
        
        /* Стилизация кнопки */
        button {
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        
        /* Hover-эффект для кнопки */
        button:hover {
            background-color: #2980b9;
            transform: scale(1.02);
        }
        
        .required {
            color: red;
        }
        
        .field-error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        .help-text {
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .cookie-info {
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>🚕 Заказ такси</h1>
        
        <div class="links">
            <a href="index.php">Заполнить форму</a> |
            <a href="view.php">Посмотреть все данные</a>
        </div>
        
        <?php if(isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
            <div class="error-container">
                <h3>Ошибки при заполнении формы:</h3>
                <ul>
                    <?php foreach($_SESSION['errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php unset($_SESSION['errors']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['name']) && !isset($_SESSION['errors'])): ?>
            <div class="success-message">
                <h3>✅ Данные успешно сохранены!</h3>
                <p>Ваша заявка принята. Вы можете просмотреть все сохраненные данные на соответствующих страницах.</p>
            </div>
        <?php endif; ?>
        
        <!-- Блок с данными из cookies -->
        <?php if(!empty($cookie_name) || !empty($cookie_email)): ?>
            <div class="cookie-data">
                <h3>🍪 Данные из cookies (сохранены в вашем браузере):</h3>
                <ul>
                    <?php if(!empty($cookie_name)): ?>
                        <li><strong>Имя:</strong> <?= $cookie_name ?></li>
                    <?php endif; ?>
                    <?php if(!empty($cookie_email)): ?>
                        <li><strong>Email:</strong> <?= $cookie_email ?></li>
                    <?php endif; ?>
                    <?php if(!empty($cookie_passengers)): ?>
                        <li><strong>Пассажиров (последний раз):</strong> <?= $cookie_passengers ?></li>
                    <?php endif; ?>
                    <?php if(!empty($cookie_tariff_name)): ?>
                        <li><strong>Тариф (последний раз):</strong> <?= $cookie_tariff_name ?></li>
                    <?php endif; ?>
                    <?php if(!empty($cookie_car_type_name)): ?>
                        <li><strong>Тип авто (последний раз):</strong> <?= $cookie_car_type_name ?></li>
                    <?php endif; ?>
                    <?php if(!empty($cookie_last_order)): ?>
                        <li><strong>Последний заказ:</strong> <?= date('d.m.Y H:i', strtotime($cookie_last_order)) ?></li>
                    <?php endif; ?>
                </ul>
                <p style="text-align: center; margin-top: 10px;">
                    <a href="?clear_cookies=1" style="color: #e74c3c; text-decoration: none;">Очистить cookies</a>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- История заказов из cookies -->
        <?php if(!empty($cookie_order_history) && is_array($cookie_order_history)): ?>
            <div class="cookie-history">
                <h4>📋 История ваших заказов (из cookies):</h4>
                <?php foreach($cookie_order_history as $index => $order): ?>
                    <div class="cookie-history-item">
                        <strong>Заказ #<?= $index + 1 ?> (<?= date('d.m.Y H:i', strtotime($order['timestamp'])) ?>):</strong><br>
                        <?= htmlspecialchars($order['name']) ?> - 
                        <?= htmlspecialchars($order['passengers']) ?> пасс. - 
                        <?= isset($tariff_names[$order['tariff']]) ? $tariff_names[$order['tariff']] : $order['tariff'] ?> - 
                        <?= isset($car_type_names[$order['car_type']]) ? $car_type_names[$order['car_type']] : $order['car_type'] ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Блок с данными из сессии -->
        <?php if(isset($_SESSION['name']) && !isset($_SESSION['errors'])): ?>
            <div class="session-data">
                <h3>📝 Данные из текущей сессии:</h3>
                <ul>
                    <li><strong>Имя:</strong> <?= htmlspecialchars($_SESSION['name']) ?></li>
                    <li><strong>Email:</strong> <?= htmlspecialchars($_SESSION['email']) ?></li>
                    <li><strong>Количество пассажиров:</strong> <?= htmlspecialchars($_SESSION['passengers']) ?></li>
                    <li><strong>Дата и время заказа:</strong> <?= htmlspecialchars($_SESSION['order_time']) ?></li>
                    <li><strong>Тариф:</strong> <?= isset($_SESSION['tariff_name']) ? htmlspecialchars($_SESSION['tariff_name']) : htmlspecialchars($_SESSION['tariff']) ?></li>
                    <li><strong>Багаж:</strong> <?= $_SESSION['luggage'] == 'yes' ? 'Да (+50 ₽)' : 'Нет' ?></li>
                    <li><strong>Тип автомобиля:</strong> <?= isset($_SESSION['car_type_name']) ? htmlspecialchars($_SESSION['car_type_name']) : htmlspecialchars($_SESSION['car_type']) ?></li>
                    <?php if(isset($_SESSION['last_activity'])): ?>
                        <li><strong>Последняя активность:</strong> <?= date('H:i:s d.m.Y', $_SESSION['last_activity']) ?></li>
                    <?php endif; ?>
                </ul>
                <p style="text-align: center; margin-top: 10px;">
                    <a href="?clear_session=1" style="color: #e74c3c; text-decoration: none;">Очистить данные сессии</a>
                </p>
            </div>
        <?php elseif(!isset($_SESSION['errors'])): ?>
            <div class="session-data">
                <p>Данных в текущей сессии пока нет.</p>
            </div>
        <?php endif; ?>
        
        <form id="taxiForm" action="process.php" method="POST" novalidate>
            <!-- Текстовое поле: Имя -->
            <div class="form-group">
                <label for="name">Имя клиента <span class="required">*</span></label>
                <input type="text" id="name" name="name" 
                       value="<?= isset($_SESSION['old_name']) ? htmlspecialchars($_SESSION['old_name']) : (isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : $cookie_name) ?>" 
                       placeholder="Введите ваше имя" required
                       class="<?= (isset($_SESSION['errors']) && (in_array('Имя не может быть пустым', $_SESSION['errors']) || preg_grep('/^Имя/', $_SESSION['errors']))) ? 'error-field' : '' ?>">
                <div class="help-text">Только буквы, пробелы и дефисы (2-50 символов)</div>
            </div>

            <!-- Поле для email -->
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" 
                       value="<?= isset($_SESSION['old_email']) ? htmlspecialchars($_SESSION['old_email']) : (isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : $cookie_email) ?>" 
                       placeholder="example@mail.com" required
                       class="<?= (isset($_SESSION['errors']) && preg_grep('/^Некорректный email|^Email/', $_SESSION['errors'])) ? 'error-field' : '' ?>">
                <div class="help-text">Например: user@example.com</div>
            </div>

            <!-- Числовое поле: Количество пассажиров -->
            <div class="form-group">
                <label for="passengers">Количество пассажиров <span class="required">*</span></label>
                <input type="number" id="passengers" name="passengers" 
                       value="<?= isset($_SESSION['old_passengers']) ? htmlspecialchars($_SESSION['old_passengers']) : (isset($_SESSION['passengers']) ? htmlspecialchars($_SESSION['passengers']) : (!empty($cookie_passengers) ? $cookie_passengers : '1')) ?>" 
                       min="1" max="8" required
                       class="<?= (isset($_SESSION['errors']) && preg_grep('/^Количество пассажиров/', $_SESSION['errors'])) ? 'error-field' : '' ?>">
                <div class="help-text">От 1 до 8 человек</div>
            </div>

            <!-- Поле для даты и времени -->
            <div class="form-group">
                <label for="order_time">Дата и время заказа <span class="required">*</span></label>
                <input type="datetime-local" id="order_time" name="order_time" 
                       value="<?= isset($_SESSION['old_order_time']) ? htmlspecialchars($_SESSION['old_order_time']) : (isset($_SESSION['order_time']) ? htmlspecialchars($_SESSION['order_time']) : '') ?>" 
                       required
                       class="<?= (isset($_SESSION['errors']) && preg_grep('/^Дата и время/', $_SESSION['errors'])) ? 'error-field' : '' ?>">
                <div class="help-text">Не может быть в прошлом</div>
            </div>

            <!-- Выпадающий список: Тариф -->
            <div class="form-group">
                <label for="tariff">Тариф <span class="required">*</span></label>
                <select id="tariff" name="tariff" required
                        class="<?= (isset($_SESSION['errors']) && preg_grep('/^Выберите тариф|^Некорректный тариф/', $_SESSION['errors'])) ? 'error-field' : '' ?>">
                    <option value="">-- Выберите тариф --</option>
                    <option value="economy" <?= (isset($_SESSION['old_tariff']) && $_SESSION['old_tariff'] == 'economy') || (isset($_SESSION['tariff']) && $_SESSION['tariff'] == 'economy') || $cookie_tariff == 'economy' ? 'selected' : '' ?>>Эконом (от 150 ₽)</option>
                    <option value="comfort" <?= (isset($_SESSION['old_tariff']) && $_SESSION['old_tariff'] == 'comfort') || (isset($_SESSION['tariff']) && $_SESSION['tariff'] == 'comfort') || $cookie_tariff == 'comfort' ? 'selected' : '' ?>>Комфорт (от 250 ₽)</option>
                    <option value="business" <?= (isset($_SESSION['old_tariff']) && $_SESSION['old_tariff'] == 'business') || (isset($_SESSION['tariff']) && $_SESSION['tariff'] == 'business') || $cookie_tariff == 'business' ? 'selected' : '' ?>>Бизнес (от 400 ₽)</option>
                    <option value="premium" <?= (isset($_SESSION['old_tariff']) && $_SESSION['old_tariff'] == 'premium') || (isset($_SESSION['tariff']) && $_SESSION['tariff'] == 'premium') || $cookie_tariff == 'premium' ? 'selected' : '' ?>>Премиум (от 600 ₽)</option>
                </select>
            </div>

            <!-- Чекбокс: Багаж -->
            <div class="form-group">
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" id="luggage" name="luggage" value="yes"
                            <?= (isset($_SESSION['old_luggage']) && $_SESSION['old_luggage'] == 'yes') || (isset($_SESSION['luggage']) && $_SESSION['luggage'] == 'yes') ? 'checked' : '' ?>>
                        🧳 Требуется багажное отделение (+50 ₽)
                    </label>
                </div>
            </div>

            <!-- Радио-кнопки: Тип авто -->
            <div class="form-group">
                <label>Тип автомобиля <span class="required">*</span></label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="car_type" value="sedan" 
                            <?= (isset($_SESSION['old_car_type']) && $_SESSION['old_car_type'] == 'sedan') || (isset($_SESSION['car_type']) && $_SESSION['car_type'] == 'sedan') || $cookie_car_type == 'sedan' ? 'checked' : '' ?> required>
                        🚗 Седан (до 3 пассажиров)
                    </label>
                    <label>
                        <input type="radio" name="car_type" value="minivan"
                            <?= (isset($_SESSION['old_car_type']) && $_SESSION['old_car_type'] == 'minivan') || (isset($_SESSION['car_type']) && $_SESSION['car_type'] == 'minivan') || $cookie_car_type == 'minivan' ? 'checked' : '' ?>>
                        🚐 Минивэн (до 6 пассажиров)
                    </label>
                    <label>
                        <input type="radio" name="car_type" value="suv"
                            <?= (isset($_SESSION['old_car_type']) && $_SESSION['old_car_type'] == 'suv') || (isset($_SESSION['car_type']) && $_SESSION['car_type'] == 'suv') || $cookie_car_type == 'suv' ? 'checked' : '' ?>>
                        🚙 Внедорожник (до 7 пассажиров)
                    </label>
                </div>
                <div class="help-text">Выберите автомобиль в соответствии с количеством пассажиров</div>
            </div>

            <!-- Кнопка отправки -->
            <button type="submit">🚖 Заказать такси</button>
        </form>
        
        <div class="cookie-info">
            <p><strong>Информация о cookies:</strong></p>
            <p>Cookies сохраняются в вашем браузере и хранятся 30 дней. Они содержат ваши последние данные для удобства заполнения формы.</p>
            <p>Вы можете очистить cookies в любое время, нажав на соответствующую ссылку выше.</p>
        </div>
    </div>

    <script>
        // Добавляем текущую дату и время по умолчанию если нет данных в сессии
        document.addEventListener('DOMContentLoaded', function() {
            const orderTimeInput = document.getElementById('order_time');
            
            // Только если поле пустое и нет значения из сессии
            if (!orderTimeInput.value) {
                const now = new Date();
                // Устанавливаем минимальную дату - текущий момент
                const minDateTime = now.toISOString().slice(0, 16);
                orderTimeInput.min = minDateTime;
                
                // Устанавливаем значение по умолчанию - через 30 минут от текущего времени
                now.setMinutes(now.getMinutes() + 30);
                const defaultDateTime = now.toISOString().slice(0, 16);
                orderTimeInput.value = defaultDateTime;
            }
        });

        // Alert с введенными данными перед отправкой
        document.getElementById("taxiForm").addEventListener("submit", function(e) {
            // Получаем значения из формы
            const name = document.querySelector("[name='name']").value;
            const email = document.querySelector("[name='email']").value;
            const passengers = document.querySelector("[name='passengers']").value;
            const orderTime = document.querySelector("[name='order_time']").value;
            const tariff = document.querySelector("[name='tariff']").value;
            
            // Получаем значение чекбокса "Багаж"
            const luggageCheckbox = document.querySelector("[name='luggage']");
            const luggage = luggageCheckbox.checked ? "Да (+50 ₽)" : "Нет";
            
            // Получаем значение выбранного типа автомобиля
            const carTypeRadio = document.querySelector("[name='car_type']:checked");
            const carType = carTypeRadio ? carTypeRadio.value : "Не выбран";
            
            // Формируем сообщение для alert
            const message = `Проверьте введенные данные:\n\n` +
                           `Имя: ${name}\n` +
                           `Email: ${email}\n` +
                           `Количество пассажиров: ${passengers}\n` +
                           `Дата и время: ${orderTime}\n` +
                           `Тариф: ${tariff}\n` +
                           `Багаж: ${luggage}\n` +
                           `Тип автомобиля: ${carType}`;
            
            // Показываем alert с введенными данными
            alert(message);
        });

        // Очищаем старые данные из сессии при загрузке страницы
        <?php
        // Очищаем старые данные после их использования
        if (isset($_SESSION['old_name'])) unset($_SESSION['old_name']);
        if (isset($_SESSION['old_email'])) unset($_SESSION['old_email']);
        if (isset($_SESSION['old_passengers'])) unset($_SESSION['old_passengers']);
        if (isset($_SESSION['old_order_time'])) unset($_SESSION['old_order_time']);
        if (isset($_SESSION['old_tariff'])) unset($_SESSION['old_tariff']);
        if (isset($_SESSION['old_luggage'])) unset($_SESSION['old_luggage']);
        if (isset($_SESSION['old_car_type'])) unset($_SESSION['old_car_type']);
        ?>
    </script>
</body>
</html>