import api from './axios';

export type BugComment = {
  id: number;
  bug_id: number;
  user_id: number;
  body: string;
  created_at: string;
  updated_at: string;
  user?: { id: number; name: string };
};

export const fetchComments = async (projectId: number, bugId: number): Promise<BugComment[]> => {
  const { data } = await api.get(`/projects/${projectId}/bugs/${bugId}/comments`);
  return data.items;
};

export const addComment = async (
  projectId: number,
  bugId: number,
  body: string,
): Promise<{ message: string; comment: BugComment }> => {
  const { data } = await api.post(`/projects/${projectId}/bugs/${bugId}/comments`, { body });
  return data;
};
