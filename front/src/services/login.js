import apiClient from "./apiClient";
export const loginUser = async (telegramId) => {
  const formData = new FormData();
  formData.append("telegram_id", telegramId);

  try {
    const response = await apiClient.post("/login", formData);
    return response.data;
  } catch (error) {
    console.error("Login request failed:", error);
    throw error;
  }
};
