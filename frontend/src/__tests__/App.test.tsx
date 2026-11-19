import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import App from '../App';

const authApi = vi.hoisted(() => ({
  me: vi.fn(),
  login: vi.fn(),
  register: vi.fn(),
  logout: vi.fn(),
}));

vi.mock('../api/auth', () => authApi);

describe('App', () => {
  it('перенаправляет неавторизованного пользователя на страницу входа', async () => {
    authApi.me.mockRejectedValue(new Error('401'));

    render(<App />);

    expect(await screen.findByRole('heading', { name: 'Вход' })).toBeInTheDocument();
    expect(screen.getByLabelText(/^Логин/)).toBeInTheDocument();
  });
});
