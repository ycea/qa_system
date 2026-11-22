import { describe, expect, it, vi } from 'vitest';
import { fetchProjectMeta } from '../meta';

const apiMock = vi.hoisted(() => ({ get: vi.fn() }));

vi.mock('../axios', () => ({ default: apiMock }));

describe('api/meta', () => {
  it('fetchProjectMeta запрашивает метаданные проекта', async () => {
    const meta = {
      members: [],
      statuses: [{ id: 1, project_id: 7, name: 'Новая', in_use: false }],
      priorities: [],
      severities: [],
    };
    apiMock.get.mockResolvedValue({ data: meta });

    await expect(fetchProjectMeta(7)).resolves.toEqual(meta);
    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/meta');
  });
});
