import api from './axios';

export type MemberRole = 'admin' | 'developer' | 'tester';

export type Member = {
  id: number;
  name: string;
  email: string;
  role: MemberRole;
};

export type MembersResponse = {
  members: Member[];
  can_manage: boolean;
};

export const fetchMembers = async (projectId: number): Promise<MembersResponse> => {
  const { data } = await api.get(`/projects/${projectId}/members`);
  return data;
};

export const addMember = async (
  projectId: number,
  payload: { name: string; role: MemberRole },
): Promise<{ message: string; member: Member }> => {
  const { data } = await api.post(`/projects/${projectId}/members`, payload);
  return data;
};

export const removeMember = async (projectId: number, userId: number): Promise<void> => {
  await api.delete(`/projects/${projectId}/members/${userId}`);
};
