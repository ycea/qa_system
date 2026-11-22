import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';
import type { BugFilters } from '../../api/bugs';
import { renderWithProviders } from '../../test/test-utils';
import BugFiltersBar from '../BugFiltersBar';

const filters = {
  search: '',
  status_id: '' as const,
  priority_id: '' as const,
  severity_id: '' as const,
  assignee_id: '' as const,
};

const options = {
  statuses: [{ id: 9, project_id: 7, name: 'Закрыт', in_use: false }],
  priorities: [{ id: 2, project_id: 7, name: 'normal', in_use: false }],
  severities: [{ id: 3, project_id: 7, name: 'Major', in_use: false }],
  members: [{ id: 4, name: 'dev' }],
};

/** Обёртка с реальным состоянием фильтров (как на странице). */
function StatefulFilters({ onChange }: { onChange: (patch: Partial<BugFilters>) => void }) {
  const [current, setCurrent] = useState<BugFilters>(filters);

  return (
    <BugFiltersBar
      {...options}
      filters={current}
      onChange={(patch) => {
        setCurrent((prev) => ({ ...prev, ...patch }));
        onChange(patch);
      }}
      onReset={() => setCurrent(filters)}
    />
  );
}

describe('BugFiltersBar', () => {
  it('передаёт поисковый запрос', async () => {
    const onChange = vi.fn();

    renderWithProviders(<StatefulFilters onChange={onChange} />);

    await userEvent.type(screen.getByLabelText('Поиск по заголовку'), 'Login');

    expect(onChange).toHaveBeenLastCalledWith({ search: 'Login' });
  });

  it('меняет фильтр по статусу', async () => {
    const onChange = vi.fn();

    renderWithProviders(
      <BugFiltersBar {...options} filters={filters} onChange={onChange} onReset={vi.fn()} />,
    );

    await userEvent.click(screen.getByRole('combobox', { name: 'Статус' }));
    await userEvent.click(await screen.findByRole('option', { name: 'Закрыт' }));

    expect(onChange).toHaveBeenCalledWith({ status_id: 9 });
  });

  it('меняет фильтр по исполнителю', async () => {
    const onChange = vi.fn();

    renderWithProviders(
      <BugFiltersBar {...options} filters={filters} onChange={onChange} onReset={vi.fn()} />,
    );

    await userEvent.click(screen.getByRole('combobox', { name: 'Исполнитель' }));
    await userEvent.click(await screen.findByRole('option', { name: 'dev' }));

    expect(onChange).toHaveBeenCalledWith({ assignee_id: 4 });
  });

  it('сбрасывает фильтры', async () => {
    const onReset = vi.fn();

    renderWithProviders(
      <BugFiltersBar {...options} filters={filters} onChange={vi.fn()} onReset={onReset} />,
    );

    await userEvent.click(screen.getByRole('button', { name: 'Сбросить всё' }));

    expect(onReset).toHaveBeenCalledTimes(1);
  });
});
