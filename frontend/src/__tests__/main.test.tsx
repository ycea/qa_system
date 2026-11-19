import { describe, expect, it, vi } from 'vitest';

const reactDomClient = vi.hoisted(() => ({ render: vi.fn(), createRoot: vi.fn() }));

vi.mock('react-dom/client', () => ({ createRoot: reactDomClient.createRoot }));

describe('main', () => {
  it('монтирует приложение в элемент #root', async () => {
    reactDomClient.createRoot.mockReturnValue({ render: reactDomClient.render });
    document.body.innerHTML = '<div id="root"></div>';
    const rootElement = document.getElementById('root');

    await import('../main');

    expect(reactDomClient.createRoot).toHaveBeenCalledWith(rootElement);
    expect(reactDomClient.render).toHaveBeenCalledTimes(1);
  }, 30_000);
});
