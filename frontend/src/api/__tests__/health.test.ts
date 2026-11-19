import { describe, expect, it, vi } from 'vitest';
import { fetchHealth } from '../health';

const apiMock = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}));

vi.mock('../axios', () => ({ default: apiMock }));

describe('api/health', () => {
  it('fetchHealth запрашивает /health и возвращает ответ', async () => {
    apiMock.get.mockResolvedValue({ data: { status: 'ok' } });

    await expect(fetchHealth()).resolves.toEqual({ status: 'ok' });
    expect(apiMock.get).toHaveBeenCalledWith('/health');
  });
});
