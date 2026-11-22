import { describe, expect, it, vi } from 'vitest';
import { fetchReport } from '../reports';

const apiMock = vi.hoisted(() => ({ get: vi.fn() }));

vi.mock('../axios', () => ({ default: apiMock }));

describe('api/reports', () => {
  it('fetchReport передаёт project_id и period', async () => {
    const report = {
      period: 'week',
      total: 4,
      closed: 2,
      closed_percent: 50,
      avg_lifetime_hours: 12.5,
    };
    apiMock.get.mockResolvedValue({ data: report });

    await expect(fetchReport(7, 'week')).resolves.toEqual(report);
    expect(apiMock.get).toHaveBeenCalledWith('/reports', {
      params: { project_id: 7, period: 'week' },
    });
  });
});
