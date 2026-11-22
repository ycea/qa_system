import api from './axios';
import type { DictionaryItem } from './dictionaries';
import type { MemberRole } from './members';

export type ProjectMetaMember = {
  id: number;
  name: string;
  email: string;
  role: MemberRole;
};

export type ProjectMeta = {
  members: ProjectMetaMember[];
  statuses: DictionaryItem[];
  priorities: DictionaryItem[];
  severities: DictionaryItem[];
};

export const fetchProjectMeta = async (projectId: number): Promise<ProjectMeta> => {
  const { data } = await api.get(`/projects/${projectId}/meta`);
  return data;
};
