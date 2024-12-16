<template>
  <div className="createChat">
    <!-- Блок с информацией о чате -->
    <div className="chatInfo">
      <div className="input-group">
        <input
          type="text"
          id="chatName"
          v-model="chatName"
          placeholder="Введите название чата"
        />
      </div>
      <div className="input-group">
        <input
          type="text"
          id="nickname"
          v-model="nickname"
          placeholder="Введите свой никнейм"
        />
      </div>
    </div>

    <!-- Блок с типом чата и ссылкой-приглашением -->
    <div className="chatType">
      <div className="input-group">
        <span className="inviteLink">Ссылка-приглашение:</span>
      </div>
      <div v-if="!linkGenerated" className="radio-group">
        <div>
          <label for="oneTimeChat">
            <input
              type="radio"
              id="oneTimeChat"
              v-model="chatType"
              value="private"
            />
            Одноразовая (тет-а-тет)
          </label>
        </div>
        <div>
          <label for="recurrentChat">
            <input
              type="radio"
              id="recurrentChat"
              v-model="chatType"
              value="group"
            />
            Многоразовая
          </label>
        </div>
        <div>
          <button @click="generateInviteLink">Сгенерировать</button>
        </div>
      </div>
      <div v-else className="link-block">
        <div className="link">
          <span>{{ inviteLink }}</span>
          <Refresh v-if="chatType === 'group'" @click="refreshInviteLink" />
        </div>
        <div>
          <button @click="copyToClipboard">Скопировать</button>
        </div>
      </div>
    </div>
    <!-- Блок с кнопкой для перехода на страничку чата -->
    <GoChat v-if="isFin" @click="handleGoChat" />
  </div>
</template>

<script setup>
import { ref } from "vue";
import Refresh from "../components/svg/Refresh.vue";
import { useUserStore } from "../stores/userStore";
import { refreshChatLink, createChat } from "../services/Chat";
import { useRouter } from "vue-router";
import GoChat from "../components/GoChat.vue";

const chatName = ref("");
const nickname = ref("");
const inviteLink = ref("ddd");
const chatType = ref("private");
const linkGenerated = ref(false);
const isFin = ref(false);
const chatId = ref(null);
const userStore = useUserStore();
const router = useRouter();

const handleGoChat = () => {
  router.push({ name: "Chat", params: { id: chatId.value } });
};
const generateInviteLink = async () => {
  try {
    const creatorId = userStore.user.id;
    const result = await createChat(
      creatorId,
      nickname.value,
      chatName.value,
      chatType.value
    );

    inviteLink.value = result.chat.inviteLink;
    chatId.value = result.chat.id;
    linkGenerated.value = true;
    isFin.value = true;
  } catch (error) {
    console.error("Ошибка создания чата:", error);
    alert("Не удалось создать чат. Попробуйте снова.");
  }
};
// Обновление ссылки
const refreshInviteLink = async () => {
  try {
    const creatorId = userStore.user.id; // ID пользователя из хранилища
    const result = await refreshChatLink(creatorId, chatId.value);

    inviteLink.value = result.inviteLink; // Устанавливаем новую ссылку
    alert("Ссылка успешно обновлена!");
  } catch (error) {
    console.error("Ошибка обновления ссылки:", error);
    alert("Не удалось обновить ссылку. Попробуйте снова.");
  }
};
const copyToClipboard = () => {
  navigator.clipboard.writeText(inviteLink.value).then(
    () => {
      alert("Ссылка скопирована!");
    },
    (err) => {
      console.error("Ошибка копирования:", err);
    }
  );
};
</script>

<style scoped>
.createChat {
  height: 100%;
}
.createChat > div {
  background-color: #fff;
  padding: 12px 24px 24px;
  margin-bottom: 8px;
}
.createChat > div input[type="text"] {
  width: 100%;
  padding: 8px;
  margin: 8px 0;
  box-sizing: border-box;
  font-size: 16px;
  line-height: 24px;
  border: none;
  border-bottom: 1px solid #000;
}
.createChat .radio-group > div {
  padding: 6px 0;
}
.inviteLink {
  color: #50a7ea;
  font-weight: 500;
}
.createChat > div button {
  background-color: #50a7ea;
  color: #fff;
  border: none;
  display: block;
  width: 100%;
  padding: 12px 0;
  border-radius: 7px;
  margin-top: 12px;
}
.link-block .link {
  display: flex;
  align-items: center;
  border: 1.8px solid #0000000d;
  justify-content: space-between;
  border-radius: 14px;
  padding: 12px;
  margin-top: 16px;
}
</style>