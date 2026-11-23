import { describe, expect, it, vi } from 'vitest';
import { addComment, fetchComments } from '../comments';

const apiMock = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn() }));

vi.mock('../axios', () => ({ default: apiMock }));

const comment = { id: 1, bug_id: 3, user_id: 2, body: 'текст' };

describe('api/comments', () => {
  it('fetchComments возвращает список комментариев', async () => {
    apiMock.get.mockResolvedValue({ data: { items: [comment] } });

    await expect(fetchComments(7, 3)).resolves.toEqual([comment]);
    expect(apiMock.get).toHaveBeenCalledWith('/projects/7/bugs/3/comments');
  });

  it('addComment отправляет тело комментария', async () => {
    apiMock.post.mockResolvedValue({ data: { message: 'Комментарий добавлен.', comment } });

    const result = await addComment(7, 3, 'текст');

    expect(apiMock.post).toHaveBeenCalledWith('/projects/7/bugs/3/comments', { body: 'текст' });
    expect(result).toEqual({ message: 'Комментарий добавлен.', comment });
  });
});
