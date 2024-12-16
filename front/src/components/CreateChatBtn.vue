<template>
  <div className="addChat bottom_p" @click="goToChat"><Pen /></div>
</template>

<script setup>
import Pen from "./svg/Pen.vue";
import { ref } from "vue";
import { useRouter } from "vue-router";
const router = useRouter();
const goToChat = () => {
  router.push("/create-chat"); // Переход на страницу чата
};
</script>

<style scoped>
.addChat {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #51aee7;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
