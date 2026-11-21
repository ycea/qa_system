import { screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import AuthCard from '../AuthCard';

describe('AuthCard', () => {
  it('рендерит заголовок как h1 и дочерние элементы', () => {
    renderWithProviders(
      <AuthCard title="Вход">
        <p>содержимое формы</p>
      </AuthCard>,
    );

    expect(screen.getByRole('heading', { level: 1, name: 'Вход' })).toBeInTheDocument();
    expect(screen.getByText('содержимое формы')).toBeInTheDocument();
  });

  it('рендерит подзаголовок, если он передан', () => {
    renderWithProviders(
      <AuthCard title="Вход" subtitle="Войдите в систему">
        <p>content</p>
      </AuthCard>,
    );

    expect(screen.getByText('Войдите в систему')).toBeInTheDocument();
  });

  it('не рендерит подзаголовок, если он не передан', () => {
    renderWithProviders(
      <AuthCard title="Вход">
        <p>content</p>
      </AuthCard>,
    );

    expect(screen.queryByText('Войдите в систему')).not.toBeInTheDocument();
  });
});
