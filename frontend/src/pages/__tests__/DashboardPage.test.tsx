import { screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import DashboardPage from '../DashboardPage';

const routeMocks = vi.hoisted(() => ({
  params: { id: '7' } as Record<string, string | undefined>,
}));

vi.mock('react-router-dom', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router-dom')>();
  return { ...actual, useParams: () => routeMocks.params };
});

const dashboardApi = vi.hoisted(() => ({ fetchDashboard: vi.fn() }));
vi.mock('../../api/dashboard', () => dashboardApi);

describe('DashboardPage', () => {
  beforeEach(() => {
    routeMocks.params = { id: '7' };
    dashboardApi.fetchDashboard.mockResolvedValue({ total: 5, open: 3, closed_week: 2 });
  });

  it('показывает виджеты статистики', async () => {
    renderWithProviders(<DashboardPage />);

    expect(await screen.findByText('Всего багов')).toBeInTheDocument();
    expect(screen.getByText('Открытые баги')).toBeInTheDocument();
    expect(screen.getByText('Закрытые за неделю')).toBeInTheDocument();
    expect(screen.getByText('5')).toBeInTheDocument();
    expect(dashboardApi.fetchDashboard).toHaveBeenCalledWith(7);
  });

  it('показывает ошибку при недоступности проекта', async () => {
    dashboardApi.fetchDashboard.mockRejectedValue(new Error('403'));

    renderWithProviders(<DashboardPage />);

    expect(await screen.findByText('Не удалось загрузить статистику.')).toBeInTheDocument();
  });
});
