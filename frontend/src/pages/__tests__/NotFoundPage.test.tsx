import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import NotFoundPage from '../NotFoundPage';

describe('NotFoundPage', () => {
  it('показывает сообщение о ненайденной странице', () => {
    render(<NotFoundPage />);

    expect(screen.getByText('404 — Страница не найдена')).toBeInTheDocument();
  });
});
