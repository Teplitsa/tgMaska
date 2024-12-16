<template>
  <div class="chat">
    <div class="chat_top">
      <Back />
      <div class="info" v-if="chatInfo" @click="openFull">
        <div class="info_ava">
          <img :src="chatInfo.avatar" alt="Аватар чата" />
        </div>
        <div class="info_txt">
          <h2>
            {{ chatInfo.name }}
            <span><img src="../../public/lock.png" alt="lock" /></span>
          </h2>
          <p>{{ pluralizeMembers(chatInfo.count_members) }}</p>
        </div>
      </div>
      <div class="full_info" v-if="chatFull">
        <ul>
          <li
            v-for="member in chatInfo.members"
            :key="member.id"
            class="member"
          >
            <div class="member-info">
              <div class="info_ava">
                <img :src="member.avatar" alt="Avatar" class="member-avatar" />
              </div>
              <p class="member-nick">{{ member.nick }}</p>
            </div>
            <p v-if="isOwner(member)" class="member-role">{{ member.role }}</p>
            <p
              v-else-if="isCurrentUser(member.user_id) || chatInfo.isOwner"
              class="member-action"
              @click="confirmAndLeaveOrBanMember(member.user_id)"
            ></p>
          </li>
        </ul>
        <span
          v-if="chatInfo.isOwner"
          class="delete"
          @click="confirmAndDeleteChat()"
        >
          <Delete
        /></span>
      </div>
    </div>
    <div class="copy_link" @click="copyInviteLink">
      Скопировать ссылку-приглашение
    </div>
    <div className="contan_mess" ref="messagesContainer">
      <div
        v-for="message in messages"
        :key="message.id"
        :class="{ green: isCurrentUser(message.member.user_id) }"
        class="message"
      >
        <div class="ava">
          <!-- Если это не владелец, отображаем аватарку -->
          <img :src="message.member.avatar" alt="Аватар" className="avatar" />
        </div>
        <div class="message-content">
          <strong v-if="!isCurrentUser(message.member.user_id)"
            >{{ message.member.nick }}:</strong
          >
          <p>
            {{ message.decrypted_content }}
          </p>
          <span class="timestamp">{{ message.created_at }}</span>
        </div>
      </div>
    </div>
    <!-- Блок для отправки сообщений -->
    <div class="message-input">
      <input
        type="text"
        placeholder="Введите свое сообщение..."
        v-model="inputMessage"
        @keyup.enter="sendMessage"
      />
      <button @click="sendMessage">
        <svg
          width="21"
          height="21"
          viewBox="0 0 21 21"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M0.00160215 0.984375C0.00160215 0.65625 0.00160215 1.43295e-05 1.16548 0C2.6619 -1.84236e-05 19.2888 8.53125 20.4527 9.51563C21.3838 10.3031 20.9499 11.1562 20.4511 11.4844C19.2875 12.4685 2.50289 20.9949 1.16388 21C0 21 0 20.3438 0 20.0156L1.16505 12.7969C1.29771 12.0094 1.66401 11.8125 1.83057 11.8125L11.8067 10.8281C11.9175 10.8281 12.1392 10.7625 12.1392 10.5C12.1392 10.2375 11.9175 10.1719 11.8067 10.1719L1.83057 9.1875C1.66401 9.1875 1.29771 8.99063 1.16505 8.20313C0.775612 5.82365 0.00160215 1.3125 0.00160215 0.984375Z"
            fill="#868686"
          />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from "vue-router";
import {
  ref,
  onMounted,
  onUnmounted,
  computed,
  watch,
  nextTick,
  inject,
} from "vue";
import { createSSEConnection } from "../services/sse";
import {
  addMessage,
  getInfoChat,
  exitChat,
  handleLeaveOrBanMemberFromChat,
  handleDeleteChat,
} from "../services/Chat";
import { useChatsStore } from "../stores/ChatsStore";
import { useUserStore } from "../stores/userStore";
import Back from "../components/svg/Back.vue";
import Delete from "../components/svg/Delete.vue";

// Состояние для хранения данных, полученных через SSE
const sseData = ref([]);
const inputMessage = ref("");

// Получаем параметр chatId из маршрута
const route = useRoute();
const router = useRouter();
const chatId = route.params.id;

// Получаем доступ к хранилищу чатов
const chatStore = useChatsStore();
// Список сообщений для отображения (получаем через геттер из хранилища)
const messages = computed(() => chatStore.getMessagesByChatId(chatId));
const userStore = useUserStore();
const userId = userStore.user.id;
const chatInfo = ref(null);
const chatFull = ref(false);
const messagesContainer = ref(null);
const tg = inject("telegram");

