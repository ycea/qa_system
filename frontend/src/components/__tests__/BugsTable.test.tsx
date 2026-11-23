import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import BugsTable from '../BugsTable';

const bug = {
  id: 5,
  project_id: 7,
  status_id: 1,
  priority_id: 2,
  severity_id: 3,
  assignee_id: null,
  created_by: 1,
  title: 'Краш',
  description: 'Описание',
  steps_to_reproduce: 'Шаги',
  closed_at: null,
  created_at: '2026-01-01T10:00:00Z',
  updated_at: '2026-01-01T10:00:00Z',
  priority: { id: 2, project_id: 7, name: 'normal' },
  severity: { id: 3, project_id: 7, name: 'Major' },
};

const statuses = [
  { id: 1, project_id: 7, name: 'Новый', in_use: false },
  { id: 9, project_id: 7, name: 'Закрыт', in_use: false },
];

const members = [{ id: 2, name: 'dev' }];

const renderTable = (overrides: Record<string, unknown> = {}) => {
  const props = {
    bugs: [bug],
    statuses,
    members,
    onStatusChange: vi.fn(),
    onAssigneeChange: vi.fn(),
    onOpen: vi.fn(),
    ...overrides,
  };

  renderWithProviders(<BugsTable {...props} />);

  return props;
};

const renderEmpty = () =>
  renderWithProviders(
    <BugsTable
      bugs={[]}
      statuses={statuses}
      members={members}
      onStatusChange={vi.fn()}
      onAssigneeChange={vi.fn()}
      onOpen={vi.fn()}
    />,
  );

describe('BugsTable', () => {
  it('показывает пустое состояние', () => {
    renderEmpty();

    expect(screen.getByText('Багов не найдено.')).toBeInTheDocument();
  });

  it('открывает баг кликом по строке', async () => {
    const props = renderTable();

    await userEvent.click(screen.getByText('Краш'));

    expect(props.onOpen).toHaveBeenCalledWith(bug);
  });

  it('меняет статус из таблицы и не открывает баг', async () => {
    const props = renderTable();

    await userEvent.click(screen.getByRole('combobox', { name: 'Статус: Краш' }));
    await userEvent.click(await screen.findByRole('option', { name: 'Закрыт' }));

    expect(props.onStatusChange).toHaveBeenCalledWith(5, 9);
    expect(props.onOpen).not.toHaveBeenCalled();
  });

  it('назначает исполнителя из таблицы', async () => {
    const props = renderTable();

    await userEvent.click(screen.getByRole('combobox', { name: 'Исполнитель: Краш' }));
    await userEvent.click(await screen.findByRole('option', { name: 'dev' }));

    expect(props.onAssigneeChange).toHaveBeenCalledWith(5, 2);
  });

  it('снимает исполнителя', async () => {
    const props = renderTable({ bugs: [{ ...bug, assignee_id: 2 }] });

    await userEvent.click(screen.getByRole('combobox', { name: 'Исполнитель: Краш' }));
    await userEvent.click(await screen.findByRole('option', { name: '— не назначен —' }));

    expect(props.onAssigneeChange).toHaveBeenCalledWith(5, null);
  });

  it('показывает прочерки без приоритета и серьёзности', () => {
    renderTable({ bugs: [{ ...bug, priority: undefined, severity: undefined }] });

    expect(screen.getAllByText('—').length).toBe(2);
  });
});
