import api from './axios';

export type BugStatus = {
  id: number;
  project_id: number;
  name: string;
  is_default?: boolean;
  in_use?: boolean;
};

export type BugDictionaryRef = {
  id: number;
  project_id: number;
  name: string;
  in_use?: boolean;
};

export type BugUserRef = {
  id: number;
  name: string;
};

export type Bug = {
  id: number;
  project_id: number;
  status_id: number;
  priority_id: number;
  severity_id: number;
  assignee_id: number | null;
  created_by: number;
  title: string;
  description: string | null;
  steps_to_reproduce: string | null;
  closed_at: string | null;
  status?: BugStatus;
  priority?: BugDictionaryRef;
  severity?: BugDictionaryRef;
  assignee?: BugUserRef | null;
  created_at: string;
  updated_at: string;
};

export type BugHistoryItem = {
  id: number;
  action: string;
  field: string | null;
  old_value: string | null;
  new_value: string | null;
  created_at: string;
  user?: BugUserRef;
};

export type BugDetails = Bug & { histories: BugHistoryItem[] };

export type BugFilters = {
  search?: string;
  status_id?: number | '';
  priority_id?: number | '';
  severity_id?: number | '';
  assignee_id?: number | '';
};

export type BugPayload = {
  title: string;
  description: string;
  steps_to_reproduce: string;
  priority_id: number | '';
  severity_id: number | '';
  status_id?: number | '';
  assignee_id?: number | '';
};

export type BugMutationResponse = {
  message: string;
  bug: Bug;
};

/** Убирает пустые фильтры, чтобы не слать пустые query-параметры. */
const cleanParams = (filters: BugFilters): Record<string, string | number> =>
  Object.fromEntries(
    Object.entries(filters).filter(
      ([, value]) => value !== '' && value !== undefined && value !== null,
    ),
  ) as Record<string, string | number>;

export const fetchBugs = async (projectId: number, filters: BugFilters = {}): Promise<Bug[]> => {
  const { data } = await api.get(`/projects/${projectId}/bugs`, { params: cleanParams(filters) });
  return data;
};

export const fetchBug = async (projectId: number, bugId: number): Promise<BugDetails> => {
  const { data } = await api.get(`/projects/${projectId}/bugs/${bugId}`);
  return data;
};

export const createBug = async (
  projectId: number,
  payload: BugPayload,
): Promise<BugMutationResponse> => {
  const { data } = await api.post(`/projects/${projectId}/bugs`, payload);
  return data;
};

export const updateBug = async (
  projectId: number,
  bugId: number,
  payload: BugPayload,
): Promise<BugMutationResponse> => {
  const { data } = await api.put(`/projects/${projectId}/bugs/${bugId}`, payload);
  return data;
};

export const changeBugStatus = async (
  projectId: number,
  bugId: number,
  statusId: number,
): Promise<BugMutationResponse> => {
  const { data } = await api.patch(`/projects/${projectId}/bugs/${bugId}/status`, {
    status_id: statusId,
  });
  return data;
};

export const assignBug = async (
  projectId: number,
  bugId: number,
  assigneeId: number | null,
): Promise<BugMutationResponse> => {
  const { data } = await api.patch(`/projects/${projectId}/bugs/${bugId}/assignee`, {
    assignee_id: assigneeId,
  });
  return data;
};

export const fetchBugHistory = async (
  projectId: number,
  bugId: number,
): Promise<BugHistoryItem[]> => {
  const { data } = await api.get(`/projects/${projectId}/bugs/${bugId}/history`);
  return data.items;
};
