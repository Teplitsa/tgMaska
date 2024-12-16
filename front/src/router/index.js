import { createRouter, createWebHistory } from "vue-router";
import HomeLayout from "@/views/HomeLayout.vue";
import Chat from "@/views/Chat.vue";
import CreateChatPage from "@/views/CreateChatPage.vue";

const initTelegram = () => {
  return new Promise((resolve, reject) => {
    const telegram = window.Telegram.WebApp;
    if (telegram) {
      resolve(telegram);
    } else {
      reject("Telegram SDK не доступен");
    }
  });
};
const routes = [
  {
    path: "/",
    component: HomeLayout,
    children: [
      {
        path: "",
        name: "Home",
        component: () => import("../views/Home.vue"),
      },
      {
        path: "/chat/:id",
        name: "Chat",
        component: Chat,
        props: true,
      },
      {
        path: "/create-chat",
        name: "CreateChat",
        component: CreateChatPage,
      },
      {
        path: "/join/:invite",
        name: "Join",
        component: () => import("@/views/JoinPage.vue"),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
