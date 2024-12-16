import "event-source-polyfill";

export const createSSEConnection = (url, onMessage, onError) => {
  // Создаем экземпляр EventSource для подключения
  const eventSource = new EventSource(url);

  // Обработчик для получения сообщений
  eventSource.onmessage = function (event) {
    try {
      const dataString = event.data;

      if (!dataString) {
        console.error("Пустое сообщение SSE или ошибка в данных:", event);
        return;
      }

      console.log("Полученные данные:", dataString); // Для отладки

      // Игнорируем сообщение "Connected to SSE", если оно есть
      if (dataString === "Connected to SSE") {
        console.log("Соединение установлено, данных нет.");
        return;
      }

      // Используем регулярное выражение для извлечения chatId
      const match = dataString.match(/([a-zA-Z]{3}) (\d+)/); // Ищем строку "cID <число>"

      if (match) {
        const typeStr = match[1];
        const chatId = match[2]; // Извлекаем число из строки "cID 78"
        const data = { chatId, typeStr }; // Создаем объект с chatId
        console.log(chatId, match);
        // Передаем данные в обработчик
        if (chatId) {
          onMessage(data);
        } else {
          console.error("Обработчик onMessage не был передан!");
        }
      } else {
        console.error("Не удалось распарсить данные:", dataString);
      }
    } catch (err) {
      console.error("Ошибка при обработке данных SSE", err);
    }
  };

  // Обработчик ошибки SSE
  eventSource.onerror = function (error) {
    console.error("Ошибка SSE:", error);
    if (onError) {
      onError(error);
    }
  };

  // Закрытие подключения
  return {
    close: () => {
      eventSource.close();
      console.log("SSE connection closed");
    },
  };
};
