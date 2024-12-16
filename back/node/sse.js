const http = require("http");
const { createClient } = require("ioredis");

// Подключение к Redis
const redisClient = createClient();
redisClient.on("ready", () => {
  console.log("Подключение к Redis успешно");
});
redisClient.on("error", (err) => {
  console.error("Ошибка подключения к Redis:", err);
});

// Список активных соединений
const connections = new Set();

// Создаем HTTP-сервер
const server = http.createServer((req, res) => {
  if (req.url !== "/sse") {
    res.writeHead(404, { "Content-Type": "text/plain" });
    res.end("Not Found");
    return;
  }

  // Устанавливаем заголовки для SSE
  res.writeHead(200, {
    "Content-Type": "text/event-stream",
    "Cache-Control": "no-cache",
    Connection: "keep-alive",
    "Access-Control-Allow-Origin": "*",
  });

  // Сообщение о подключении
  res.write("data: Connected to SSE\n\n");

  // Добавляем соединение в список активных
  connections.add(res);

  // Убираем соединение при закрытии
  req.on("close", () => {
    connections.delete(res);
  });
});

// Подписываемся на канал Redis
const subscriber = redisClient.duplicate();
subscriber.subscribe("sse_notifications", (err, count) => {
  if (err) {
    console.error("Ошибка подписки на канал:", err);
    return;
  }
  console.log(
    `Подписан на канал sse_notifications. Количество каналов: ${count}`
  );
});

subscriber.on("message", (channel, message) => {
  console.log(`Получено сообщение из Redis [${channel}]: ${message}`);

  // Рассылаем сообщение всем подключенным клиентам
  connections.forEach((conn) => {
    conn.write(`data: ${message}\n\n`);
  });
});

// Запускаем сервер
const PORT = 9501;
server.listen(PORT, () => {
  console.log(`Сервер запущен на http://localhost:${PORT}`);
});
