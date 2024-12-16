<template>
  <div className="block-start">
    <div className="block-start__img-block">
      <img
        className="block-start__img"
        src="../../public/start-img.png"
        alt=""
      />
    </div>
    <div className="block-start__about-block">
      <p className="title">Анонимные чаты</p>
      <div className="text">
        Без использования Telegram iD и хранения ваших данных
      </div>
      <button className="button button-little" @click="goToChat">
        Создать чат
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
const router = useRouter();
const goToChat = () => {
  router.push("/create-chat"); // Переход на страницу чата
};
</script>


<style scoped>
.block-start {
  background-color: var(--white);
  border: 1px solid var(--light-black);
  padding: 20px;
  display: flex;
  gap: 20px;
  border-radius: var(--border-radius-little);
  margin: 32px 21px;
}

.block-start__title {
  font-family: "Roboto", sans-serif;
  font-size: 16px;
  font-weight: 600;
  line-height: 24px;
  letter-spacing: 0.15000000596046448px;
  text-underline-position: from-font;
  text-decoration-skip-ink: none;
}

.block-start__about-block .title {
  margin: 0;
  font-weight: 600;
}

.block-start__about-block .text {
  line-height: 22px;
  margin-bottom: 12px;
  color: var(--text-dark-grey);
}

@media screen and (max-width: 330px) {
  .block-start {
    margin: 21px 15px 0 15px;
    padding: 15px;
    gap: 15px;
  }
}
</style>