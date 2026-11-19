import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import {
  createTestQueryClient,
  deferred,
  makeAxiosError,
  renderWithProviders,
} from '../../test/test-utils';
import TeamTab from '../TeamTab';

const membersApi = vi.hoisted(() => ({
  fetchMembers: vi.fn(),
  addMember: vi.fn(),
  removeMember: vi.fn(),
}));

vi.mock('../../api/members', () => membersApi);

const admin = { id: 1, name: 'admin', email: 'admin@example.com', role: 'admin' as const };
const developer = { id: 2, name: 'dev', email: 'dev@example.com', role: 'developer' as const };
const tester = { id: 3, name: 'qa', email: 'qa@example.com', role: 'tester' as const };

const rowByEmail = (email: string) => screen.getByText(email).closest('tr') as HTMLElement;

const openAddDialog = async () => {
  await userEvent.click(screen.getByRole('button', { name: 'Добавить участника' }));
  return screen.findByRole('dialog');
};

describe('TeamTab', () => {
  it('показывает спиннер во время загрузки', () => {
    membersApi.fetchMembers.mockReturnValue(deferred().promise);

    renderWithProviders(<TeamTab projectId={7} />);

    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });

  it('показывает ошибку, если запрос упал', async () => {
    membersApi.fetchMembers.mockRejectedValue(new Error('500'));

    renderWithProviders(<TeamTab projectId={7} />);

    expect(await screen.findByText('Не удалось загрузить участников.')).toBeInTheDocument();
  });

  it('показывает заглушку и кнопку добавления при пустом составе', async () => {
    membersApi.fetchMembers.mockResolvedValue({ members: [], can_manage: true });

    renderWithProviders(<TeamTab projectId={7} />);

    expect(await screen.findByText('Пока нет участников.')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Добавить участника' })).toBeInTheDocument();
    expect(membersApi.fetchMembers).toHaveBeenCalledWith(7);
  });

  it('рендерит участников с русскими названиями ролей', async () => {
    membersApi.fetchMembers.mockResolvedValue({
      members: [admin, developer, tester],
      can_manage: true,
    });

    renderWithProviders(<TeamTab projectId={7} />);

    expect(await screen.findByText('dev@example.com')).toBeInTheDocument();
    expect(within(rowByEmail('admin@example.com')).getByText('Администратор')).toBeInTheDocument();
    expect(within(rowByEmail('dev@example.com')).getByText('Разработчик')).toBeInTheDocument();
    expect(within(rowByEmail('qa@example.com')).getByText('Тестировщик')).toBeInTheDocument();
    expect(screen.getByText('Действия')).toBeInTheDocument();
  });

  it('скрывает управление, если can_manage = false', async () => {
    membersApi.fetchMembers.mockResolvedValue({ members: [developer], can_manage: false });

    renderWithProviders(<TeamTab projectId={7} />);

    expect(await screen.findByText('dev@example.com')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Добавить участника' })).not.toBeInTheDocument();
    expect(screen.queryByText('Действия')).not.toBeInTheDocument();
    expect(within(rowByEmail('dev@example.com')).queryByRole('button')).not.toBeInTheDocument();
  });

  it('не удаляет администратора и уважает отказ в confirm', async () => {
    membersApi.fetchMembers.mockResolvedValue({ members: [admin, developer], can_manage: true });
    const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

    renderWithProviders(<TeamTab projectId={7} />);
    await screen.findByText('dev@example.com');

    expect(within(rowByEmail('admin@example.com')).getByRole('button')).toBeDisabled();

    await userEvent.click(within(rowByEmail('dev@example.com')).getByRole('button'));

    expect(confirmSpy).toHaveBeenCalledWith('Удалить dev из проекта?');
    expect(membersApi.removeMember).not.toHaveBeenCalled();
  });

  it('удаляет участника после подтверждения и инвалидирует кэш', async () => {
    membersApi.fetchMembers.mockResolvedValue({ members: [developer], can_manage: true });
    membersApi.removeMember.mockResolvedValue(undefined);
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const queryClient = createTestQueryClient();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');

    renderWithProviders(<TeamTab projectId={7} />, { queryClient });
    await screen.findByText('dev@example.com');

    await userEvent.click(within(rowByEmail('dev@example.com')).getByRole('button'));

    await waitFor(() => expect(membersApi.removeMember).toHaveBeenCalledWith(7, developer.id));
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['members', 7] });
  });

  it('добавляет участника с выбранной ролью и закрывает диалог', async () => {
    membersApi.fetchMembers.mockResolvedValue({ members: [], can_manage: true });
    membersApi.addMember.mockResolvedValue({ message: 'ok', member: { ...tester, name: 'bob' } });

    renderWithProviders(<TeamTab projectId={7} />);
    await screen.findByText('Пока нет участников.');

    const dialog = await openAddDialog();
    await userEvent.type(within(dialog).getByLabelText('Логин пользователя'), 'bob');
    await userEvent.click(within(dialog).getByRole('combobox'));
    await userEvent.click(await screen.findByRole('option', { name: 'Тестировщик' }));
    await userEvent.click(within(dialog).getByRole('button', { name: 'Добавить' }));

    await waitFor(() =>
      expect(membersApi.addMember).toHaveBeenCalledWith(7, { name: 'bob', role: 'tester' }),
    );
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
  });

  it('отправляет роль по умолчанию (developer)', async () => {
    membersApi.fetchMembers.mockResolvedValue({ members: [], can_manage: true });
    membersApi.addMember.mockResolvedValue({ message: 'ok', member: developer });

    renderWithProviders(<TeamTab projectId={7} />);
    await screen.findByText('Пока нет участников.');

    const dialog = await openAddDialog();
    await userEvent.type(within(dialog).getByLabelText('Логин пользователя'), 'dev');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Добавить' }));

    await waitFor(() =>
      expect(membersApi.addMember).toHaveBeenCalledWith(7, { name: 'dev', role: 'developer' }),
    );
  });

  it.each([
    [makeAxiosError({ errors: { name: ['Пользователь не найден'] } }), 'Пользователь не найден'],
    [makeAxiosError({ message: 'Нельзя добавить' }), 'Нельзя добавить'],
    [new Error('network'), 'Не удалось добавить участника'],
  ])('показывает текст ошибки добавления (case %#)', async (error, expected) => {
    membersApi.fetchMembers.mockResolvedValue({ members: [], can_manage: true });
    membersApi.addMember.mockRejectedValue(error);

    renderWithProviders(<TeamTab projectId={7} />);
    await screen.findByText('Пока нет участников.');

    const dialog = await openAddDialog();
    await userEvent.type(within(dialog).getByLabelText('Логин пользователя'), 'ghost');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Добавить' }));

    expect(await screen.findByText(expected)).toBeInTheDocument();
  });

  it('закрывает диалог по кнопке Отмена', async () => {
    membersApi.fetchMembers.mockResolvedValue({ members: [], can_manage: true });

    renderWithProviders(<TeamTab projectId={7} />);
    await screen.findByText('Пока нет участников.');

    const dialog = await openAddDialog();
    await userEvent.click(within(dialog).getByRole('button', { name: 'Отмена' }));

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(membersApi.addMember).not.toHaveBeenCalled();
  });
});
