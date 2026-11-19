import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import {
  createTestQueryClient,
  deferred,
  makeAxiosError,
  renderWithProviders,
} from '../../test/test-utils';
import DictionariesTab from '../DictionariesTab';

const dictionariesApi = vi.hoisted(() => ({
  fetchDictionaries: vi.fn(),
  createDictionaryItem: vi.fn(),
  updateDictionaryItem: vi.fn(),
  deleteDictionaryItem: vi.fn(),
}));

vi.mock('../../api/dictionaries', () => dictionariesApi);

const defaultItem = { id: 1, project_id: 2, name: 'Новый', is_default: true, in_use: false };
const usedItem = { id: 2, project_id: 2, name: 'В работе', is_default: false, in_use: true };
const freeItem = { id: 3, project_id: 2, name: 'Закрыт', is_default: false, in_use: false };

const rowByName = (name: string) => screen.getByText(name).closest('tr') as HTMLElement;
const deleteButton = (name: string) => within(rowByName(name)).getAllByRole('button')[1];
const editButton = (name: string) => within(rowByName(name)).getAllByRole('button')[0];

const waitForTable = async () =>
  waitFor(() => expect(screen.queryByRole('progressbar')).not.toBeInTheDocument());

const openCreateDialog = async () => {
  await userEvent.click(screen.getByRole('button', { name: /Добавить в/ }));
  return screen.findByRole('dialog');
};

