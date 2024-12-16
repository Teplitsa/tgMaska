<script setup>
import { useUserStore } from "./stores/userStore";
import { useChatsStore } from "./stores/chatsStore";
import { ref, onMounted, inject } from "vue";
import { useRouter } from "vue-router";

const userStore = useUserStore();
const chatsStore = useChatsStore();
const tg = inject("telegram");
const userTelegramId = ref(null);
const invitationParams = ref(false);
const user = ref(null);
const router = useRouter();

onMounted(async () => {
  try {
    tg.expand();
    if (tg.initDataUnsafe?.user) {
      user.value = tg.initDataUnsafe.user;
    }
    userTelegramId.value = user.value.id;
  } catch (error) {
    console.error("Ошибка получения user_id:", error);
  }
  const telegramId = userTelegramId.value;

  if (telegramId) {
    const chats = await userStore.login(telegramId);
    if (userStore.token) {
      chatsStore.setChats(chats);
    }
  } else {
    console.error("Telegram ID не найден");
  }
  if (tg.initDataUnsafe?.start_param) {
    const inviteStr = tg.initDataUnsafe?.start_param.split("invation=")[1];
    router.push({ name: "Join", params: { invite: inviteStr } });
  }
});
</script>

<template>
  <router-view></router-view>
</template>

<style scoped>
</style>
