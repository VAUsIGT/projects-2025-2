<?php
// Включаем буферизацию вывода для предотвращения ошибок с заголовками
ob_start();

// Стартуем сессию
session_start();

// Очищаем предыдущие ошибки
unset($_SESSION['errors']);

// Получаем данные формы через $_POST
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$passengers = isset($_POST['passengers']) ? trim($_POST['passengers']) : '';
$order_time = isset($_POST['order_time']) ? trim($_POST['order_time']) : '';
$tariff = isset($_POST['tariff']) ? trim($_POST['tariff']) : '';
$luggage = isset($_POST['luggage']) ? trim($_POST['luggage']) : 'no';
$car_type = isset($_POST['car_type']) ? trim($_POST['car_type']) : '';

// Массив для хранения ошибок
$errors = [];

// Проверка корректности данных
// 1. Проверка имени
if (empty($name)) {
    $errors[] = "Имя не может быть пустым";
} elseif (strlen($name) < 2) {
    $errors[] = "Имя должно содержать минимум 2 символа";
} elseif (strlen($name) > 50) {
    $errors[] = "Имя не должно превышать 50 символов";
} elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $name)) {
    $errors[] = "Имя может содержать только буквы, пробелы и дефисы";
}

// 2. Проверка email
if (empty($email)) {
    $errors[] = "Email не может быть пустым";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный email";
} elseif (strlen($email) > 100) {
    $errors[] = "Email не должен превышать 100 символов";
}

// 3. Проверка количества пассажиров
if (empty($passengers)) {
    $errors[] = "Количество пассажиров не может быть пустым";
} elseif (!is_numeric($passengers)) {
    $errors[] = "Количество пассажиров должно быть числом";
} elseif ($passengers < 1 || $passengers > 8) {
    $errors[] = "Количество пассажиров должно быть от 1 до 8";
}

// 4. Проверка даты и времени
if (empty($order_time)) {
    $errors[] = "Дата и время заказа не могут быть пустыми";
} else {
    $order_datetime = DateTime::createFromFormat('Y-m-d\TH:i', $order_time);
    $now = new DateTime();
    if (!$order_datetime) {
        $errors[] = "Некорректный формат даты и времени";
    } elseif ($order_datetime < $now) {
        $errors[] = "Дата и время заказа не могут быть в прошлом";
    }
}

// 5. Проверка тарифа
if (empty($tariff)) {
    $errors[] = "Выберите тариф";
} elseif (!in_array($tariff, ['economy', 'comfort', 'business', 'premium'])) {
    $errors[] = "Некорректный тариф";
}

// 6. Проверка типа автомобиля
if (empty($car_type)) {
    $errors[] = "Выберите тип автомобиля";
} elseif (!in_array($car_type, ['sedan', 'minivan', 'suv'])) {
    $errors[] = "Некорректный тип автомобиля";
}

// 7. Проверка соответствия количества пассажиров и типа авто
if ($car_type == 'sedan' && $passengers > 3) {
    $errors[] = "Седан вмещает максимум 3 пассажира. Пожалуйста, выберите другой тип автомобиля.";
} elseif ($car_type == 'minivan' && $passengers > 6) {
    $errors[] = "Минивэн вмещает максимум 6 пассажиров. Пожалуйста, выберите другой тип автомобиля.";
} elseif ($car_type == 'suv' && $passengers > 7) {
    $errors[] = "Внедорожник вмещает максимум 7 пассажиров. Пожалуйста, уменьшите количество пассажиров.";
}

// Если есть ошибки, сохраняем их в сессию и перенаправляем обратно
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    
    // Сохраняем введенные данные в сессии, чтобы не потерять их при ошибке
    $_SESSION['old_name'] = htmlspecialchars($name);
    $_SESSION['old_email'] = htmlspecialchars($email);
    $_SESSION['old_passengers'] = htmlspecialchars($passengers);
    $_SESSION['old_order_time'] = htmlspecialchars($order_time);
    $_SESSION['old_tariff'] = htmlspecialchars($tariff);
    $_SESSION['old_luggage'] = htmlspecialchars($luggage);
    $_SESSION['old_car_type'] = htmlspecialchars($car_type);
    
    header("Location: index.php");
    exit();
}

