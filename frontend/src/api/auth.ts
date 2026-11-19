import axios from 'axios';
import api from './axios';

export type User = {
  id: number;
  name: string;
  email: string;
};

export type AuthResponse = {
  message: string;
  user: User;
};

export const csrf = async () => {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
};

export const login = async (data: { name: string; password: string }) => {
  await csrf();
  const response = await api.post<AuthResponse>('/login', data);
  return response.data;
};

export const register = async (data: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}) => {
  await csrf();
  const response = await api.post<AuthResponse>('/register', data);
  return response.data;
};

export const logout = async () => {
  await api.post('/logout');
};

export const me = async () => {
  const response = await api.get<User>('/me');
  return response.data;
};
