import apiClient from "./apiClient";
import { useUserStore } from "../stores/userStore";

export const createChat = async (creatorId, nick, name, type) => {
  const formData = new FormData();
  formData.append("creatorId", creatorId);
  formData.append("nick", nick);
  formData.append("name", name);
  formData.append("type", type);

  try {
    const userStore = useUserStore();
    const token = userStore.token;

    const response = await apiClient.post("/create_chat", formData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
    return response.data; // Возвращаем результат, если он есть
  } catch (error) {
    console.error("Create chat request failed:", error);
    throw error; // Пробрасываем ошибку для обработки выше
  }
};
export const refreshChatLink = async (creatorId, chatId) => {
  const formData = new FormData();
  formData.append("creatorId", creatorId);
  formData.append("chatId", chatId);

  try {
    const userStore = useUserStore();
    const token = userStore.token;
    const response = await apiClient.post("/update_link_chat", formData, {
      headers: {
        Authorization: `Bearer ${token}`, // Используем токен из localStorage
      },
    });
    return response.data; // Возвращаем результат с новой ссылкой
  } catch (error) {
    console.error("Ошибка обновления ссылки чата:", error);
    throw error; // Пробрасываем ошибку для обработки выше
  }
};

export const getInfoChat = async (chatId) => {
  const formData = new FormData();
  formData.append("chatId", chatId);

  try {
    const userStore = useUserStore();
    const token = userStore.token;
    const response = await apiClient.post("/get_info_chat", formData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
    return response.data;
  } catch (error) {
    console.error("Ошибка получиния информации о чате:", error);
    throw error;
  }
};
export const FirstJoin = async (inviteCode, memberId, nick) => {
  const formData = new FormData();
  formData.append("inviteCode", inviteCode);
  formData.append("memberId", memberId);
  formData.append("nick", nick);

  try {
    const userStore = useUserStore();
    const token = userStore.token;
    const response = await apiClient.post("/first_join_chat", formData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
    return response.data;
  } catch (error) {
    console.error("Ошибка пришедшего чата:", error);
    throw error;
  }
};

export const checkChatMember = async (inviteLink, memberId) => {
  const formData = new FormData();
  formData.append("invite_link", inviteLink);
  formData.append("memberId", memberId);
  try {
    const userStore = useUserStore();
    const token = userStore.token;
    const response = await apiClient.post("/has_in_chat", formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/x-www-form-urlencoded",
      },
    });

    if (response.status === 200) {
      return response.data;
    }

    if (response.status === 204) {
      return false;
    }
  } catch (error) {
    console.error("Ошибка:", error);
    throw error; // Пробрасываем ошибку дальше, чтобы она могла быть обработана
  }
};

export const exitChat = async (chatId, memberId) => {
  const formData = new FormData();
  formData.append("chatId", chatId);
  formData.append("memberId", memberId);

  try {
    const userStore = useUserStore();
    const token = userStore.token;

    const response = await apiClient.post("/exit_chat", formData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    return response.data;
  } catch (error) {
    console.error("Ошибка при выходе из чата:", error);
    throw error; // Пробрасываем ошибку дальше, чтобы она могла быть обработана
  }
};

export const addMessage = async (creatorId, chatId, message) => {
  const formData = new FormData();
  formData.append("creatorId", creatorId);
  formData.append("chatId", chatId);
  formData.append("message", message);
  try {
    const userStore = useUserStore();
    const token = userStore.token;
    const response = await apiClient.post("/add_mess", formData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
    return response.data;
  } catch (error) {
    console.error("Ошибка написания сообщения чата:", error);
    throw error;
  }
};

export const getMessages = async (chatId) => {
  const formData = new FormData();

  formData.append("chatId", chatId);

  try {
    const userStore = useUserStore();
    const token = userStore.token;
    const response = await apiClient.post("/get_mess", formData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
    return response.data;
  } catch (error) {
    console.error("Ошибка написания сообщения чата:", error);
    throw error;
  }
};

export const handleLeaveOrBanMemberFromChat = async (chatId, memberId) => {
  const formData = new FormData();
  formData.append("chatId", chatId);
  formData.append("memberId", memberId);

  try {
    const userStore = useUserStore();
    const token = userStore.token;
    const response = await apiClient.post("/del_member", formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/x-www-form-urlencoded",
      },
    });

    if (response.status === 200) {
      console.log("User left or banned successfully");
      return response.data;
    }

    if (response.status === 404) {
      console.error("User is not in the chat or Chat not found");
      return false;
    }
  } catch (error) {
    console.error("Error:", error);
    throw error; // Пробрасываем ошибку дальше, чтобы она могла быть обработана
  }
};
export const handleDeleteChat = async (chatId) => {
  const formData = new FormData();
  formData.append("chatId", chatId);

  try {
    const userStore = useUserStore();
    const token = userStore.token;
    const response = await apiClient.post("/del_chat", formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/x-www-form-urlencoded",
      },
    });

    if (response.status === 200) {
      console.log("User left or banned successfully");
      return response.data;
    }

    if (response.status === 404) {
      console.error("User is not in the chat or Chat not found");
      return false;
    }
  } catch (error) {
    console.error("Error:", error);
    throw error; // Пробрасываем ошибку дальше, чтобы она могла быть обработана
  }
};