// Если ошибок нет, обрабатываем данные
// Используем htmlspecialchars для защиты от XSS атак
$name = htmlspecialchars($name);
$email = htmlspecialchars($email);
$passengers = htmlspecialchars($passengers);
$order_time = htmlspecialchars($order_time);
$tariff = htmlspecialchars($tariff);
$luggage = htmlspecialchars($luggage);
$car_type = htmlspecialchars($car_type);

// ========== СОХРАНЕНИЕ ДАННЫХ В COOKIES ==========
// Сохраняем основные данные в cookies на 30 дней
$cookie_expire = time() + (30 * 24 * 60 * 60); // 30 дней

setcookie("taxi_name", $name, $cookie_expire, "/");
setcookie("taxi_email", $email, $cookie_expire, "/");
setcookie("taxi_passengers", $passengers, $cookie_expire, "/");
setcookie("taxi_tariff", $tariff, $cookie_expire, "/");
setcookie("taxi_car_type", $car_type, $cookie_expire, "/");
setcookie("taxi_last_order", date('Y-m-d H:i:s'), $cookie_expire, "/");

// Также сохраняем историю заказов в cookie (последние 5 заказов)
$order_history = isset($_COOKIE['taxi_order_history']) ? json_decode($_COOKIE['taxi_order_history'], true) : [];

// Добавляем текущий заказ в историю
$new_order = [
    'name' => $name,
    'email' => $email,
    'passengers' => $passengers,
    'order_time' => $order_time,
    'tariff' => $tariff,
    'car_type' => $car_type,
    'timestamp' => date('Y-m-d H:i:s')
];

array_unshift($order_history, $new_order);

// Ограничиваем историю 5 последними заказами
$order_history = array_slice($order_history, 0, 5);

// Сохраняем историю обратно в cookie
setcookie("taxi_order_history", json_encode($order_history), $cookie_expire, "/");
// =================================================

// Сохраняем все данные в сессии
$_SESSION['name'] = $name;
$_SESSION['email'] = $email;
$_SESSION['passengers'] = $passengers;
$_SESSION['order_time'] = $order_time;
$_SESSION['tariff'] = $tariff;
$_SESSION['luggage'] = $luggage;
$_SESSION['car_type'] = $car_type;

// Также сохраняем время последней активности
$_SESSION['last_activity'] = time();

// Сохраняем тарифы для отображения на русском языке
$tariff_names = [
    'economy' => 'Эконом',
    'comfort' => 'Комфорт',
    'business' => 'Бизнес',
    'premium' => 'Премиум'
];

if (isset($tariff_names[$tariff])) {
    $_SESSION['tariff_name'] = $tariff_names[$tariff];
} else {
    $_SESSION['tariff_name'] = $tariff;
}

// Сохраняем типы автомобилей для отображения на русском языке
$car_type_names = [
    'sedan' => 'Седан',
    'minivan' => 'Минивэн',
    'suv' => 'Внедорожник'
];

if (isset($car_type_names[$car_type])) {
    $_SESSION['car_type_name'] = $car_type_names[$car_type];
} else {
    $_SESSION['car_type_name'] = $car_type;
}

// Формируем красивую дату для отображения
if ($order_time) {
    $date_time = DateTime::createFromFormat('Y-m-d\TH:i', $order_time);
    if ($date_time) {
        $_SESSION['order_time_formatted'] = $date_time->format('d.m.Y H:i');
    }
}

// ========== СОХРАНЕНИЕ ДАННЫХ В ФАЙЛ ==========
// Подготавливаем строку для записи в файл
$data_line = $name . ";" . $email . ";" . $passengers . ";" . $order_time . ";" 
             . $tariff . ";" . $luggage . ";" . $car_type . ";" . date('Y-m-d H:i:s') . "\n";

// Сохраняем данные в файл data.txt
// FILE_APPEND - добавляем в конец файла
// LOCK_EX - блокируем файл на время записи
file_put_contents("data.txt", $data_line, FILE_APPEND | LOCK_EX);
// ==============================================

// После сохранения в сессию, cookies и файл перенаправляем обратно на главную страницу
header("Location: index.php");
exit();

// Закрываем буферизацию
ob_end_flush();
?>