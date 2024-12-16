import { defineStore } from "pinia";
import { loginUser } from "@/services/login";
import { jwtDecode } from "jwt-decode";

export const useUserStore = defineStore("login", {
  state: () => ({
    token: sessionStorage.getItem("token") || null,
    user: JSON.parse(sessionStorage.getItem("user")) || null,
    error: null,
    tgId: null,
    invite: "",
  }),
  actions: {
    async login(telegramId) {
      try {
        const result = await loginUser(telegramId);
        this.token = result.jwt;
        this.user = result.user;
        this.tgId = telegramId;

        sessionStorage.setItem("token", result.jwt);
        sessionStorage.setItem("user", JSON.stringify(result.user));

        this.error = null;
        return result.chats;
      } catch (error) {
        this.token = null;
        this.user = null;
        this.error = error.message;
        console.error("Login failed:", error.message);
      }
    },
    setInvite(invite) {
      this.invite = invite;
    },
    logout() {
      // Очистка данных и удаление из localStorage
      this.token = null;
      this.user = null;
      this.error = null;
      localStorage.removeItem("token");
      localStorage.removeItem("user");
    },
    isTokenExpired(token) {
      try {
        const decoded = jwtDecode(token);
        const currentTime = Date.now() / 1000;
        return decoded.exp < currentTime;
      } catch (error) {
        return true;
      }
    },
  },
});
