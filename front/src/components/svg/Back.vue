<template>
  <div class="back" @click="goHome">
    <svg
      width="18"
      height="18"
      viewBox="0 0 18 18"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        d="M17.2963 7.92131H4.61922L10.4421 2.09839L8.96297 0.629639L0.629639 8.96297L8.96297 17.2963L10.4317 15.8276L4.61922 10.0046H17.2963V7.92131Z"
        fill="#333333"
      />
    </svg>
  </div>
</template>
  
  <script setup>
import { useRouter } from "vue-router";

const router = useRouter();

const goHome = () => {
  router.push({ name: "Home" }); // Переход на домашнюю страницу
};
</script>
  
  <style scoped>
.back {
  height: 55px;
  width: 55px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
  