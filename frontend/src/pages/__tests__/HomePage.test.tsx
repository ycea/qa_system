import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { deferred, makeAxiosError, renderWithProviders } from '../../test/test-utils';
import HomePage from '../HomePage';

const projectsApi = vi.hoisted(() => ({ fetchProjects: vi.fn(), createProject: vi.fn() }));
vi.mock('../../api/projects', () => projectsApi);

const routeMocks = vi.hoisted(() => ({ navigate: vi.fn() }));
vi.mock('react-router-dom', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router-dom')>();
  return { ...actual, useNavigate: () => routeMocks.navigate };
});

const project = {
  id: 5,
  name: 'QMS',
  description: 'Описание проекта',
  owner_id: 1,
  bugs_count: 2,
  created_at: '2026-01-01',
  updated_at: '2026-01-02',
};

const paginated = (data: unknown[], overrides: Record<string, unknown> = {}) => ({
  data,
  current_page: 1,
  last_page: 1,
  per_page: 30,
  total: data.length,
  ...overrides,
});

const cardByName = (name: string) => screen.getByText(name).closest('.MuiCard-root') as HTMLElement;

const openCreateDialog = async () => {
  await userEvent.click(screen.getByRole('button', { name: 'Создать проект' }));
  return screen.findByRole('dialog');
};

describe('HomePage', () => {
  it('показывает спиннер во время загрузки', () => {
    projectsApi.fetchProjects.mockReturnValue(deferred().promise);

    renderWithProviders(<HomePage />);

    expect(screen.getByRole('progressbar')).toBeInTheDocument();
    expect(screen.getByRole('heading', { level: 1, name: 'Мои проекты' })).toBeInTheDocument();
  });

  it('показывает ошибку загрузки', async () => {
    projectsApi.fetchProjects.mockRejectedValue(new Error('500'));

    renderWithProviders(<HomePage />);

    expect(await screen.findByText('Не удалось загрузить проекты')).toBeInTheDocument();
  });

  it('показывает заглушку при пустом списке', async () => {
    projectsApi.fetchProjects.mockResolvedValue(paginated([]));

    renderWithProviders(<HomePage />);

    expect(
      await screen.findByText('У вас пока нет проектов. Создайте первый!'),
    ).toBeInTheDocument();
  });

  it('рендерит карточки проектов с описанием и количеством багов', async () => {
    projectsApi.fetchProjects.mockResolvedValue(
      paginated([project, { ...project, id: 6, name: 'Пустой', description: null, bugs_count: 0 }]),
    );

    renderWithProviders(<HomePage />);
    await screen.findByText('QMS');

    expect(within(cardByName('QMS')).getByText('Описание проекта')).toBeInTheDocument();
    expect(within(cardByName('QMS')).getByText('2')).toBeInTheDocument();
    expect(within(cardByName('Пустой')).getByText('Без описания')).toBeInTheDocument();
    expect(within(cardByName('Пустой')).getByText('0')).toBeInTheDocument();
  });

  it('переходит на страницу проекта по клику на карточку', async () => {
    projectsApi.fetchProjects.mockResolvedValue(paginated([project]));

    renderWithProviders(<HomePage />);
    await screen.findByText('QMS');

    await userEvent.click(screen.getByText('QMS'));

    expect(routeMocks.navigate).toHaveBeenCalledWith('/projects/5');
  });

  it('переключает страницу пагинации', async () => {
    projectsApi.fetchProjects.mockResolvedValue(paginated([project], { last_page: 2, total: 31 }));

    renderWithProviders(<HomePage />);
    await screen.findByText('QMS');

    await userEvent.click(screen.getByRole('button', { name: 'Go to page 2' }));

    await waitFor(() =>
      expect(projectsApi.fetchProjects).toHaveBeenCalledWith({ page: 2, per_page: 30 }),
    );
  });

  it('не отправляет проект без названия', async () => {
    projectsApi.fetchProjects.mockResolvedValue(paginated([]));

    renderWithProviders(<HomePage />);
    await screen.findByText('У вас пока нет проектов. Создайте первый!');

    const dialog = await openCreateDialog();
    await userEvent.click(within(dialog).getByRole('button', { name: 'Создать' }));

    expect(await screen.findByText('Название обязательно')).toBeInTheDocument();
    expect(projectsApi.createProject).not.toHaveBeenCalled();
  });

  it('создаёт проект и закрывает диалог', async () => {
    projectsApi.fetchProjects.mockResolvedValue(paginated([]));
    projectsApi.createProject.mockResolvedValue({ message: 'created', project });

    renderWithProviders(<HomePage />);
    await screen.findByText('У вас пока нет проектов. Создайте первый!');

    const dialog = await openCreateDialog();
    await userEvent.type(within(dialog).getByLabelText('Название проекта'), 'Новый проект');
    await userEvent.type(within(dialog).getByLabelText('Описание (опционально)'), 'Описание');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Создать' }));

    await waitFor(() => expect(projectsApi.createProject).toHaveBeenCalled());
    expect(projectsApi.createProject.mock.calls[0][0]).toEqual({
      name: 'Новый проект',
      description: 'Описание',
    });
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
  });

  it('показывает серверную ошибку создания', async () => {
    projectsApi.fetchProjects.mockResolvedValue(paginated([]));
    projectsApi.createProject.mockRejectedValue(makeAxiosError({ message: 'Название занято' }));

    renderWithProviders(<HomePage />);
    await screen.findByText('У вас пока нет проектов. Создайте первый!');

    const dialog = await openCreateDialog();
    await userEvent.type(within(dialog).getByLabelText('Название проекта'), 'QMS');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Создать' }));

    expect(await screen.findByText('Название занято')).toBeInTheDocument();
  });

  it('показывает ошибку по умолчанию при создании', async () => {
    projectsApi.fetchProjects.mockResolvedValue(paginated([]));
    projectsApi.createProject.mockRejectedValue(new Error('network'));

    renderWithProviders(<HomePage />);
    await screen.findByText('У вас пока нет проектов. Создайте первый!');

    const dialog = await openCreateDialog();
    await userEvent.type(within(dialog).getByLabelText('Название проекта'), 'QMS');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Создать' }));

    expect(await screen.findByText('Ошибка при создании проекта')).toBeInTheDocument();
  });

  it('закрывает диалог по кнопке Отмена', async () => {
    projectsApi.fetchProjects.mockResolvedValue(paginated([]));

    renderWithProviders(<HomePage />);
    await screen.findByText('У вас пока нет проектов. Создайте первый!');

    const dialog = await openCreateDialog();
    await userEvent.click(within(dialog).getByRole('button', { name: 'Отмена' }));

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(projectsApi.createProject).not.toHaveBeenCalled();
  });
});
