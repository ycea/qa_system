import { ThemeProvider } from '@mui/material';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, type RenderResult } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { vi } from 'vitest';
import { AuthContext, type AuthContextType } from '../context/auth-context';
import { theme } from '../theme';

/** Свежий QueryClient на каждый тест: без ретраев и с мгновенным сборщиком мусора. */
export const createTestQueryClient = (): QueryClient =>
  new QueryClient({
    defaultOptions: {
      queries: { retry: false, gcTime: 0, staleTime: 0 },
      mutations: { retry: false },
    },
  });

/** Значение AuthContext со замоканными методами — позволяет тестировать без провайдера. */
export const createAuthValue = (overrides: Partial<AuthContextType> = {}): AuthContextType => ({
  user: null,
  isLoading: false,
  login: vi.fn().mockResolvedValue(undefined),
  register: vi.fn().mockResolvedValue(undefined),
  logout: vi.fn().mockResolvedValue(undefined),
  ...overrides,
});

type RenderWithProvidersOptions = {
  queryClient?: QueryClient;
  initialEntries?: string[];
  auth?: AuthContextType;
};

export function renderWithProviders(
  ui: ReactNode,
  {
    queryClient = createTestQueryClient(),
    initialEntries = ['/'],
    auth,
  }: RenderWithProvidersOptions = {},
): RenderResult {
  const content = auth ? <AuthContext.Provider value={auth}>{ui}</AuthContext.Provider> : ui;

  return render(
    <QueryClientProvider client={queryClient}>
      <ThemeProvider theme={theme}>
        <MemoryRouter initialEntries={initialEntries}>{content}</MemoryRouter>
      </ThemeProvider>
    </QueryClientProvider>,
  );
}

/** Имитация ошибки axios, как её видит UI-код: err.response.data.errors / err.response.data.message. */
export const makeAxiosError = (data: unknown, status = 422) => ({
  isAxiosError: true,
  response: { status, data },
});

/** Promise, завершением которого управляет тест (для проверки промежуточных состояний). */
export const deferred = <T,>() => {
  let resolve!: (value: T) => void;
  let reject!: (reason?: unknown) => void;
  const promise = new Promise<T>((res, rej) => {
    resolve = res;
    reject = rej;
  });
  return { promise, resolve, reject };
};
