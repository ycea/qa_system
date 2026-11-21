import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Route, Routes } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { createAuthValue, renderWithProviders } from '../../test/test-utils';
import MainLayout from '../MainLayout';

const routeMocks = vi.hoisted(() => ({ navigate: vi.fn() }));

vi.mock('react-router-dom', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router-dom')>();
  return { ...actual, useNavigate: () => routeMocks.navigate };
});

const alice = { id: 1, name: 'alice', email: 'alice@example.com' };

const renderLayout = (auth = createAuthValue()) =>
  renderWithProviders(
    <Routes>
      <Route path="/" element={<MainLayout />}>
        <Route index element={<div>страница проектов</div>} />
      </Route>
    </Routes>,
    { auth },
  );

describe('MainLayout', () => {
  it('показывает логин, аватар пользователя и содержимое вложенного маршрута', () => {
    renderLayout(createAuthValue({ user: alice }));

    expect(screen.getByText('alice')).toBeInTheDocument();
    expect(screen.getByText('A')).toBeInTheDocument();
    expect(screen.getByText('страница проектов')).toBeInTheDocument();
    expect(screen.getByText('QMS')).toBeInTheDocument();
  });

  it('не показывает блок пользователя без авторизации', () => {
    renderLayout();

    expect(screen.queryByText('alice')).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Выйти' })).toBeInTheDocument();
  });

  it('выходит из системы и переходит на /login', async () => {
    const logout = vi.fn().mockResolvedValue(undefined);

    renderLayout(createAuthValue({ user: alice, logout }));
    await userEvent.click(screen.getByRole('button', { name: 'Выйти' }));

    expect(logout).toHaveBeenCalledTimes(1);
    expect(routeMocks.navigate).toHaveBeenCalledWith('/login');
  });

  it('переходит на главную по клику на логотип', async () => {
    renderLayout();

    await userEvent.click(screen.getByText('QMS'));

    expect(routeMocks.navigate).toHaveBeenCalledWith('/');
  });
});
