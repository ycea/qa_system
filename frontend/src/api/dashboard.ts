import api from './axios';

export type ProjectStats = {
  total: number;
  open: number;
  closed_week: number;
};

export const fetchDashboard = async (projectId: number): Promise<ProjectStats> => {
  const { data } = await api.get(`/projects/${projectId}/dashboard`);
  return data;
};