describe('DictionariesTab', () => {
  it('загружает статусы проекта по умолчанию', async () => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([defaultItem, usedItem, freeItem]);

    renderWithProviders(<DictionariesTab projectId={2} />);

    expect(await screen.findByText('В работе')).toBeInTheDocument();
    expect(dictionariesApi.fetchDictionaries).toHaveBeenCalledWith(2, 'statuses');
    expect(screen.getByText('(по умолчанию)')).toBeInTheDocument();
    expect(screen.getByText('Справочники проекта')).toBeInTheDocument();
  });

  it('показывает спиннер во время загрузки', () => {
    dictionariesApi.fetchDictionaries.mockReturnValue(deferred().promise);

    renderWithProviders(<DictionariesTab projectId={2} />);

    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });

  it('показывает ошибку, если справочник не загрузился', async () => {
    dictionariesApi.fetchDictionaries.mockRejectedValue(new Error('500'));

    renderWithProviders(<DictionariesTab projectId={2} />);

    expect(await screen.findByText('Не удалось загрузить справочник.')).toBeInTheDocument();
  });

  it('переключает тип справочника', async () => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([]);

    renderWithProviders(<DictionariesTab projectId={2} />);
    await waitForTable();

    await userEvent.click(screen.getByRole('tab', { name: 'Серьёзности' }));

    await waitFor(() =>
      expect(dictionariesApi.fetchDictionaries).toHaveBeenCalledWith(2, 'severities'),
    );
    expect(screen.getByRole('button', { name: 'Добавить в «Серьёзности»' })).toBeInTheDocument();
  });

  it('блокирует удаление дефолтных и используемых элементов', async () => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([defaultItem, usedItem, freeItem]);

    renderWithProviders(<DictionariesTab projectId={2} />);
    await screen.findByText('Закрыт');

    expect(deleteButton('Новый')).toBeDisabled();
    expect(deleteButton('В работе')).toBeDisabled();
    expect(deleteButton('Закрыт')).toBeEnabled();
  });

  it('удаляет элемент после подтверждения и инвалидирует кэш', async () => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([freeItem]);
    dictionariesApi.deleteDictionaryItem.mockResolvedValue(undefined);
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const queryClient = createTestQueryClient();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');

    renderWithProviders(<DictionariesTab projectId={2} />, { queryClient });
    await screen.findByText('Закрыт');

    await userEvent.click(deleteButton('Закрыт'));

    await waitFor(() =>
      expect(dictionariesApi.deleteDictionaryItem).toHaveBeenCalledWith(2, 'statuses', freeItem.id),
    );
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['dictionaries', 2, 'statuses'] });
  });

  it('не удаляет элемент при отказе в confirm', async () => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([freeItem]);
    vi.spyOn(window, 'confirm').mockReturnValue(false);

    renderWithProviders(<DictionariesTab projectId={2} />);
    await screen.findByText('Закрыт');

    await userEvent.click(deleteButton('Закрыт'));

    expect(dictionariesApi.deleteDictionaryItem).not.toHaveBeenCalled();
  });

  it('показывает alert при ошибке удаления', async () => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([freeItem]);
    dictionariesApi.deleteDictionaryItem.mockRejectedValue(
      makeAxiosError({ message: 'Нельзя удалить используемый статус' }),
    );
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

    renderWithProviders(<DictionariesTab projectId={2} />);
    await screen.findByText('Закрыт');

    await userEvent.click(deleteButton('Закрыт'));

    await waitFor(() =>
      expect(alertSpy).toHaveBeenCalledWith('Нельзя удалить используемый статус'),
    );
  });

  it('создаёт элемент справочника', async () => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([]);
    dictionariesApi.createDictionaryItem.mockResolvedValue({ ...freeItem, name: 'Готово' });

    renderWithProviders(<DictionariesTab projectId={2} />);
    await waitForTable();

    const dialog = await openCreateDialog();
    expect(within(dialog).getByText('Создать элемент')).toBeInTheDocument();

    const input = within(dialog).getByLabelText('Название');
    const save = within(dialog).getByRole('button', { name: 'Сохранить' });
    expect(save).toBeDisabled();

    await userEvent.type(input, '   ');
    expect(save).toBeDisabled();

    await userEvent.clear(input);
    await userEvent.type(input, 'Готово');
    expect(save).toBeEnabled();
    await userEvent.click(save);

    await waitFor(() =>
      expect(dictionariesApi.createDictionaryItem).toHaveBeenCalledWith(2, 'statuses', 'Готово'),
    );
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
  });

  it('редактирует элемент справочника', async () => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([freeItem]);
    dictionariesApi.updateDictionaryItem.mockResolvedValue({ ...freeItem, name: 'Закрыт 2' });

    renderWithProviders(<DictionariesTab projectId={2} />);
    await screen.findByText('Закрыт');

    await userEvent.click(editButton('Закрыт'));
    const dialog = await screen.findByRole('dialog');
    expect(within(dialog).getByText('Редактировать элемент')).toBeInTheDocument();

    const input = within(dialog).getByLabelText('Название');
    expect(input).toHaveValue('Закрыт');
    await userEvent.clear(input);
    await userEvent.type(input, 'Закрыт 2');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Сохранить' }));

    await waitFor(() =>
      expect(dictionariesApi.updateDictionaryItem).toHaveBeenCalledWith(
        2,
        'statuses',
        3,
        'Закрыт 2',
      ),
    );
  });

  it.each([
    [makeAxiosError({ errors: { name: ['Название уже занято'] } }), 'Название уже занято'],
    [makeAxiosError({ message: 'Ошибка сервера' }), 'Ошибка сервера'],
    [new Error('network'), 'Не удалось сохранить'],
  ])('показывает ошибку сохранения (case %#)', async (error, expected) => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([]);
    dictionariesApi.createDictionaryItem.mockRejectedValue(error);

    renderWithProviders(<DictionariesTab projectId={2} />);
    await waitForTable();

    const dialog = await openCreateDialog();
    await userEvent.type(within(dialog).getByLabelText('Название'), 'Дубликат');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Сохранить' }));

    expect(await screen.findByText(expected)).toBeInTheDocument();
  });

  it('закрывает диалог по кнопке Отмена', async () => {
    dictionariesApi.fetchDictionaries.mockResolvedValue([]);

    renderWithProviders(<DictionariesTab projectId={2} />);
    await waitForTable();

    const dialog = await openCreateDialog();
    await userEvent.click(within(dialog).getByRole('button', { name: 'Отмена' }));

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(dictionariesApi.createDictionaryItem).not.toHaveBeenCalled();
  });
});
