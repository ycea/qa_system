import api from './axios';

export type MetricType = 'numeric' | 'categorical';

export type Metric = {
  id: number;
  project_id: number;
  name: string;
  type: MetricType;
  options: string[] | null;
  created_at?: string;
  updated_at?: string;
};

export type MetricPayload = {
  name: string;
  type: MetricType;
  options?: string[];
};

export type CategoricalIndicator = { value: string; count: number };

export type StatusIndicator = { status: string; count: number; avg: number | null };

export type MetricAnalytics = {
  metric: { id: number; name: string; type: MetricType; options: string[] | null };
  count: number;
  indicators: Record<string, number> | CategoricalIndicator[];
  by_status: StatusIndicator[];
};

export const fetchMetrics = async (projectId: number): Promise<Metric[]> => {
  const { data } = await api.get(`/projects/${projectId}/metrics`);
  return data.items;
};

export const createMetric = async (
  projectId: number,
  payload: MetricPayload,
): Promise<{ message: string; metric: Metric }> => {
  const { data } = await api.post(`/projects/${projectId}/metrics`, payload);
  return data;
};

export const updateMetric = async (
  projectId: number,
  metricId: number,
  payload: MetricPayload,
): Promise<{ message: string; metric: Metric }> => {
  const { data } = await api.put(`/projects/${projectId}/metrics/${metricId}`, payload);
  return data;
};

export const deleteMetric = async (projectId: number, metricId: number): Promise<void> => {
  await api.delete(`/projects/${projectId}/metrics/${metricId}`);
};

export const setMetricValue = async (
  projectId: number,
  bugId: number,
  metricId: number,
  payload: { numeric_value?: number; categorical_value?: string },
): Promise<{ message: string; metric_value: unknown }> => {
  const { data } = await api.put(
    `/projects/${projectId}/bugs/${bugId}/metrics/${metricId}`,
    payload,
  );
  return data;
};

export const fetchAnalytics = async (projectId: number): Promise<MetricAnalytics[]> => {
  const { data } = await api.get(`/projects/${projectId}/metrics/analytics`);
  return data.items;
};

export const metricExportUrl = (projectId: number): string =>
  `/api/projects/${projectId}/metrics/export`;
