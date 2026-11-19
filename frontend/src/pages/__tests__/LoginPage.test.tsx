import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import {
  createAuthValue,
  deferred,
  makeAxiosError,
  renderWithProviders,
} from '../../test/test-utils';
import LoginPage from '../LoginPage';

const routeMocks = vi.hoisted(() => ({ navigate: vi.fn() }));

vi.mock('react-router-dom', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router-dom')>();
  return { ...actual, useNavigate: () => routeMocks.navigate };
});

const fillAndSubmit = async (name = 'alice', password = 'secret') => {
  await userEvent.type(screen.getByLabelText(/^Логин/), name);
  await userEvent.type(screen.getByLabelText(/^Пароль/), password);
  await userEvent.click(screen.getByRole('button', { name: 'Войти' }));
};

describe('LoginPage', () => {
  it('рендерит поля формы и ссылку на регистрацию', () => {
    renderWithProviders(<LoginPage />, { auth: createAuthValue() });

    expect(screen.getByLabelText(/^Логин/)).toBeInTheDocument();
    expect(screen.getByLabelText(/^Пароль/)).toHaveAttribute('type', 'password');
    expect(screen.getByRole('button', { name: 'Войти' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Зарегистрироваться' })).toHaveAttribute(
      'href',
      '/register',
    );
  });

  it('отправляет введённые данные и переходит на главную', async () => {
    const login = vi.fn().mockResolvedValue(undefined);

    renderWithProviders(<LoginPage />, { auth: createAuthValue({ login }) });
    await fillAndSubmit();

    await waitFor(() => expect(login).toHaveBeenCalledWith({ name: 'alice', password: 'secret' }));
    expect(routeMocks.navigate).toHaveBeenCalledWith('/');
  });

  it('блокирует кнопку на время запроса', async () => {
    const login = vi.fn().mockReturnValue(deferred().promise);

    renderWithProviders(<LoginPage />, { auth: createAuthValue({ login }) });
    await fillAndSubmit();

    expect(screen.getByRole('button', { name: 'Входим...' })).toBeDisabled();
  });

  it.each([
    [makeAxiosError({ errors: { name: ['Неверный логин'] } }), 'Неверный логин'],
    [makeAxiosError({ message: 'Сервер недоступен' }), 'Сервер недоступен'],
    [new Error('network'), 'Не удалось войти. Проверьте данные.'],
  ])('показывает ошибку входа (case %#)', async (error, expected) => {
    const login = vi.fn().mockRejectedValue(error);

    renderWithProviders(<LoginPage />, { auth: createAuthValue({ login }) });
    await fillAndSubmit();

    expect(await screen.findByText(expected)).toBeInTheDocument();
    expect(routeMocks.navigate).not.toHaveBeenCalled();
  });
});
