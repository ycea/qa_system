import { screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import MetricChart from '../MetricChart';

const numeric = {
  metric: { id: 1, name: 'Время', type: 'numeric' as const, options: null },
  count: 2,
  indicators: { avg: 15, min: 10, max: 20, sum: 30 },
  by_status: [
    { status: 'Новый', count: 1, avg: 10 },
    { status: 'В работе', count: 1, avg: 20 },
  ],
};

const categorical = {
  metric: { id: 2, name: 'Компонент', type: 'categorical' as const, options: ['UI'] },
  count: 2,
  indicators: [{ value: 'UI', count: 2 }],
  by_status: [{ status: 'Новый', count: 2, avg: null }],
};

describe('MetricChart', () => {
  it('строит график по статусам для числовой метрики', () => {
    const { container } = renderWithProviders(<MetricChart item={numeric} />);

    expect(container.querySelector('svg.recharts-surface')).toBeInTheDocument();
    expect(screen.getAllByText('Новый').length).toBeGreaterThan(0);
    expect(screen.getAllByText('В работе').length).toBeGreaterThan(0);
  });

  it('строит график распределения для категориальной метрики', () => {
    const { container } = renderWithProviders(<MetricChart item={categorical} />);

    expect(container.querySelector('svg.recharts-surface')).toBeInTheDocument();
    expect(screen.getAllByText('UI').length).toBeGreaterThan(0);
  });

  it('показывает заглушку при отсутствии данных', () => {
    renderWithProviders(<MetricChart item={{ ...numeric, count: 0, by_status: [] }} />);

    expect(screen.getByText('Нет данных для графика.')).toBeInTheDocument();
  });
});
