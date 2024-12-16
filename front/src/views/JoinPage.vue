<template>
  <div class="join">
    <div class="overlay">
      <div class="join_form">
        <div class="form_title">
          <div class="title_icon">
            <img src="../../public/lock.png" alt="" />
          </div>
          <div class="title_text">
            <span class="title">Войти в чат</span>
            <span class="subtitle">Введите свой псевдоним</span>
          </div>
        </div>
        <div class="form">
          <input
            v-model="nickname"
            type="text"
            placeholder="Введите свой псевдоним"
            required
          />
          <div class="error" v-if="error">
            {{ error }}
          </div>
        </div>
      </div>
    </div>
    <div class="accept_btn">
      <button @click="accept">Accept</button>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue";
import { useUserStore } from "@/stores/userStore";
import { FirstJoin, checkChatMember } from "../services/Chat";
import { useChatsStore } from "@/stores/chatsStore";
import { useRoute, useRouter } from "vue-router";
const router = useRouter();
const nickname = ref("");
const error = ref(null);

const userStore = useUserStore();
const chatsStore = useChatsStore();
const { invite } = useRoute().params;

onMounted(async () => {
  if (!invite) {
    console.log("Отсутствует код приглашения, перенаправляем на главную.");
    router.push({ name: "Home" });
  }
  const isMember = await checkChatMember(invite, userStore.user.id);
  if (isMember) {
    console.log("Вы уже являетесь участником этого чата.");
    router.push({ name: "Chat", params: { id: isMember.id } });
  }
});

const accept = async () => {
  if (!nickname.value.trim()) {
    error.value = "Псевдоним не может быть пустым";
    return;
  }
  error.value = null;
  try {
    const memberId = userStore.user.id;
    const response = await FirstJoin(invite, memberId, nickname.value);
    chatsStore.updateOrAddChat(response.chat);
    console.log("Успешно вошли в чат:", response);
    router.push({ name: "Chat", params: { id: response.chat.chat_id } });
  } catch (err) {
    error.value = "Ошибка при входе в чат";
    console.error("Ошибка при входе в чат:", err);
  }
};
</script>

<style scoped>
.join {
  height: var(--tg-viewport-height);
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  flex-direction: column;
}
.overlay {
  background-color: rgba(0, 0, 0, 0.2);
  flex: 1 1 100%;
  align-self: stretch;
  display: flex;
  align-items: center;
  justify-content: center;
}
.join_form {
  background-color: #fff;
  border-radius: 5.5px;
  width: 65%;
}
.form_title {
  display: flex;
  align-items: center;
  border-bottom: 1px solid #00000020;
}
.form {
  padding: 20px;
}
.form input {
  border: 1px solid #00000020;
  padding: 12px;
  width: 100%;
  box-sizing: border-box;
  border-radius: 14px;
  font-size: 16px;
  line-height: 24px;
}
input::placeholder {
  color: #3c3c434a;
}
.title_icon {
  width: 55px;
  height: 55px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.title_text {
  padding: 9px 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.title {
  font-size: 16px;
  font-weight: 500;
  color: #222;
}
.subtitle {
  font-size: 12px;
  color: #888;
}
.accept_btn {
  width: 100%;
}
.accept_btn button {
  width: 100%;
  height: 50px;
  background-color: #60c255;
  color: white;
  font-size: 18px;
  border: none;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
</style>