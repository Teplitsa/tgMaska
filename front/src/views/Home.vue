<template>
  <div id="home">
    <CreateChat />
    <div v-if="hasChats" class="chats">
      <ListItemChat
        v-for="chat in chats"
        :key="chat.chat_id"
        :avatar="chat.avatar"
        :chatName="chat.name"
        :chat_id="chat.chat_id"
        :message_count="chat.message_count"
      />
    </div>

    <!-- Если чатов нет, показываем компонент для создания чата -->
  </div>
</template>
<script setup>
import { useUserStore } from "@/stores/userStore";
import { useChatsStore } from "@/stores/chatsStore";
import ListItemChat from "../components/ListItemChat.vue";
import CreateChat from "../components/CreateChat.vue";
import CreateChatBtn from "../components/CreateChatBtn.vue";
import { ref, computed, onMounted, inject } from "vue";

const chatStore = useChatsStore();
const userStore = useUserStore();

const chats = computed(() => chatStore.chats);
const userTelegramId = ref(null);
const hasChats = computed(() => chats.value.length > 0);
const tg = inject("telegram");
const user = ref(null);

onMounted(async () => {
  try {
    if (tg.initDataUnsafe?.user) {
      user.value = tg.initDataUnsafe.user;
    }
    userTelegramId.value = user.value.id;
  } catch (error) {
    console.error("Ошибка получения user_id Н:", error);
  }
  const telegramId = userTelegramId.value;
  if (telegramId) {
    const chats = await userStore.login(telegramId);
    if (userStore.token) {
      chatStore.setChats(chats);
    }
  } else {
    console.error("Telegram ID не найден Н");
  }
});
</script>
<style scoped>
#home {
  width: 100%;
}
.chats {
  max-height: 90vh;
  overflow-y: auto;
}
</style>