import { screen } from '@testing-library/react';
import { Route, Routes } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import type { AuthContextType } from '../../context/auth-context';
import { createAuthValue, renderWithProviders } from '../../test/test-utils';
import ProtectedRoute from '../ProtectedRoute';

const renderProtected = (auth: AuthContextType) =>
  renderWithProviders(
    <Routes>
      <Route path="/login" element={<div>страница входа</div>} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <div>защищённое содержимое</div>
          </ProtectedRoute>
        }
      />
    </Routes>,
    { auth, initialEntries: ['/'] },
  );

describe('ProtectedRoute', () => {
  it('показывает спиннер, пока проверяется сессия', () => {
    renderProtected(createAuthValue({ isLoading: true }));

    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });

  it('перенаправляет неавторизованного пользователя на /login', () => {
    renderProtected(createAuthValue({ user: null }));

    expect(screen.getByText('страница входа')).toBeInTheDocument();
    expect(screen.queryByText('защищённое содержимое')).not.toBeInTheDocument();
  });

  it('рендерит содержимое авторизованному пользователю', () => {
    renderProtected(createAuthValue({ user: { id: 1, name: 'alice', email: 'a@a.ru' } }));

    expect(screen.getByText('защищённое содержимое')).toBeInTheDocument();
  });
});