const openFull = () => {
  chatFull.value = !chatFull.value;
};
const pluralizeMembers = (count) => {
  const forms = ["участник", "участника", "участников"];
  const cases = [2, 0, 1, 1, 1, 2];
  const formIndex =
    count % 100 > 4 && count % 100 < 20 ? 2 : cases[Math.min(count % 10, 5)];
  return `${count} ${forms[formIndex]}`;
};

const showSuccessPopup = () => {
  if (!tg) {
    return;
  }

  tg.showPopup({
    title: "Успех!",
    message: "Ссылка успешно скопирована.",
    buttons: [{ id: "ok", type: "default", text: "Ок" }],
  });
};

const copyInviteLink = () => {
  if (chatInfo.value && chatInfo.value.inviteLink) {
    navigator.clipboard
      .writeText(chatInfo.value.inviteLink)
      .then(() => {
        console.log("Ссылка успешно скопирована!");
        showSuccessPopup(); // Вызов всплывающего окна
      })
      .catch((err) => {
        console.error("Ошибка при копировании ссылки:", err);
      });
  } else {
    console.warn("Ссылка отсутствует!");
  }
};

const isCurrentUser = (memberId) => {
  return memberId === userId;
};

const scrollToBottom = () => {
  if (messagesContainer.value) {
    console.log(messagesContainer.value.scrollHeight);
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

const exitChatFromCurrentChat = async () => {
  try {
    // Вызываем метод для выхода из чата и передаем chatId и userId
    const response = await exitChat(chatId, userId);

    if (response && response.message === "Exit successfully") {
      console.log("Пользователь успешно покинул чат");
      // Дополнительные действия после выхода
    } else {
      console.error("Ошибка при выходе из чата", response);
    }
  } catch (error) {
    console.error("Ошибка при вызове метода выхода:", error);
  }
};
const isOwner = (member) => {
  // Проверяем, является ли пользователь владельцем
  return member.role === "owner";
};

const showConfirmationPopup = (message, callback) => {
  if (!tg) {
    return;
  }
  tg.showPopup(
    {
      title: "Подтверждение",
      message:
        message ||
        "Вы уверены? Все сообщения участника будут также удалены из чата (в асинхронном режиме, они исчезнут при следующем заходе в чат).",
      buttons: [
        { id: "cancel", type: "default", text: "Отмена" },
        { id: "ok", type: "destructive", text: "Удалить" },
      ],
    },
    (buttonId) => {
      if (buttonId === "ok" && callback) {
        callback();
      }
    }
  );
};
const confirmAndDeleteChat = () => {
  const customMessage =
    "Вы уверены, что хотите удалить этот чат? Это действие необратимо.";
  showConfirmationPopup(customMessage, async () => {
    try {
      const result = await handleDeleteChat(chatId);
      if (result) {
        console.log("Chat deleted successfully");
      } else {
        console.log("Chat not found or already deleted");
      }
    } catch (error) {
      console.error("Ошибка при попытке удаления чата:", error);
    }
  });
};
const confirmAndLeaveOrBanMember = (memberId) => {
  const customMessage =
    "Вы уверены? Все сообщения участника будут также удалены из чата (в асинхронном режиме, они исчезнут при следующем заходе в чат).";
  showConfirmationPopup(customMessage, async () => {
    try {
      const result = await handleLeaveOrBanMemberFromChat(chatId, memberId);
      if (result) {
        console.log("User left or banned successfully");
      } else {
        console.log("User is not in the chat or Chat not found");
      }
    } catch (error) {
      console.error("Ошибка при попытке выхода или бана пользователя:", error);
    }
  });
};
const isCurrentUser2 = () => {};
watch(messages, () => {
  nextTick(() => {
    scrollToBottom();
  });
});

const sendMessage = async () => {
  if (!inputMessage.value.trim()) return; // Проверяем, чтобы сообщение не было пустым

  try {
    // Подключаем хранилище пользователя
    const creatorId = userStore.user.id; // ID текущего пользователя
    const response = await addMessage(creatorId, chatId, inputMessage.value);

    inputMessage.value = ""; // Очищаем поле ввода
  } catch (error) {
    console.error("Ошибка отправки сообщения:", error);
  }
};
// Функция для обработки данных SSE
const handleSSEData = async (event) => {
  try {
    const data = event;
    if (data.chatId == chatId) {
      if (data.typeStr == "cID") {
        await chatStore.setFullChats(chatId);
      }
      if (data.typeStr == "uID") {
        const response = await getInfoChat(chatId);
        chatInfo.value = response.chat;
      }
      if (data.typeStr == "dID") {
        router.push({ name: "Home" });
      }
    }

    console.log("Полученные данные:", event); // Для теста выводим в консоль
  } catch (error) {
    console.error("Ошибка при парсинге данных:", event.data);
  }
};

let sseConnection = null;
const isUserInChat = computed(() => {
  if (!chatInfo.value || !chatInfo.value.members) {
    console.log("Не загружен");
    return false;
  }
  return chatInfo.value.members.some((member) => member.user_id === userId);
});
// Открытие соединения с сервером при монтировании компонента
onMounted(async () => {
  scrollToBottom();
  try {
    if (chatId) {
      const response = await getInfoChat(chatId);
      chatInfo.value = response.chat;
    }
  } catch (error) {
    console.error("Ошибка при получении информации о чате:", error);
  }

  try {
    if (chatId) {
      await chatStore.setFullChats(chatId);

      // Создаем соединение и сохраняем его
      sseConnection = createSSEConnection(
        `https://msk-back.te-st.org/sse`, // URL с chatId
        handleSSEData
      );
    }
  } catch (error) {
    console.error("Ошибка при сообщений чата:", error);
  }
});
watch(chatInfo, (newValue) => {
  if (newValue && newValue.members) {
    if (isUserInChat.value) {
      console.log("Пользователь в чате");
    } else {
      router.push({ name: "Home" });
    }
  }
});
onUnmounted(() => {
  if (sseConnection) {
    sseConnection.close();
  }

  exitChatFromCurrentChat();
});
</script>

<style scoped>
.copy_link {
  height: 30px;
  background-color: #fff;
  box-shadow: 0px 1.33px 2.34px 0px #0000002b;
  padding: 6px;
  box-sizing: border-box;
  text-align: center;
  color: #007bff;
  font-size: 12px;
  line-height: 18px;
  font-weight: 500;
}
.member-action {
  display: flex;
  width: 18px;
  height: 18px;
  border: 2px solid #de3a3a;
  border-radius: 50%;
  align-items: center;
  justify-content: center;
}
.member-action::before {
  content: "";
  display: block;
  border: 1px solid #de3a3a;
  width: 6px;
}
.chat_top {
  display: flex;
  background-color: #fff;
  align-items: center;
  box-shadow: 0px 1.33px 2.34px 0px #0000002b;
  margin-bottom: 2.34px;
  flex-wrap: wrap;
}
.chat_top .info {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}
.full_info {
  flex: 1 1 100%;
}
.full_info ul {
  list-style-type: none;
  padding: 0 24px;
}
.full_info ul li {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.full_info ul li .member-info {
  display: flex;
  align-items: center;
  gap: 20px;
}
.member-role {
  color: #007aff;
  text-transform: capitalize;
}
.chat_top .info .info_ava,
.full_info ul li .info_ava {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  overflow: hidden;
}
.chat_top .info h2 {
  font-size: 16px;
  margin: 0;
}
.chat_top .info p {
  margin: 0;
  font-size: 14px;
  font-weight: 400;
  color: #9a9a9a;
}
.chat {
  background-image: url("/public/Background.png");
  background-size: cover;
  width: 100%;
  height: var(--tg-viewport-height);
  display: flex;
  flex-direction: column;
}
.message {
  margin-bottom: 8px;
  display: inline-flex;
}
.message .message-content {
  background-color: #fff;
  flex: 0 1 82%;
  border-radius: 5px 5px 5px 0;
  padding: 8px;
  position: relative;
}
.message.green .message-content {
  background-color: #effedd;
  border-radius: 5px 5px 0px 5px;
}
.message.green .message-content::before {
  transform: rotate(180deg);
  right: -10px;
  left: auto;
  border-top: 10px solid #effedd;
}
.message.green .ava {
  visibility: hidden;
}

.message .message-content strong {
  color: #c9564f;
}
.message .message-content p {
  font-weight: 400;
  font-size: 16px;
  padding: 8px 0;
  margin: 0;
}
.message-content::before {
  content: "";
  position: absolute;
  bottom: 0;
  left: -10px;
  width: 0;
  height: 0;
  border-left: 10px solid transparent;
  border-top: 10px solid rgb(255, 255, 255);
  transform: rotate(90deg);
}
.timestamp {
  display: block;
  text-align: right;
  font-size: 12px;
  color: #a1aab3;
}
.contan_mess {
  padding: 17px;
  flex: 1 1 100%;
  overflow-y: scroll;
}
.message {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}
.message .ava {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  overflow: hidden;
}
.message .ava img {
  display: block;
  width: 100%;
  height: 100%;
}
.message-input {
  background-color: #fff;
  padding: 13px;
  display: flex;
}
.message-input input {
  border: none;
  padding: 10px 10px 10px 5px;
  width: calc(100% - 33px);
  font-size: 16px;
}
.message-input button {
  border: none;
  background: transparent;
}
.message-input input:focus {
  border: 1px solid #007bff; /* Уменьшенная граница для активного поля */
  outline: none; /* Убирает стандартное выделение браузера */
}
span.delete {
  text-align: center;
  display: block;
  background-color: red;
}
</style>
