import { createTheme } from '@mui/material/styles';

export const theme = createTheme({
  palette: {
    mode: 'light',
    primary: {
      main: '#0f172a', // глубокий slate-indigo — основной
      light: '#334155',
      dark: '#020617',
      contrastText: '#ffffff',
    },
    secondary: {
      main: '#06b6d4', // бирюзовый акцент
      light: '#67e8f9',
      dark: '#0e7490',
      contrastText: '#ffffff',
    },
    background: {
      default: '#f8fafc', // очень светлый серо-голубой
      paper: '#ffffff',
    },
    text: {
      primary: '#0f172a',
      secondary: '#475569',
    },
    divider: '#e2e8f0',
  },
  shape: {
    borderRadius: 12,
  },
  typography: {
    fontFamily: [
      'Inter',
      'Roboto',
      '-apple-system',
      'BlinkMacSystemFont',
      '"Segoe UI"',
      'sans-serif',
    ].join(','),
    h4: { fontWeight: 700, letterSpacing: -0.5 },
    h5: { fontWeight: 600 },
    h6: { fontWeight: 600 },
    button: { textTransform: 'none', fontWeight: 500 },
  },
  components: {
    MuiCssBaseline: {
      styleOverrides: {
        // Убираем белые полосы и коричневый фон автозаполнения
        html: { height: '100%' },
        body: { height: '100%', margin: 0 },
        '#root': { height: '100%', minHeight: '100vh' },
        'input:-webkit-autofill, input:-webkit-autofill:hover, input:-webkit-autofill:focus': {
          WebkitBoxShadow: '0 0 0 1000px #ffffff inset',
          WebkitTextFillColor: '#0f172a',
          transition: 'background-color 5000s ease-in-out 0s',
        },
      },
    },
    MuiButton: {
      styleOverrides: {
        root: {
          borderRadius: 10,
          paddingInline: 22,
          paddingBlock: 10,
          '&.MuiButton-containedPrimary': {
            boxShadow: '0 6px 18px 0 rgba(15, 23, 42, 0.25)',
            '&:hover': {
              boxShadow: '0 8px 24px 0 rgba(15, 23, 42, 0.35)',
            },
          },
        },
      },
    },
    MuiTextField: {
      defaultProps: {
        variant: 'outlined',
        size: 'medium',
      },
    },
    MuiOutlinedInput: {
      styleOverrides: {
        root: {
          borderRadius: 10,
          backgroundColor: '#ffffff',
          '& fieldset': {
            borderColor: '#cbd5e1',
          },
          '&:hover fieldset': {
            borderColor: '#94a3b8',
          },
          '&.Mui-focused fieldset': {
            borderColor: '#0f172a',
            borderWidth: 2,
          },
        },
      },
    },
    MuiPaper: {
      styleOverrides: {
        root: {
          backgroundImage: 'none',
        },
      },
    },
    MuiAppBar: {
      styleOverrides: {
        root: {
          backgroundImage: 'none',
        },
      },
    },
  },
});
