import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import BugFormDialog from '../BugFormDialog';

const statuses = [
  { id: 1, project_id: 7, name: 'Новый', is_default: true, in_use: false },
  { id: 9, project_id: 7, name: 'Закрыт', in_use: false },
];
const priorities = [{ id: 2, project_id: 7, name: 'normal', in_use: false }];
const severities = [{ id: 3, project_id: 7, name: 'Major', in_use: false }];
const members = [{ id: 4, name: 'dev' }];

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
};

const baseProps = {
  open: true,
  statuses,
  priorities,
  severities,
  members,
};

describe('BugFormDialog', () => {
  it('в режиме создания кнопка сохранения неактивна до заполнения формы', async () => {
    const onSubmit = vi.fn();

    renderWithProviders(
      <BugFormDialog {...baseProps} mode="create" onSubmit={onSubmit} onClose={vi.fn()} />,
    );
    const dialog = screen.getByRole('dialog');

    expect(within(dialog).getByText('Создать баг')).toBeInTheDocument();
    const save = within(dialog).getByRole('button', { name: 'Сохранить' });
    expect(save).toBeDisabled();

    await userEvent.type(within(dialog).getByLabelText('Заголовок'), 'Новый баг');
    await userEvent.type(within(dialog).getByLabelText('Описание'), 'Описание');
    await userEvent.type(within(dialog).getByLabelText('Шаги воспроизведения'), 'Шаги');
    await userEvent.click(within(dialog).getByLabelText('Приоритет'));
    await userEvent.click(await screen.findByRole('option', { name: 'normal' }));
    await userEvent.click(within(dialog).getByLabelText('Серьёзность'));
    await userEvent.click(await screen.findByRole('option', { name: 'Major' }));
    await userEvent.click(within(dialog).getByLabelText('Исполнитель'));
    await userEvent.click(await screen.findByRole('option', { name: 'dev' }));

    expect(save).toBeEnabled();
    await userEvent.click(save);

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        title: 'Новый баг',
        status_id: 1,
        priority_id: 2,
        severity_id: 3,
        assignee_id: 4,
      }),
    );
  });

  it('в режиме редактирования предзаполняет поля и не показывает статус', () => {
    renderWithProviders(
      <BugFormDialog {...baseProps} mode="edit" bug={bug} onSubmit={vi.fn()} onClose={vi.fn()} />,
    );
    const dialog = screen.getByRole('dialog');

    expect(within(dialog).getByText('Редактировать баг')).toBeInTheDocument();
    expect((within(dialog).getByLabelText('Заголовок') as HTMLInputElement).value).toBe('Краш');
    expect(within(dialog).queryByLabelText('Статус')).not.toBeInTheDocument();
    expect(within(dialog).getByRole('button', { name: 'Сохранить' })).toBeEnabled();
  });

  it('показывает ошибку и закрывается по кнопке Отмена', async () => {
    const onClose = vi.fn();

    renderWithProviders(
      <BugFormDialog
        {...baseProps}
        mode="create"
        error="Не удалось создать баг"
        onSubmit={vi.fn()}
        onClose={onClose}
      />,
    );
    const dialog = screen.getByRole('dialog');

    expect(within(dialog).getByText('Не удалось создать баг')).toBeInTheDocument();

    await userEvent.click(within(dialog).getByRole('button', { name: 'Отмена' }));

    expect(onClose).toHaveBeenCalledTimes(1);
  });
});
