<template>
  <div className="list-item-chat" @click="handleGoChat">
    <div className="img_chat"><img :src="avatar" alt="Chat Avatar" /></div>
    <div className="block-title">
      <span className="title">{{ chatName || "Анонимный чат" }}</span>
      <span><img src="../../public/lock.png" alt="lock" /></span>
      <span class="countNew" v-if="message_count > 0">{{
        message_count > 10 ? "10+" : message_count
      }}</span>
    </div>
  </div>
</template>
<script setup>
import { defineProps } from "vue";
import { useRouter } from "vue-router";
const router = useRouter();
const props = defineProps({
  avatar: {
    type: String,
    required: true,
  },
  chatName: {
    type: String,
    default: "",
  },
  chat_id: {},
  message_count: {
    type: Number,
    default: 0,
  },
});
const handleGoChat = () => {
  router.push({ name: "Chat", params: { id: props.chat_id } });
};
</script>
<style scoped>
.list-item-chat {
  display: flex;
  background-color: #fff;
  padding: 10px 0 9px 10px;
  gap: 11px;
  align-items: center;
}
.img_chat {
  border-radius: 50%;
  overflow: hidden;
}
span.countNew {
  background: red;
  color: #fff;
  line-height: 1em;
  font-size: 12px;
  padding: 3px;
  border-radius: 5px;
}
.list-item-chat .block-title {
  align-self: stretch;
  flex: 1;
  border-bottom: 0.35px solid #d9d9d9;
  display: flex;
  gap: 11px;
  align-items: center;
}

.list-item-chat .block-title .title {
  font-size: 16px;
  font-weight: 500;
  line-height: 19px;
  color: var(--dark-black);
}

.list-item-chat .block-title .member-nick {
  font-size: 14.78px;
  font-weight: 500;
  line-height: 17.32px;
  color: var(--very-light-black);
}

.list-item-chat .block-title .last-message {
  font-size: 15px;
  font-weight: 400;
  line-height: 18px;
  color: var(--text-dark-grey);
}

.list-item-chat .time {
  font-size: 13px;
  font-weight: 400;
  line-height: 15px;
  color: #95999a;
  position: absolute;
  right: 13px;
}
</style>