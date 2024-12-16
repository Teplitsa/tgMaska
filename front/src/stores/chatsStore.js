import { defineStore } from "pinia";
import { getMessages } from "../services/Chat";
export const useChatsStore = defineStore("chats", {
  state: () => ({
    chats: [],
    fullChats: [],
  }),
  actions: {
    setChats(chats) {
      this.chats = chats;
    },
    async setFullChats(chatId) {
      if (!this.fullChats[chatId]) {
        // Если сообщений для чата еще нет, создаем массив
        this.fullChats[chatId] = new Map();
      }
      try {
        // Получаем сообщения с сервера с помощью getMessages
        const data = await getMessages(chatId);

        // Добавляем полученные сообщения в Map
        const messages = data.messages || []; // Если нет сообщений, используем пустой массив
        messages.forEach((message) => {
          // Добавляем каждое сообщение в Map с его id как ключом
          this.fullChats[chatId].set(message.id, message);
        });

        console.log("Сообщения для чата ID:", chatId, this.fullChats[chatId]);
      } catch (error) {
        console.error("Ошибка при получении сообщений для чата:", error);
      }
    },
    clearChats() {
      this.chats = [];
    },
    async updateOrAddChat(chatData) {
      const chatIndex = this.chats.findIndex(
        (chat) => chat.chat_id === chatData.chat_id
      );
      if (chatIndex !== -1) {
        this.chats[chatIndex] = { ...this.chats[chatIndex], ...chatData };
      } else {
        this.chats.push(chatData);
      }
      console.log("Чат обновлен или добавлен:", chatData);
    },
  },
  getters: {
    // Геттер для получения сообщений по ID чата
    getMessagesByChatId: (state) => {
      return (chatId) => {
        // Возвращаем все сообщения для данного чата, если они есть
        return state.fullChats[chatId]
          ? Array.from(state.fullChats[chatId].values())
          : [];
      };
    },
    isChatExists: (state) => {
      return (chatId) => {
        return state.chats.some((chat) => chat.chat_id === chatId);
      };
    },
    getChatById: (state) => {
      return (chatId) => {
        return state.chats.find((chat) => chat.chat_id === chatId) || null;
      };
    },
  },
});
