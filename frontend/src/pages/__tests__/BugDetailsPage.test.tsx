import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import BugDetailsPage from '../BugDetailsPage';

const routeMocks = vi.hoisted(() => ({
  navigate: vi.fn(),
  params: { id: '7', bugId: '5' } as Record<string, string | undefined>,
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
  fetchBug: vi.fn(),
  updateBug: vi.fn(),
  changeBugStatus: vi.fn(),
  assignBug: vi.fn(),
}));
vi.mock('../../api/bugs', () => bugsApi);

const metaApi = vi.hoisted(() => ({ fetchProjectMeta: vi.fn() }));
vi.mock('../../api/meta', () => metaApi);

const commentsApi = vi.hoisted(() => ({ fetchComments: vi.fn(), addComment: vi.fn() }));
vi.mock('../../api/comments', () => commentsApi);

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
  description: 'Описание бага',
  steps_to_reproduce: 'Шаги',
  closed_at: null,
  created_at: '2026-01-01T10:00:00Z',
  updated_at: '2026-01-01T10:00:00Z',
  priority: { id: 2, project_id: 7, name: 'low' },
  severity: { id: 3, project_id: 7, name: 'Minor' },
  assignee: null,
  histories: [
    {
      id: 1,
      action: 'created',
      field: null,
      old_value: null,
      new_value: null,
      created_at: '2026-01-01T10:00:00Z',
      user: { id: 1, name: 'owner' },
    },
  ],
};

describe('BugDetailsPage', () => {
  beforeEach(() => {
    routeMocks.params = { id: '7', bugId: '5' };
    metaApi.fetchProjectMeta.mockResolvedValue(meta);
    commentsApi.fetchComments.mockResolvedValue([]);
    bugsApi.fetchBug.mockResolvedValue(bug);
  });

  it('показывает детали бага и историю', async () => {
    renderWithProviders(<BugDetailsPage />);

    expect(await screen.findByRole('heading', { name: 'Краш' })).toBeInTheDocument();
    expect(screen.getByText('Описание бага')).toBeInTheDocument();
    expect(screen.getByText('Баг создан')).toBeInTheDocument();
  });

  it('возвращается к списку багов', async () => {
    renderWithProviders(<BugDetailsPage />);
    await screen.findByRole('heading', { name: 'Краш' });

    await userEvent.click(screen.getByRole('button', { name: '← Назад к списку багов' }));

    expect(routeMocks.navigate).toHaveBeenCalledWith('/projects/7/bugs');
  });

  it('меняет статус на странице деталей', async () => {
    bugsApi.changeBugStatus.mockResolvedValue({ message: 'ok', bug });

    renderWithProviders(<BugDetailsPage />);
    await screen.findByRole('heading', { name: 'Краш' });

    await userEvent.click(screen.getByRole('combobox', { name: 'Статус' }));
    await userEvent.click(await screen.findByRole('option', { name: 'Закрыта' }));

    await waitFor(() => expect(bugsApi.changeBugStatus).toHaveBeenCalledWith(7, 5, 9));
  });

  it('показывает сообщение при недоступном баге', async () => {
    bugsApi.fetchBug.mockRejectedValue(new Error('404'));

    renderWithProviders(<BugDetailsPage />);

    expect(await screen.findByText('Баг не найден или доступ запрещён.')).toBeInTheDocument();
  });
});
