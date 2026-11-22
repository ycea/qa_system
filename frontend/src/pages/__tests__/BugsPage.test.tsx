import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import BugsPage from '../BugsPage';

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

const bugsApi = vi.hoisted(() => ({
  fetchBugs: vi.fn(),
  createBug: vi.fn(),
  changeBugStatus: vi.fn(),
  assignBug: vi.fn(),
}));
vi.mock('../../api/bugs', () => bugsApi);

const metaApi = vi.hoisted(() => ({ fetchProjectMeta: vi.fn() }));
vi.mock('../../api/meta', () => metaApi);

const meta = {
  members: [{ id: 2, name: 'dev', email: 'dev@example.com', role: 'developer' as const }],
  statuses: [
    { id: 1, project_id: 7, name: 'Новая', is_default: true, in_use: false },
    { id: 9, project_id: 7, name: 'Закрыта', in_use: false },
  ],
  priorities: [{ id: 2, project_id: 7, name: 'low', in_use: false }],
  severities: [{ id: 3, project_id: 7, name: 'Minor', in_use: false }],
};

const bug = {
  id: 5,
  project_id: 7,
  status_id: 1,
  priority_id: 2,
  severity_id: 3,
  assignee_id: null,
  created_by: 1,
  title: 'Краш',
  description: 'd',
  steps_to_reproduce: 's',
  closed_at: null,
  created_at: '2026-01-01T10:00:00Z',
  updated_at: '2026-01-01T10:00:00Z',
  status: { id: 1, project_id: 7, name: 'Новая' },
  priority: { id: 2, project_id: 7, name: 'low' },
  severity: { id: 3, project_id: 7, name: 'Minor' },
  assignee: null,
};

describe('BugsPage', () => {
  beforeEach(() => {
    routeMocks.params = { id: '7' };
    metaApi.fetchProjectMeta.mockResolvedValue(meta);
    bugsApi.fetchBugs.mockResolvedValue([bug]);
  });

  it('загружает meta проекта и список багов', async () => {
    renderWithProviders(<BugsPage />);

    expect(await screen.findByText('Краш')).toBeInTheDocument();
    expect(metaApi.fetchProjectMeta).toHaveBeenCalledWith(7);
    expect(bugsApi.fetchBugs).toHaveBeenCalledWith(7, expect.objectContaining({ search: '' }));
  });

  it('показывает пустое состояние', async () => {
    bugsApi.fetchBugs.mockResolvedValue([]);

    renderWithProviders(<BugsPage />);

    expect(await screen.findByText('Багов не найдено.')).toBeInTheDocument();
  });

  it('переходит к деталям по клику на заголовок', async () => {
    renderWithProviders(<BugsPage />);
    await screen.findByText('Краш');

    await userEvent.click(screen.getByRole('button', { name: 'Краш' }));

    expect(routeMocks.navigate).toHaveBeenCalledWith('/projects/7/bugs/5');
  });

  it('меняет статус бага прямо из таблицы', async () => {
    bugsApi.changeBugStatus.mockResolvedValue({ message: 'ok', bug });

    renderWithProviders(<BugsPage />);
    await screen.findByText('Краш');

    await userEvent.click(screen.getByRole('combobox', { name: 'Статус: Краш' }));
    await userEvent.click(await screen.findByRole('option', { name: 'Закрыта' }));

    await waitFor(() => expect(bugsApi.changeBugStatus).toHaveBeenCalledWith(7, 5, 9));
  });

  it('создаёт баг из формы', async () => {
    bugsApi.createBug.mockResolvedValue({ message: 'Баг создан.', bug });

    renderWithProviders(<BugsPage />);
    await screen.findByText('Краш');

    await userEvent.click(screen.getByRole('button', { name: 'Создать баг' }));
    const dialog = await screen.findByRole('dialog');

    await userEvent.type(within(dialog).getByLabelText('Заголовок'), 'Новый баг');
    await userEvent.type(within(dialog).getByLabelText('Описание'), 'описание');
    await userEvent.type(within(dialog).getByLabelText('Шаги воспроизведения'), 'шаги');
    await userEvent.click(within(dialog).getByLabelText('Приоритет'));
    await userEvent.click(await screen.findByRole('option', { name: 'low' }));
    await userEvent.click(within(dialog).getByLabelText('Серьёзность'));
    await userEvent.click(await screen.findByRole('option', { name: 'Minor' }));

    await userEvent.click(within(dialog).getByRole('button', { name: 'Сохранить' }));

    await waitFor(() =>
      expect(bugsApi.createBug).toHaveBeenCalledWith(
        7,
        expect.objectContaining({ title: 'Новый баг', description: 'описание' }),
      ),
    );
  });
});
