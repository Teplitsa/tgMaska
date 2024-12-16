<?php
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

require __DIR__ . '/vendor/autoload.php';

// Список активных соединений
$connections = [];

// Создаем HTTP сервер на Swoole
$server = new Server("0.0.0.0", 9501);

// Устанавливаем конфигурацию сервера
$server->set([
    'daemonize' => false, // Оставляем сервер работать в переднем плане (для отладки)
    'worker_num' => 2, // Количество рабочих процессов
]);

// Запускаем подписку Redis в фоновом процессе
$server->on("WorkerStart", function ($server) {
    go(function () use ($server) {
        $redis = new Swoole\Coroutine\Redis();
        if (!$redis->connect('127.0.0.1', 6379)) {
            echo "Не удалось подключиться к Redis\n";
            return;
        }
        echo "Подключение к Redis успешно\n";
        // Подписываемся на канал
        $redis->subscribe(['sse_notifications']);
        
        while (true) {
            $message = $redis->recv(); // Ожидаем сообщение из Redis
            if ($message && isset($message[2])) { // Проверяем, что получили корректное сообщение
                $notification = $message[2];
                
                global $connections;
                foreach ($connections as $conn) {
                    if ($conn->isWritable()) {
                        $conn->write("data: {$notification}\n\n");
                    } else {
                        unset($connections[spl_object_id($conn)]);
                    }
                }
            }
        }
    });
});

// Устанавливаем обработчик для SSE-запросов
$server->on("Request", function (Request $request, Response $response) use ($server) {
    global $connections;

    if ($request->server['request_uri'] !== '/sse') {
        $response->status(404);
        $response->end("Not Found");
        return;
    }

    // Устанавливаем необходимые заголовки для SSE
    $response->header("Content-Type", "text/event-stream; charset=utf-8");
    $response->header("Cache-Control", "no-cache");
    $response->header("Connection", "keep-alive");
    $response->header("Access-Control-Allow-Origin", "*");

    // Добавляем соединение в список активных
    $connections[spl_object_id($response)] = $response;

    // Отправляем сообщение клиенту, что он подключен
    $response->write("data: Connected to SSE\n\n");

});

// Убираем соединение из списка при закрытии
$server->on('Close', function ($server, $fd) {
    global $connections;

    if (isset($connections[$fd])) {
        unset($connections[$fd]);
    }
});

// Запускаем сервер
$server->start();