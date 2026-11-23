import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { renderWithProviders } from '../../test/test-utils';
import MetricFormDialog from '../MetricFormDialog';

describe('MetricFormDialog', () => {
  it('создаёт числовую метрику', async () => {
    const onSubmit = vi.fn();

    renderWithProviders(<MetricFormDialog open onSubmit={onSubmit} onClose={vi.fn()} />);
    const dialog = screen.getByRole('dialog');

    expect(within(dialog).getByText('Создать метрику')).toBeInTheDocument();
    expect(within(dialog).getByRole('button', { name: 'Сохранить' })).toBeDisabled();

    await userEvent.type(within(dialog).getByLabelText('Название метрики'), 'Время');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Сохранить' }));

    expect(onSubmit).toHaveBeenCalledWith({ name: 'Время', type: 'numeric', options: '' });
  });

  it('для категориальной метрики требует варианты значений', async () => {
    const onSubmit = vi.fn();

    renderWithProviders(<MetricFormDialog open onSubmit={onSubmit} onClose={vi.fn()} />);
    const dialog = screen.getByRole('dialog');

    await userEvent.type(within(dialog).getByLabelText('Название метрики'), 'Компонент');
    await userEvent.click(within(dialog).getByLabelText('Тип метрики'));
    await userEvent.click(await screen.findByRole('option', { name: 'Категориальная' }));

    expect(within(dialog).getByLabelText('Варианты (через запятую)')).toBeInTheDocument();
    expect(within(dialog).getByRole('button', { name: 'Сохранить' })).toBeDisabled();

    await userEvent.type(within(dialog).getByLabelText('Варианты (через запятую)'), 'UI, API');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Сохранить' }));

    expect(onSubmit).toHaveBeenCalledWith({
      name: 'Компонент',
      type: 'categorical',
      options: 'UI, API',
    });
  });

  it('предзаполняет форму при редактировании', () => {
    const metric = {
      id: 3,
      project_id: 7,
      name: 'Компонент',
      type: 'categorical' as const,
      options: ['UI', 'API'],
    };

    renderWithProviders(
      <MetricFormDialog open metric={metric} onSubmit={vi.fn()} onClose={vi.fn()} />,
    );
    const dialog = screen.getByRole('dialog');

    expect(within(dialog).getByText('Редактировать метрику')).toBeInTheDocument();
    expect((within(dialog).getByLabelText('Название метрики') as HTMLInputElement).value).toBe(
      'Компонент',
    );
    expect(
      (within(dialog).getByLabelText('Варианты (через запятую)') as HTMLInputElement).value,
    ).toBe('UI, API');
  });

  it('закрывается по кнопке Отмена', async () => {
    const onClose = vi.fn();

    renderWithProviders(<MetricFormDialog open onSubmit={vi.fn()} onClose={onClose} />);

    await userEvent.click(screen.getByRole('button', { name: 'Отмена' }));

    expect(onClose).toHaveBeenCalledTimes(1);
  });
});
