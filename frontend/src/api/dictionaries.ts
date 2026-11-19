import api from './axios';

export type DictionaryType = 'statuses' | 'priorities' | 'severities';

export type DictionaryItem = {
  id: number;
  project_id: number;
  name: string;
  is_default?: boolean;
  in_use: boolean;
};

export const fetchDictionaries = async (
  projectId: number,
  type: DictionaryType,
): Promise<DictionaryItem[]> => {
  const { data } = await api.get(`/projects/${projectId}/dictionaries/${type}`);
  return data.items;
};

export const createDictionaryItem = async (
  projectId: number,
  type: DictionaryType,
  name: string,
): Promise<DictionaryItem> => {
  const { data } = await api.post(`/projects/${projectId}/dictionaries/${type}`, { name });
  return data.item;
};

export const updateDictionaryItem = async (
  projectId: number,
  type: DictionaryType,
  id: number,
  name: string,
): Promise<DictionaryItem> => {
  const { data } = await api.put(`/projects/${projectId}/dictionaries/${type}/${id}`, { name });
  return data.item;
};

export const deleteDictionaryItem = async (
  projectId: number,
  type: DictionaryType,
  id: number,
): Promise<void> => {
  await api.delete(`/projects/${projectId}/dictionaries/${type}/${id}`);
};
