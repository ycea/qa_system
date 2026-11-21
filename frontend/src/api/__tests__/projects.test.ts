import { describe, expect, it, vi } from 'vitest';
import { createProject, deleteProject, fetchProject, fetchProjects } from '../projects';

const apiMock = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}));

vi.mock('../axios', () => ({ default: apiMock }));

const project = {
  id: 5,
  name: 'QMS',
  description: null,
  owner_id: 1,
  bugs_count: 3,
  created_at: '2026-01-01',
  updated_at: '2026-01-02',
};

describe('api/projects', () => {
  it('fetchProjects возвращает список проектов', async () => {
    apiMock.get.mockResolvedValue({ data: [project] });

    await expect(fetchProjects()).resolves.toEqual([project]);
    expect(apiMock.get).toHaveBeenCalledWith('/projects');
  });

  it('fetchProject запрашивает проект по id', async () => {
    apiMock.get.mockResolvedValue({ data: project });

    await expect(fetchProject(5)).resolves.toEqual(project);
    expect(apiMock.get).toHaveBeenCalledWith('/projects/5');
  });

  it('createProject отправляет данные и возвращает ответ целиком', async () => {
    apiMock.post.mockResolvedValue({ data: { message: 'created', project } });

    const result = await createProject({ name: 'QMS', description: 'описание' });

    expect(apiMock.post).toHaveBeenCalledWith('/projects', {
      name: 'QMS',
      description: 'описание',
    });
    expect(result).toEqual({ message: 'created', project });
  });

  it('createProject не отправляет описание, если оно не передано', async () => {
    apiMock.post.mockResolvedValue({ data: { message: 'created', project } });

    await createProject({ name: 'QMS' });

    expect(apiMock.post).toHaveBeenCalledWith('/projects', { name: 'QMS' });
  });

  it('deleteProject удаляет проект по id', async () => {
    apiMock.delete.mockResolvedValue({});

    await deleteProject(5);

    expect(apiMock.delete).toHaveBeenCalledWith('/projects/5');
  });
});
