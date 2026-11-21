import { describe, expect, it } from 'vitest';
import { theme } from '../theme';

describe('theme', () => {
  it('содержит палитру проекта', () => {
    expect(theme.palette.mode).toBe('light');
    expect(theme.palette.primary.main).toBe('#0f172a');
    expect(theme.palette.secondary.main).toBe('#06b6d4');
    expect(theme.palette.background.default).toBe('#f8fafc');
  });

  it('настраивает форму и типографику', () => {
    expect(theme.shape.borderRadius).toBe(12);
    expect(theme.typography.fontFamily).toContain('Inter');
    expect(theme.typography.h4.fontWeight).toBe(700);
    expect(theme.typography.button.textTransform).toBe('none');
  });

  it('переопределяет стили и дефолты компонентов MUI', () => {
    const components = theme.components as unknown as Record<
      string,
      { defaultProps?: Record<string, unknown>; styleOverrides?: unknown }
    >;

    expect(components.MuiTextField.defaultProps?.variant).toBe('outlined');
    expect(components.MuiButton.styleOverrides).toBeDefined();
    expect(components.MuiCssBaseline.styleOverrides).toBeDefined();
    expect(components.MuiPaper.styleOverrides).toBeDefined();
    expect(components.MuiOutlinedInput.styleOverrides).toBeDefined();
    expect(components.MuiAppBar.styleOverrides).toBeDefined();
  });
});
