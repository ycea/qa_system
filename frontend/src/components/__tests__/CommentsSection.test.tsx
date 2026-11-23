import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { makeAxiosError, renderWithProviders } from '../../test/test-utils';
import CommentsSection from '../CommentsSection';

const commentsApi = vi.hoisted(() => ({
  fetchComments: vi.fn(),
  addComment: vi.fn(),
}));

vi.mock('../../api/comments', () => commentsApi);

describe('CommentsSection', () => {
  beforeEach(() => {
    commentsApi.fetchComments.mockResolvedValue([]);
  });

  it('показывает пустое состояние и запрашивает комментарии', async () => {
    renderWithProviders(<CommentsSection projectId={7} bugId={5} />);

    expect(await screen.findByText('Пока нет комментариев.')).toBeInTheDocument();
    expect(commentsApi.fetchComments).toHaveBeenCalledWith(7, 5);
  });

  it('рендерит комментарии с автором', async () => {
    commentsApi.fetchComments.mockResolvedValue([
      {
        id: 1,
        body: 'Первый комментарий',
        created_at: '2026-01-01T10:00:00Z',
        user: { id: 2, name: 'dev' },
      },
    ]);

    renderWithProviders(<CommentsSection projectId={7} bugId={5} />);

    expect(await screen.findByText('Первый комментарий')).toBeInTheDocument();
    expect(screen.getByText(/dev/)).toBeInTheDocument();
  });

  it('добавляет комментарий', async () => {
    commentsApi.addComment.mockResolvedValue({ message: 'ok', comment: {} });

    renderWithProviders(<CommentsSection projectId={7} bugId={5} />);
    await screen.findByText('Пока нет комментариев.');

    const submit = screen.getByRole('button', { name: 'Отправить' });
    expect(submit).toBeDisabled();

    await userEvent.type(screen.getByLabelText('Новый комментарий'), 'Текст');
    await userEvent.click(submit);

    await waitFor(() => expect(commentsApi.addComment).toHaveBeenCalledWith(7, 5, 'Текст'));
  });

  it('показывает сообщение об ошибке добавления', async () => {
    commentsApi.addComment.mockRejectedValue(makeAxiosError({ message: 'Нельзя добавить' }));

    renderWithProviders(<CommentsSection projectId={7} bugId={5} />);
    await screen.findByText('Пока нет комментариев.');

    await userEvent.type(screen.getByLabelText('Новый комментарий'), 'Текст');
    await userEvent.click(screen.getByRole('button', { name: 'Отправить' }));

    expect(await screen.findByText('Нельзя добавить')).toBeInTheDocument();
  });
});
