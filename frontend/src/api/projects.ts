import api from './axios';

export type Project = {
  id: number;
  name: string;
  description: string | null;
  owner_id: number;
  bugs_count: number;
  created_at: string;
  updated_at: string;
};

export const fetchProjects = async (): Promise<Project[]> => {
  const { data } = await api.get('/projects');
  return data;
};

export const fetchProject = async (id: number): Promise<Project> => {
  const { data } = await api.get(`/projects/${id}`);
  return data;
};

export const createProject = async (data: {
  name: string;
  description?: string;
}): Promise<{ message: string; project: Project }> => {
  const { data: responseData } = await api.post('/projects', data);
  return responseData;
};
export const deleteProject = async (id: number): Promise<void> => {
  await api.delete(`/projects/${id}`);
};
