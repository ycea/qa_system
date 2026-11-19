import { describe, expect, it, vi } from 'vitest';
import {
  createDictionaryItem,
  deleteDictionaryItem,
  fetchDictionaries,
  updateDictionaryItem,
} from '../dictionaries';

const apiMock = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}));

vi.mock('../axios', () => ({ default: apiMock }));

const item = { id: 9, project_id: 3, name: 'Новый', is_default: false, in_use: false };

describe('api/dictionaries', () => {
  it('fetchDictionaries запрашивает справочник проекта и распаковывает items', async () => {
    apiMock.get.mockResolvedValue({ data: { items: [item] } });

    await expect(fetchDictionaries(3, 'priorities')).resolves.toEqual([item]);
    expect(apiMock.get).toHaveBeenCalledWith('/projects/3/dictionaries/priorities');
  });

  it.each(['statuses', 'priorities', 'severities'] as const)(
    'fetchDictionaries подставляет тип %s в URL',
    async (type) => {
      apiMock.get.mockResolvedValue({ data: { items: [] } });

      await fetchDictionaries(8, type);

      expect(apiMock.get).toHaveBeenCalledWith(`/projects/8/dictionaries/${type}`);
    },
  );

  it('createDictionaryItem отправляет название и распаковывает item', async () => {
    apiMock.post.mockResolvedValue({ data: { item } });

    await expect(createDictionaryItem(3, 'statuses', 'Новый')).resolves.toEqual(item);
    expect(apiMock.post).toHaveBeenCalledWith('/projects/3/dictionaries/statuses', {
      name: 'Новый',
    });
  });

  it('updateDictionaryItem отправляет название и распаковывает item', async () => {
    apiMock.put.mockResolvedValue({ data: { item } });

    await expect(updateDictionaryItem(3, 'severities', 9, 'Обновлённый')).resolves.toEqual(item);
    expect(apiMock.put).toHaveBeenCalledWith('/projects/3/dictionaries/severities/9', {
      name: 'Обновлённый',
    });
  });

  it('deleteDictionaryItem удаляет элемент справочника', async () => {
    apiMock.delete.mockResolvedValue({});

    await deleteDictionaryItem(3, 'priorities', 9);

    expect(apiMock.delete).toHaveBeenCalledWith('/projects/3/dictionaries/priorities/9');
  });
});
