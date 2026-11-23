import api from './axios';

export type ReportPeriod = 'day' | 'week' | 'month';

export type ProjectReport = {
  period: ReportPeriod;
  total: number;
  closed: number;
  closed_percent: number;
  avg_lifetime_hours: number;
};

export const fetchReport = async (
  projectId: number,
  period: ReportPeriod,
): Promise<ProjectReport> => {
  const { data } = await api.get('/reports', { params: { project_id: projectId, period } });
  return data;
};
