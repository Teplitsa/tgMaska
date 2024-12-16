import axios from "axios";
// Создаем настроенный экземпляр axios
const apiClient = axios.create({
  baseURL: "https://msk-back.te-st.org/", // Ваш базовый URL для API
  headers: {
    "Content-Type": "application/json",
  },
});

export default apiClient;
