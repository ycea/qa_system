import { describe, expect, it, vi } from 'vitest';
import { csrf, login, logout, me, register } from '../auth';

const apiMock = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}));

const axiosMock = vi.hoisted(() => ({ get: vi.fn() }));

vi.mock('../axios', () => ({ default: apiMock }));
vi.mock('axios', () => ({ default: axiosMock }));

const user = { id: 1, name: 'alice', email: 'alice@example.com' };

describe('api/auth', () => {
  it('csrf запрашивает cookie Sanctum', async () => {
    axiosMock.get.mockResolvedValue({});

    await csrf();

    expect(axiosMock.get).toHaveBeenCalledWith('/sanctum/csrf-cookie', { withCredentials: true });
  });

  it('login сначала получает csrf, затем отправляет данные на /login', async () => {
    axiosMock.get.mockResolvedValue({});
    apiMock.post.mockResolvedValue({ data: { message: 'ok', user } });

    const result = await login({ name: 'alice', password: 'secret' });

    expect(axiosMock.get).toHaveBeenCalledWith('/sanctum/csrf-cookie', { withCredentials: true });
    expect(apiMock.post).toHaveBeenCalledWith('/login', { name: 'alice', password: 'secret' });
    expect(result).toEqual({ message: 'ok', user });
  });

  it('register сначала получает csrf, затем отправляет данные на /register', async () => {
    axiosMock.get.mockResolvedValue({});
    const payload = {
      name: 'bob',
      email: 'bob@example.com',
      password: 'secret',
      password_confirmation: 'secret',
    };
    apiMock.post.mockResolvedValue({ data: { message: 'created', user } });

    const result = await register(payload);

    expect(axiosMock.get).toHaveBeenCalledTimes(1);
    expect(apiMock.post).toHaveBeenCalledWith('/register', payload);
    expect(result).toEqual({ message: 'created', user });
  });

  it('logout отправляет POST на /logout', async () => {
    apiMock.post.mockResolvedValue({});

    await logout();

    expect(apiMock.post).toHaveBeenCalledWith('/logout');
  });

  it('me возвращает текущего пользователя', async () => {
    apiMock.get.mockResolvedValue({ data: user });

    await expect(me()).resolves.toEqual(user);
    expect(apiMock.get).toHaveBeenCalledWith('/me');
  });
});
