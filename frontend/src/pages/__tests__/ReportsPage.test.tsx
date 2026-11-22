import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import ReportsPage from '../ReportsPage';

const reportsApi = vi.hoisted(() => ({ fetchReport: vi.fn() }));
vi.mock('../../api/reports', () => reportsApi);

const projectsApi = vi.hoisted(() => ({ fetchProjects: vi.fn() }));
vi.mock('../../api/projects', () => projectsApi);

const project = {
  id: 7,
  name: 'QMS',
  description: null,
  owner_id: 1,
  bugs_count: 0,
  created_at: '',
  updated_at: '',
};

describe('ReportsPage', () => {
  beforeEach(() => {
    projectsApi.fetchProjects.mockResolvedValue({
      data: [project],
      current_page: 1,
      last_page: 1,
      per_page: 100,
      total: 1,
    });
    reportsApi.fetchReport.mockResolvedValue({
      period: 'week',
      total: 4,
      closed: 2,
      closed_percent: 50,
      avg_lifetime_hours: 12.5,
    });
  });

  it('просит выбрать проект до построения отчёта', async () => {
    renderWithProviders(<ReportsPage />);

    expect(await screen.findByText('Выберите проект для построения отчёта.')).toBeInTheDocument();
    expect(reportsApi.fetchReport).not.toHaveBeenCalled();
  });

  it('строит отчёт после выбора проекта', async () => {
    renderWithProviders(<ReportsPage />);
    await screen.findByText('Выберите проект для построения отчёта.');

    await userEvent.click(screen.getByRole('combobox', { name: 'Проект' }));
    await userEvent.click(await screen.findByRole('option', { name: 'QMS' }));

    expect(await screen.findByText('50%')).toBeInTheDocument();
    expect(screen.getByText('Среднее время жизни (ч)')).toBeInTheDocument();

    await waitFor(() => expect(reportsApi.fetchReport).toHaveBeenCalledWith(7, 'week'));
  });

  it('смена периода перезапрашивает отчёт', async () => {
    renderWithProviders(<ReportsPage />);
    await screen.findByText('Выберите проект для построения отчёта.');

    await userEvent.click(screen.getByRole('combobox', { name: 'Проект' }));
    await userEvent.click(await screen.findByRole('option', { name: 'QMS' }));
    await screen.findByText('50%');

    await userEvent.click(screen.getByRole('combobox', { name: 'Период' }));
    await userEvent.click(await screen.findByRole('option', { name: 'Месяц' }));

    await waitFor(() => expect(reportsApi.fetchReport).toHaveBeenCalledWith(7, 'month'));
  });

  it('содержит варианты периодов: день, неделя, месяц', async () => {
    renderWithProviders(<ReportsPage />);
    await screen.findByText('Выберите проект для построения отчёта.');

    await userEvent.click(screen.getByRole('combobox', { name: 'Период' }));

    expect(await screen.findByRole('option', { name: 'День' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Неделя' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Месяц' })).toBeInTheDocument();
  });
});
