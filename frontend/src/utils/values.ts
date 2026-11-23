/** Приводит значение Select (string | number | unknown) к nullable числовому id. */
export const toNullableId = (value: unknown): number | null =>
  value === '' || value === null || value === undefined ? null : Number(value);
