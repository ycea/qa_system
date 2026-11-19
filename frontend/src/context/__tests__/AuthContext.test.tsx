import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { AuthProvider } from '../AuthContext';
import { useAuth } from '../use-auth';

const authApi = vi.hoisted(() => ({
  me: vi.fn(),
  login: vi.fn(),
  register: vi.fn(),
  logout: vi.fn(),
}));

vi.mock('../../api/auth', () => authApi);

const user = { id: 1, name: 'alice', email: 'alice@example.com' };

function Probe() {
  const { user: currentUser, isLoading, login, register, logout } = useAuth();

  return (
    <div>
      <span data-testid="loading">{isLoading ? 'loading' : 'ready'}</span>
      <span data-testid="user">{currentUser ? currentUser.name : 'none'}</span>
      <button onClick={() => login({ name: 'alice', password: 'secret' })}>login</button>
      <button
        onClick={() =>
          register({
            name: 'bob',
            email: 'bob@example.com',
            password: 'secret',
            password_confirmation: 'secret',
          })
        }
      >
        register
      </button>
      <button onClick={() => logout()}>logout</button>
    </div>
  );
}

const renderProvider = () =>
  render(
    <AuthProvider>
      <Probe />
    </AuthProvider>,
  );

describe('AuthProvider', () => {
  it('загружает пользователя через me() при монтировании', async () => {
    authApi.me.mockResolvedValue(user);

    renderProvider();

    expect(screen.getByTestId('loading')).toHaveTextContent('loading');
    await waitFor(() => expect(screen.getByTestId('user')).toHaveTextContent('alice'));
    expect(screen.getByTestId('loading')).toHaveTextContent('ready');
    expect(authApi.me).toHaveBeenCalledTimes(1);
  });

  it('оставляет пользователя пустым, если me() завершился ошибкой', async () => {
    authApi.me.mockRejectedValue(new Error('401'));

    renderProvider();

    await waitFor(() => expect(screen.getByTestId('loading')).toHaveTextContent('ready'));
    expect(screen.getByTestId('user')).toHaveTextContent('none');
  });

  it('login сохраняет пользователя из ответа', async () => {
    authApi.me.mockRejectedValue(new Error('401'));
    authApi.login.mockResolvedValue({ message: 'ok', user });

    renderProvider();
    await waitFor(() => expect(screen.getByTestId('loading')).toHaveTextContent('ready'));

    await userEvent.click(screen.getByRole('button', { name: 'login' }));

    await waitFor(() => expect(screen.getByTestId('user')).toHaveTextContent('alice'));
    expect(authApi.login).toHaveBeenCalledWith({ name: 'alice', password: 'secret' });
  });

  it('register сохраняет пользователя из ответа', async () => {
    authApi.me.mockRejectedValue(new Error('401'));
    authApi.register.mockResolvedValue({ message: 'created', user: { ...user, name: 'bob' } });

    renderProvider();
    await waitFor(() => expect(screen.getByTestId('loading')).toHaveTextContent('ready'));

    await userEvent.click(screen.getByRole('button', { name: 'register' }));

    await waitFor(() => expect(screen.getByTestId('user')).toHaveTextContent('bob'));
    expect(authApi.register).toHaveBeenCalledWith({
      name: 'bob',
      email: 'bob@example.com',
      password: 'secret',
      password_confirmation: 'secret',
    });
  });

  it('logout очищает пользователя', async () => {
    authApi.me.mockResolvedValue(user);
    authApi.logout.mockResolvedValue(undefined);

    renderProvider();
    await waitFor(() => expect(screen.getByTestId('user')).toHaveTextContent('alice'));

    await userEvent.click(screen.getByRole('button', { name: 'logout' }));

    await waitFor(() => expect(screen.getByTestId('user')).toHaveTextContent('none'));
    expect(authApi.logout).toHaveBeenCalledTimes(1);
  });
});
