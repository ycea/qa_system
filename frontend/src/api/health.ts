import api from './axios';

export const fetchHealth = async () => {
  const { data } = await api.get('/health');
  return data;
};
