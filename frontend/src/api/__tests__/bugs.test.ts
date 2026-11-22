import { describe, expect, it, vi } from 'vitest';
import {
  assignBug,
  changeBugStatus,
  createBug,
  fetchBug,
  fetchBugHistory,
  fetchBugs,
  updateBug,
} from '../bugs';

const apiMock = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  patch: vi.fn(),
}));

vi.mock('../axios', () => ({ default: apiMock }));

const bug = {
  id: 3,
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
  created_at: '2026-01-01',
  updated_at: '2026-01-02',
};

describe('api/bugs', () => {
  it('fetchBugs передаёт только заполненные фильтры', async () => {
    const page = { data: [bug], current_page: 1, last_page: 1, per_page: 30, total: 1 };
    apiMock.get.mockResolvedValue({ data: page });

    await expect(fetchBugs(7, { search: 'Login', status_id: 5, priority_id: '' })).resolves.toEqual(
      page,
    );

    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/bugs', {
      params: { search: 'Login', status_id: 5 },
    });
  });

  it('fetchBugs без фильтров отправляет пустые params', async () => {
    apiMock.get.mockResolvedValue({
      data: { data: [], current_page: 1, last_page: 1, per_page: 30, total: 0 },
    });

    await fetchBugs(7);

    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/bugs', { params: {} });
  });

  it('fetchBugs передаёт параметры пагинации', async () => {
    apiMock.get.mockResolvedValue({
      data: { data: [], current_page: 2, last_page: 2, per_page: 50, total: 60 },
    });

    await fetchBugs(7, {}, { page: 2, per_page: 50 });

    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/bugs', {
      params: { page: 2, per_page: 50 },
    });
  });

  it('fetchBug запрашивает детали по id', async () => {
    apiMock.get.mockResolvedValue({ data: { ...bug, histories: [] } });

    await expect(fetchBug(7, 3)).resolves.toEqual({ ...bug, histories: [] });
    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/bugs/3');
  });

  it('createBug отправляет payload', async () => {
    apiMock.post.mockResolvedValue({ data: { message: 'Баг создан.', bug } });

    const payload = {
      title: 'Краш',
      description: 'Описание',
      steps_to_reproduce: 'Шаги',
      priority_id: 2,
      severity_id: 3,
      status_id: 1,
    };
    const result = await createBug(7, payload);

    expect(apiMock.post).toHaveBeenCalledWith('/projects/7/bugs', payload);
    expect(result).toEqual({ message: 'Баг создан.', bug });
  });

  it('updateBug отправляет изменения', async () => {
    apiMock.put.mockResolvedValue({ data: { message: 'Баг обновлён.', bug } });

    await updateBug(7, 3, {
      title: 'Новый',
      description: 'd',
      steps_to_reproduce: 's',
      priority_id: 2,
      severity_id: 3,
    });

    expect(apiMock.put).toHaveBeenCalledWith('/projects/7/bugs/3', {
      title: 'Новый',
      description: 'd',
      steps_to_reproduce: 's',
      priority_id: 2,
      severity_id: 3,
    });
  });

  it('changeBugStatus шлёт PATCH со status_id', async () => {
    apiMock.patch.mockResolvedValue({ data: { message: 'Статус изменён.', bug } });

    await changeBugStatus(7, 3, 9);

    expect(apiMock.patch).toHaveBeenCalledWith('/projects/7/bugs/3/status', { status_id: 9 });
  });

  it('assignBug шлёт PATCH с assignee_id (в т.ч. null)', async () => {
    apiMock.patch.mockResolvedValue({ data: { message: 'Исполнитель назначен.', bug } });

    await assignBug(7, 3, null);

    expect(apiMock.patch).toHaveBeenCalledWith('/projects/7/bugs/3/assignee', {
      assignee_id: null,
    });
  });

  it('fetchBugHistory возвращает список items', async () => {
    apiMock.get.mockResolvedValue({ data: { items: [{ id: 1 }] } });

    await expect(fetchBugHistory(7, 3)).resolves.toEqual([{ id: 1 }]);
    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/bugs/3/history');
  });
});
