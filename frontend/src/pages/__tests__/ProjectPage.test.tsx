import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
  createAuthValue,
  deferred,
  makeAxiosError,
  renderWithProviders,
} from '../../test/test-utils';
import ProjectPage from '../ProjectPage';

const projectsApi = vi.hoisted(() => ({ fetchProject: vi.fn(), deleteProject: vi.fn() }));
const membersApi = vi.hoisted(() => ({
  fetchMembers: vi.fn(),
  addMember: vi.fn(),
  removeMember: vi.fn(),
}));
const dictionariesApi = vi.hoisted(() => ({
  fetchDictionaries: vi.fn(),
  createDictionaryItem: vi.fn(),
  updateDictionaryItem: vi.fn(),
  deleteDictionaryItem: vi.fn(),
}));

vi.mock('../../api/projects', () => projectsApi);
vi.mock('../../api/members', () => membersApi);
vi.mock('../../api/dictionaries', () => dictionariesApi);

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

const owner = { id: 1, name: 'owner', email: 'owner@example.com' };
const projectAdmin = { id: 2, name: 'admin2', email: 'admin2@example.com' };
const stranger = { id: 3, name: 'dev', email: 'dev@example.com' };

const project = {
  id: 7,
  name: 'QMS',
  description: 'Описание проекта',
  owner_id: owner.id,
  bugs_count: 4,
  members: [
    {
      ...projectAdmin,
      pivot: { project_id: 7, user_id: projectAdmin.id, role: 'admin' as const },
    },
  ],
  created_at: '2026-01-01',
  updated_at: '2026-01-02',
};

const renderPage = (user: typeof owner) =>
  renderWithProviders(<ProjectPage />, { auth: createAuthValue({ user }) });

const deleteHeaderButton = () =>
  screen.getByTestId('DeleteIcon').closest('button') as HTMLButtonElement;

describe('ProjectPage', () => {
  beforeEach(() => {
    routeMocks.params = { id: '7' };
    membersApi.fetchMembers.mockResolvedValue({ members: [], can_manage: true });
    dictionariesApi.fetchDictionaries.mockResolvedValue([]);
  });

  it('показывает спиннер во время загрузки', () => {
    projectsApi.fetchProject.mockReturnValue(deferred().promise);

    renderPage(owner);

    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });

  it('показывает сообщение, если проект недоступен', async () => {
    projectsApi.fetchProject.mockRejectedValue(new Error('404'));

    renderPage(owner);

    expect(await screen.findByText('Проект не найден или доступ запрещён.')).toBeInTheDocument();
  });

  it('не запрашивает проект без id в URL', async () => {
    routeMocks.params = {};
    projectsApi.fetchProject.mockReturnValue(deferred().promise);

    renderPage(owner);

    expect(await screen.findByText('Проект не найден или доступ запрещён.')).toBeInTheDocument();
    expect(projectsApi.fetchProject).not.toHaveBeenCalled();
  });

  it('показывает данные проекта владельцу', async () => {
    projectsApi.fetchProject.mockResolvedValue(project);

    renderPage(owner);

    expect(await screen.findByRole('heading', { name: 'QMS' })).toBeInTheDocument();
    expect(screen.getByText('Описание проекта')).toBeInTheDocument();
    expect(screen.getByText('Багов:')).toBeInTheDocument();
    expect(screen.getByText('4')).toBeInTheDocument();
    expect(screen.getByTestId('DeleteIcon')).toBeInTheDocument();
  });

  it('показывает «Без описания» и скрывает удаление от обычного участника', async () => {
    projectsApi.fetchProject.mockResolvedValue({ ...project, description: null });

    renderPage(stranger);

    expect(await screen.findByRole('heading', { name: 'QMS' })).toBeInTheDocument();
    expect(screen.getByText('Без описания')).toBeInTheDocument();
    expect(screen.queryByTestId('DeleteIcon')).not.toBeInTheDocument();
    expect(screen.queryByRole('tab', { name: 'Справочники' })).not.toBeInTheDocument();
  });

  it('переключается на вкладку команды', async () => {
    projectsApi.fetchProject.mockResolvedValue(project);

    renderPage(owner);
    await screen.findByRole('heading', { name: 'QMS' });

    await userEvent.click(screen.getByRole('tab', { name: 'Команда' }));

    expect(await screen.findByText('Состав команды')).toBeInTheDocument();
    expect(membersApi.fetchMembers).toHaveBeenCalledWith(7);
  });

  it('показывает справочники администратору проекта', async () => {
    projectsApi.fetchProject.mockResolvedValue(project);

    renderPage(projectAdmin);
    await screen.findByRole('heading', { name: 'QMS' });

    await userEvent.click(screen.getByRole('tab', { name: 'Справочники' }));

    expect(await screen.findByText('Справочники проекта')).toBeInTheDocument();
    expect(dictionariesApi.fetchDictionaries).toHaveBeenCalledWith(7, 'statuses');
  });

  it('удаляет проект после ввода названия и переходит к списку', async () => {
    projectsApi.fetchProject.mockResolvedValue(project);
    projectsApi.deleteProject.mockResolvedValue(undefined);

    renderPage(owner);
    await screen.findByRole('heading', { name: 'QMS' });

    await userEvent.click(deleteHeaderButton());
    const dialog = await screen.findByRole('dialog');
    const confirm = within(dialog).getByRole('button', { name: 'Удалить проект' });
    expect(confirm).toBeDisabled();

    await userEvent.type(within(dialog).getByLabelText('Введите "QMS"'), 'QMS');
    expect(confirm).toBeEnabled();
    await userEvent.click(confirm);

    await waitFor(() => expect(projectsApi.deleteProject).toHaveBeenCalledWith(7));
    expect(routeMocks.navigate).toHaveBeenCalledWith('/');
  });

  it('показывает ошибку удаления проекта', async () => {
    projectsApi.fetchProject.mockResolvedValue(project);
    projectsApi.deleteProject.mockRejectedValue(makeAxiosError({ message: 'Нельзя удалить' }));

    renderPage(owner);
    await screen.findByRole('heading', { name: 'QMS' });

    await userEvent.click(deleteHeaderButton());
    const dialog = await screen.findByRole('dialog');
    await userEvent.type(within(dialog).getByLabelText('Введите "QMS"'), 'QMS');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Удалить проект' }));

    expect(await screen.findByText('Нельзя удалить')).toBeInTheDocument();
    expect(routeMocks.navigate).not.toHaveBeenCalled();
  });

  it('возвращает к списку проектов по кнопке Назад', async () => {
    projectsApi.fetchProject.mockResolvedValue(project);

    renderPage(owner);
    await screen.findByRole('heading', { name: 'QMS' });

    await userEvent.click(screen.getByRole('button', { name: '← Назад к проектам' }));

    expect(routeMocks.navigate).toHaveBeenCalledWith('/');
  });
});
