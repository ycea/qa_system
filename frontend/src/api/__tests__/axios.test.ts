import { describe, expect, it, vi } from 'vitest';

const axiosMock = vi.hoisted(() => ({ create: vi.fn() }));

vi.mock('axios', () => ({ default: axiosMock }));

describe('api/axios', () => {
  it('создаёт инстанс с baseURL /api, credentials и XSRF-настройками', async () => {
    axiosMock.create.mockReturnValue({});

    await import('../axios');

    expect(axiosMock.create).toHaveBeenCalledTimes(1);
    expect(axiosMock.create).toHaveBeenCalledWith({
      baseURL: '/api',
      withCredentials: true,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      xsrfCookieName: 'XSRF-TOKEN',
      xsrfHeaderName: 'X-XSRF-TOKEN',
    });
  });
});
