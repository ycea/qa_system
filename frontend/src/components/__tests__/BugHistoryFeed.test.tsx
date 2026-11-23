import { screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import BugHistoryFeed from '../BugHistoryFeed';

const base = {
  id: 1,
  field: null,
  old_value: null,
  new_value: null,
  created_at: '2026-01-01T10:00:00Z',
  user: { id: 1, name: 'admin' },
};

describe('BugHistoryFeed', () => {
  it('показывает пустое состояние', () => {
    renderWithProviders(<BugHistoryFeed items={[]} />);

    expect(screen.getByText('История изменений пуста.')).toBeInTheDocument();
  });

  it('рендерит все виды записей истории', () => {
    renderWithProviders(
      <BugHistoryFeed
        items={[
          { ...base, id: 1, action: 'created' },
          { ...base, id: 2, action: 'updated', field: 'title', old_value: 'A', new_value: 'B' },
          {
            ...base,
            id: 3,
            action: 'updated',
            field: 'unknown_field',
            old_value: null,
            new_value: null,
          },
          {
            ...base,
            id: 4,
            action: 'status_changed',
            field: 'status',
            old_value: 'Новый',
            new_value: 'Закрыт',
          },
          { ...base, id: 5, action: 'assignee_assigned', field: 'assignee', new_value: 'dev' },
          { ...base, id: 6, action: 'unknown_action' },
        ]}
      />,
    );

    expect(screen.getByText('Баг создан')).toBeInTheDocument();
    expect(screen.getByText('Заголовок: A → B')).toBeInTheDocument();
    expect(screen.getByText('unknown_field: — → —')).toBeInTheDocument();
    expect(screen.getByText('Статус: Новый → Закрыт')).toBeInTheDocument();
    expect(screen.getByText('Исполнитель: не назначен → dev')).toBeInTheDocument();
    expect(screen.getByText('unknown_action')).toBeInTheDocument();
  });
});
