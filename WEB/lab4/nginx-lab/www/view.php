<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Все сохранённые данные</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1, h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        
        ul {
            list-style-type: none;
            padding-left: 0;
        }
        
        li {
            background-color: #f8f9fa;
            margin-bottom: 10px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        li:hover {
            background-color: #e9ecef;
        }
        
        .data-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .data-info {
            flex-grow: 1;
        }
        
        .data-actions {
            margin-left: 15px;
        }
        
        .empty-message {
            text-align: center;
            color: #7f8c8d;
            padding: 40px;
            font-style: italic;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .links {
            text-align: center;
            margin-top: 30px;
        }
        
        .links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .links a:hover {
            background-color: #2980b9;
        }
        
        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .delete-btn:hover {
            background-color: #c0392b;
        }
        
        .file-info {
            background-color: #f0f8ff;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .session-info {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-top: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 Все сохранённые данные из файла</h1>
        
        <div class="file-info">
            <p><strong>Файл данных:</strong> data.txt</p>
            <p><strong>Путь к файлу:</strong> <?= realpath('data.txt') ?: 'data.txt (файл пока не создан)' ?></p>
            <p><strong>Дата просмотра:</strong> <?= date('d.m.Y H:i:s') ?></p>
        </div>
        
        <h2>Записи из файла:</h2>
        
        <?php if(file_exists("data.txt")): ?>
            <?php
            $lines = file("data.txt", FILE_IGNORE_NEW_LINES);
            if(count($lines) > 0): ?>
                <ul>
                    <?php 
                    $counter = 1;
                    foreach($lines as $line):
                        list($name, $email, $passengers, $order_time, $tariff, $luggage, $car_type) = explode(";", $line);
                        
                        // Преобразуем коды в читаемые названия
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
                        
                        $tariff_name = isset($tariff_names[$tariff]) ? $tariff_names[$tariff] : $tariff;
                        $car_type_name = isset($car_type_names[$car_type]) ? $car_type_names[$car_type] : $car_type;
                        $luggage_text = $luggage == 'yes' ? 'Да (+50 ₽)' : 'Нет';
                    ?>
                    <li>
                        <div class="data-item">
                            <div class="data-info">
                                <strong>Запись #<?= $counter ?>:</strong>
                                <p><?= htmlspecialchars($name) ?> (<?= htmlspecialchars($email) ?>)</p>
                                <p><small>
                                    Пассажиров: <?= htmlspecialchars($passengers) ?>, 
                                    Дата: <?= htmlspecialchars($order_time) ?>, 
                                    Тариф: <?= htmlspecialchars($tariff_name) ?>,
                                    Багаж: <?= $luggage_text ?>,
                                    Авто: <?= htmlspecialchars($car_type_name) ?>
                                </small></p>
                            </div>
                        </div>
                    </li>
                    <?php 
                    $counter++;
                    endforeach; 
                    ?>
                </ul>
                
                <div style="text-align: center; margin: 20px 0;">
                    <p><strong>Всего записей:</strong> <?= count($lines) ?></p>
                </div>
            <?php else: ?>
                <div class="empty-message">
                    <p>В файле пока нет данных.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-message">
                <p>Файл данных не найден.</p>
                <p>После первой отправки формы файл будет создан автоматически.</p>
            </div>
        <?php endif; ?>
        
        <div class="links">
            <a href="index.php">На главную</a>
            <a href="view_session.php">Посмотреть данные сессии</a>
        </div>
        
        <?php if(isset($_SESSION['name'])): ?>
            <div class="session-info">
                <h3>Текущая сессия:</h3>
                <table>
                    <tr>
                        <th>Поле</th>
                        <th>Значение</th>
                    </tr>
                    <tr>
                        <td>Имя</td>
                        <td><?= htmlspecialchars($_SESSION['name']) ?></td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td><?= htmlspecialchars($_SESSION['email']) ?></td>
                    </tr>
                    <tr>
                        <td>Пассажиры</td>
                        <td><?= htmlspecialchars($_SESSION['passengers']) ?></td>
                    </tr>
                    <tr>
                        <td>Дата и время</td>
                        <td><?= htmlspecialchars($_SESSION['order_time']) ?></td>
                    </tr>
                    <tr>
                        <td>Тариф</td>
                        <td><?= htmlspecialchars($_SESSION['tariff_name']) ?></td>
                    </tr>
                    <tr>
                        <td>Багаж</td>
                        <td><?= $_SESSION['luggage'] == 'yes' ? 'Да (+50 ₽)' : 'Нет' ?></td>
                    </tr>
                    <tr>
                        <td>Тип автомобиля</td>
                        <td><?= htmlspecialchars($_SESSION['car_type_name']) ?></td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>