import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach, vi } from 'vitest';

// RTL включает act-окружение только при наличии глобального afterEach,
// поэтому глобальные моки и cleanup описываем вручную (globals: false).
(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

// jsdom не реализует window.matchMedia — нужен для MUI (responsive sx/useMediaQuery)
Object.defineProperty(window, 'matchMedia', {
  configurable: true,
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  }),
});

// jsdom не реализует ResizeObserver — нужен для MUI (Tabs, Select, Dialog)
class ResizeObserverMock {
  observe = vi.fn();
  unobserve = vi.fn();
  disconnect = vi.fn();
}
globalThis.ResizeObserver = ResizeObserverMock as unknown as typeof ResizeObserver;

// jsdom не реализует scrollIntoView — нужен для MUI Select/Tooltip
Element.prototype.scrollIntoView = vi.fn();
window.scrollTo = vi.fn() as unknown as typeof window.scrollTo;

afterEach(() => {
  cleanup();
});
