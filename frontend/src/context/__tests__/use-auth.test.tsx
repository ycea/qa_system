import { render } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { useAuth } from '../use-auth';

function Probe() {
  useAuth();
  return <div>ok</div>;
}

describe('useAuth', () => {
  it('бросает ошибку вне AuthProvider', () => {
    const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => render(<Probe />)).toThrow('useAuth must be used within AuthProvider');

    consoleError.mockRestore();
  });
});
