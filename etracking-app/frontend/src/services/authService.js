import api from './api';

export const authService = {
  login: async (email, password) => {
    const { data } = await api.post('/auth/login', { email, password });
    return data.data;
  },
  logout: async () => {
    await api.post('/auth/logout');
  },
  me: async () => {
    const { data } = await api.get('/auth/me');
    return data.data;
  },
};
