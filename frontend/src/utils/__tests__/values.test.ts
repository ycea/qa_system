import { describe, expect, it } from 'vitest';
import { toNullableId } from '../values';

describe('utils/values', () => {
  it('возвращает null для пустых значений', () => {
    expect(toNullableId('')).toBeNull();
    expect(toNullableId(null)).toBeNull();
    expect(toNullableId(undefined)).toBeNull();
  });

  it('приводит непустое значение к числу', () => {
    expect(toNullableId('5')).toBe(5);
    expect(toNullableId(7)).toBe(7);
  });
});
