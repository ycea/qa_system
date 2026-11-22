import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import BugMetricsSection from '../BugMetricsSection';

const metricsApi = vi.hoisted(() => ({ fetchMetrics: vi.fn(), setMetricValue: vi.fn() }));
vi.mock('../../api/metrics', () => metricsApi);

const numericMetric = {
  id: 3,
  project_id: 7,
  name: 'Время',
  type: 'numeric' as const,
  options: null,
};

describe('BugMetricsSection', () => {
  beforeEach(() => {
    metricsApi.fetchMetrics.mockResolvedValue([numericMetric]);
    metricsApi.setMetricValue.mockResolvedValue({ message: 'ok', metric_value: { id: 1 } });
  });

  it('сохраняет числовое значение метрики на баге', async () => {
    renderWithProviders(<BugMetricsSection projectId={7} bugId={5} />);

    const input = await screen.findByLabelText('Время');
    await userEvent.type(input, '5');
    await userEvent.click(screen.getByRole('button', { name: 'Сохранить' }));

    await waitFor(() =>
      expect(metricsApi.setMetricValue).toHaveBeenCalledWith(7, 5, 3, { numeric_value: 5 }),
    );
  });

  it('ничего не рендерит при отсутствии метрик', async () => {
    metricsApi.fetchMetrics.mockResolvedValue([]);

    const { container } = renderWithProviders(<BugMetricsSection projectId={7} bugId={5} />);

    await waitFor(() => expect(metricsApi.fetchMetrics).toHaveBeenCalledWith(7));
    expect(container).toBeEmptyDOMElement();
  });
});
