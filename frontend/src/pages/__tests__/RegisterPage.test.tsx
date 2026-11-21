import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import {
  createAuthValue,
  deferred,
  makeAxiosError,
  renderWithProviders,
} from '../../test/test-utils';
import RegisterPage from '../RegisterPage';

const routeMocks = vi.hoisted(() => ({ navigate: vi.fn() }));

vi.mock('react-router-dom', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router-dom')>();
  return { ...actual, useNavigate: () => routeMocks.navigate };
});

const form = {
  name: 'bob',
  email: 'bob@example.com',
  password: 'secret123',
  password_confirmation: 'secret123',
};

const fillAndSubmit = async () => {
  await userEvent.type(screen.getByLabelText(/^Логин/), form.name);
  await userEvent.type(screen.getByLabelText(/^Email/), form.email);
  await userEvent.type(screen.getByLabelText(/^Пароль/), form.password);
  await userEvent.type(screen.getByLabelText(/Подтверждение пароля/), form.password_confirmation);
  await userEvent.click(screen.getByRole('button', { name: 'Зарегистрироваться' }));
};

describe('RegisterPage', () => {
  it('рендерит все поля формы и ссылку на вход', () => {
    renderWithProviders(<RegisterPage />, { auth: createAuthValue() });

    expect(screen.getByLabelText(/^Логин/)).toBeInTheDocument();
    expect(screen.getByLabelText(/^Email/)).toHaveAttribute('type', 'email');
    expect(screen.getByLabelText(/^Пароль/)).toBeInTheDocument();
    expect(screen.getByLabelText(/Подтверждение пароля/)).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Войти' })).toHaveAttribute('href', '/login');
  });

  it('отправляет заполненную форму и переходит на главную', async () => {
    const register = vi.fn().mockResolvedValue(undefined);

    renderWithProviders(<RegisterPage />, { auth: createAuthValue({ register }) });
    await fillAndSubmit();

    await waitFor(() => expect(register).toHaveBeenCalledWith(form));
    expect(routeMocks.navigate).toHaveBeenCalledWith('/');
  });

  it('блокирует кнопку на время запроса', async () => {
    const register = vi.fn().mockReturnValue(deferred().promise);

    renderWithProviders(<RegisterPage />, { auth: createAuthValue({ register }) });
    await fillAndSubmit();

    expect(screen.getByRole('button', { name: 'Регистрируем...' })).toBeDisabled();
  });

  it.each([
    [
      makeAxiosError({ errors: { email: ['Email уже занят'], name: ['Логин уже занят'] } }),
      'Email уже занят',
    ],
    [makeAxiosError({ message: 'Ошибка сервера' }), 'Ошибка сервера'],
    [new Error('network'), 'Не удалось зарегистрироваться.'],
  ])('показывает ошибку регистрации (case %#)', async (error, expected) => {
    const register = vi.fn().mockRejectedValue(error);

    renderWithProviders(<RegisterPage />, { auth: createAuthValue({ register }) });
    await fillAndSubmit();

    expect(await screen.findByText(expected)).toBeInTheDocument();
    expect(routeMocks.navigate).not.toHaveBeenCalled();
  });
});
