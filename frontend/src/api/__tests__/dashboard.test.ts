import { describe, expect, it, vi } from 'vitest';
import { fetchDashboard } from '../dashboard';

const apiMock = vi.hoisted(() => ({ get: vi.fn() }));

vi.mock('../axios', () => ({ default: apiMock }));

describe('api/dashboard', () => {
  it('fetchDashboard возвращает статистику проекта', async () => {
    apiMock.get.mockResolvedValue({ data: { total: 4, open: 2, closed_week: 1 } });

    await expect(fetchDashboard(7)).resolves.toEqual({ total: 4, open: 2, closed_week: 1 });
    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/dashboard');
  });
});
