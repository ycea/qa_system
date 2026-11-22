import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createAuthValue, renderWithProviders } from '../../test/test-utils';
import MetricsPage from '../MetricsPage';

const routeMocks = vi.hoisted(() => ({
  navigate: vi.fn(),
  params: { id: '7' } as Record<string, string | undefined>,
}));

vi.mock('react-router-dom', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router-dom')>();
  return {
    ...actual,
    useNavigate: () => routeMocks.navigate,
    useParams: () => routeMocks.params,
  };
});

const metricsApi = vi.hoisted(() => ({
  fetchMetrics: vi.fn(),
  fetchAnalytics: vi.fn(),
  createMetric: vi.fn(),
  updateMetric: vi.fn(),
  deleteMetric: vi.fn(),
  setMetricValue: vi.fn(),
  metricExportUrl: vi.fn((id: number) => `/api/projects/${id}/metrics/export`),
}));
vi.mock('../../api/metrics', () => metricsApi);

const projectsApi = vi.hoisted(() => ({ fetchProject: vi.fn() }));
vi.mock('../../api/projects', () => projectsApi);

const metric = { id: 3, project_id: 7, name: 'Время', type: 'numeric' as const, options: null };

const analytics = [
  {
    metric: { id: 3, name: 'Время', type: 'numeric' as const, options: null },
    count: 2,
    indicators: { avg: 15, min: 10, max: 20, sum: 30 },
    by_status: [],
  },
];

const projectAdmin = {
  id: 7,
  name: 'P',
  description: null,
  owner_id: 1,
  bugs_count: 0,
  members: [
    {
      id: 1,
      name: 'owner',
      email: 'o@e.com',
      pivot: { project_id: 7, user_id: 1, role: 'admin' as const },
    },
  ],
  created_at: '',
  updated_at: '',
};

const projectDev = {
  ...projectAdmin,
  members: [
    {
      id: 2,
      name: 'dev',
      email: 'd@e.com',
      pivot: { project_id: 7, user_id: 2, role: 'developer' as const },
    },
  ],
};

describe('MetricsPage', () => {
  beforeEach(() => {
    routeMocks.params = { id: '7' };
    metricsApi.fetchMetrics.mockResolvedValue([metric]);
    metricsApi.fetchAnalytics.mockResolvedValue(analytics);
    projectsApi.fetchProject.mockResolvedValue(projectAdmin);
  });

  it('показывает список метрик и раздел аналитики администратору', async () => {
    renderWithProviders(<MetricsPage />, {
      auth: createAuthValue({ user: { id: 1, name: 'owner', email: 'o@e.com' } }),
    });

    expect(await screen.findByRole('heading', { name: 'Время' })).toBeInTheDocument();
    expect(screen.getByText('Аналитика')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Создать метрику' })).toBeInTheDocument();
    expect(metricsApi.fetchMetrics).toHaveBeenCalledWith(7);
  });

  it('скрывает управление метриками от обычного участника', async () => {
    projectsApi.fetchProject.mockResolvedValue(projectDev);

    renderWithProviders(<MetricsPage />, {
      auth: createAuthValue({ user: { id: 2, name: 'dev', email: 'd@e.com' } }),
    });

    expect(await screen.findByRole('heading', { name: 'Время' })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Создать метрику' })).not.toBeInTheDocument();
  });

  it('создаёт числовую метрику через диалог', async () => {
    metricsApi.fetchMetrics.mockResolvedValue([]);
    metricsApi.fetchAnalytics.mockResolvedValue([]);
    metricsApi.createMetric.mockResolvedValue({ message: 'ok', metric });

    renderWithProviders(<MetricsPage />, {
      auth: createAuthValue({ user: { id: 1, name: 'owner', email: 'o@e.com' } }),
    });

    await screen.findByText('Метрик пока нет.');
    await userEvent.click(screen.getByRole('button', { name: 'Создать метрику' }));

    const dialog = await screen.findByRole('dialog');
    await userEvent.type(within(dialog).getByLabelText('Название метрики'), 'Время');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Сохранить' }));

    await waitFor(() =>
      expect(metricsApi.createMetric).toHaveBeenCalledWith(7, {
        name: 'Время',
        type: 'numeric',
        options: undefined,
      }),
    );
  });

  it('показывает поле вариантов при категориальном типе и создаёт метрику', async () => {
    metricsApi.createMetric.mockResolvedValue({ message: 'ok', metric });

    renderWithProviders(<MetricsPage />, {
      auth: createAuthValue({ user: { id: 1, name: 'owner', email: 'o@e.com' } }),
    });
    await screen.findByRole('heading', { name: 'Время' });

    await userEvent.click(screen.getByRole('button', { name: 'Создать метрику' }));
    const dialog = await screen.findByRole('dialog');
    await userEvent.type(within(dialog).getByLabelText('Название метрики'), 'Компонент');
    await userEvent.click(within(dialog).getByLabelText('Тип метрики'));
    await userEvent.click(await screen.findByRole('option', { name: 'Категориальная' }));
    await userEvent.type(within(dialog).getByLabelText('Варианты (через запятую)'), 'UI, API');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Сохранить' }));

    await waitFor(() =>
      expect(metricsApi.createMetric).toHaveBeenCalledWith(7, {
        name: 'Компонент',
        type: 'categorical',
        options: ['UI', 'API'],
      }),
    );
  });

  it('редактирование предзаполняет форму метрики', async () => {
    renderWithProviders(<MetricsPage />, {
      auth: createAuthValue({ user: { id: 1, name: 'owner', email: 'o@e.com' } }),
    });
    await screen.findByRole('heading', { name: 'Время' });

    await userEvent.click(screen.getByTestId('EditIcon').closest('button') as HTMLButtonElement);
    const dialog = await screen.findByRole('dialog');

    expect(within(dialog).getByText('Редактировать метрику')).toBeInTheDocument();
    expect((within(dialog).getByLabelText('Название метрики') as HTMLInputElement).value).toBe(
      'Время',
    );
  });

  it('удаление метрики требует подтверждения', async () => {
    const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);
    metricsApi.deleteMetric.mockResolvedValue(undefined);

    renderWithProviders(<MetricsPage />, {
      auth: createAuthValue({ user: { id: 1, name: 'owner', email: 'o@e.com' } }),
    });
    await screen.findByRole('heading', { name: 'Время' });

    await userEvent.click(screen.getByTestId('DeleteIcon').closest('button') as HTMLButtonElement);

    expect(confirmSpy).toHaveBeenCalledWith('Удалить метрику «Время»?');
    await waitFor(() => expect(metricsApi.deleteMetric).toHaveBeenCalledWith(7, 3));
  });

  it('ссылка экспорта ведёт на API экспорта метрик', async () => {
    renderWithProviders(<MetricsPage />, {
      auth: createAuthValue({ user: { id: 1, name: 'owner', email: 'o@e.com' } }),
    });
    await screen.findByRole('heading', { name: 'Время' });

    expect(screen.getByRole('link', { name: 'Экспорт в Excel' })).toHaveAttribute(
      'href',
      '/api/projects/7/metrics/export',
    );
  });
});
