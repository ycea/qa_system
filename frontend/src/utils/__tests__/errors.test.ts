import { describe, expect, it } from 'vitest';
import { extractErrorMessage } from '../errors';

describe('utils/errors', () => {
  it('берёт первое поле из errors', () => {
    const err = {
      response: { data: { errors: { name: ['Логин занят'], email: ['Email занят'] } } },
    };

    expect(extractErrorMessage(err, 'fallback')).toBe('Логин занят');
  });

  it('игнорирует пустой массив ошибок и берёт message', () => {
    const err = { response: { data: { errors: { name: [] }, message: 'Ошибка сервера' } } };

    expect(extractErrorMessage(err, 'fallback')).toBe('Ошибка сервера');
  });

  it('берёт message, если поля ошибок нет', () => {
    expect(extractErrorMessage({ response: { data: { message: 'Сообщение' } } }, 'fallback')).toBe(
      'Сообщение',
    );
  });

  it('возвращает fallback для неизвестной ошибки', () => {
    expect(extractErrorMessage(new Error('network'), 'fallback')).toBe('fallback');
    expect(extractErrorMessage(null, 'fallback')).toBe('fallback');
    expect(extractErrorMessage({}, 'fallback')).toBe('fallback');
  });
});
