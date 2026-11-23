import { describe, expect, it, vi } from 'vitest';
import {
  createMetric,
  deleteMetric,
  fetchAnalytics,
  fetchMetrics,
  metricExportUrl,
  setMetricValue,
  updateMetric,
} from '../metrics';

const apiMock = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}));

vi.mock('../axios', () => ({ default: apiMock }));

const metric = {
  id: 3,
  project_id: 7,
  name: 'Время',
  type: 'numeric' as const,
  options: null,
};

describe('api/metrics', () => {
  it('fetchMetrics возвращает список метрик', async () => {
    apiMock.get.mockResolvedValue({ data: { items: [metric] } });

    await expect(fetchMetrics(7)).resolves.toEqual([metric]);
    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/metrics');
  });

  it('createMetric отправляет name/type/options', async () => {
    apiMock.post.mockResolvedValue({ data: { message: 'Метрика создана.', metric } });

    await createMetric(7, { name: 'Время', type: 'numeric' });

    expect(apiMock.post).toHaveBeenCalledWith('/projects/7/metrics', {
      name: 'Время',
      type: 'numeric',
    });
  });

  it('updateMetric обновляет метрику', async () => {
    apiMock.put.mockResolvedValue({ data: { message: 'ok', metric } });

    await updateMetric(7, 3, { name: 'Время', type: 'numeric' });

    expect(apiMock.put).toHaveBeenCalledWith('/projects/7/metrics/3', {
      name: 'Время',
      type: 'numeric',
    });
  });

  it('deleteMetric удаляет метрику', async () => {
    apiMock.delete.mockResolvedValue({});

    await deleteMetric(7, 3);

    expect(apiMock.delete).toHaveBeenCalledWith('/projects/7/metrics/3');
  });

  it('setMetricValue отправляет значение метрики на баге', async () => {
    apiMock.put.mockResolvedValue({ data: { message: 'ok', metric_value: { id: 1 } } });

    await setMetricValue(7, 5, 3, { numeric_value: 12.5 });

    expect(apiMock.put).toHaveBeenCalledWith('/projects/7/bugs/5/metrics/3', {
      numeric_value: 12.5,
    });
  });

  it('fetchAnalytics возвращает показатели', async () => {
    apiMock.get.mockResolvedValue({ data: { items: [{ count: 2 }] } });

    await expect(fetchAnalytics(7)).resolves.toEqual([{ count: 2 }]);
    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/metrics/analytics');
  });

  it('metricExportUrl строит ссылку на экспорт', () => {
    expect(metricExportUrl(7)).toBe('/api/projects/7/metrics/export');
  });
});
