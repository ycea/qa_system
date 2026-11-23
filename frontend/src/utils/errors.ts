/**
 * Достаёт текст ошибки из ответа axios (errors.<field>[0] | message | fallback).
 */
export const extractErrorMessage = (err: unknown, fallback: string): string => {
  const response = (err as { response?: { data?: Record<string, unknown> } })?.response;
  const data = response?.data as
    { message?: string; errors?: Record<string, string[] | undefined> } | undefined;

  const firstFieldError = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;

  return firstFieldError || data?.message || fallback;
};
