import { Alert, Button, Link, TextField, Typography } from '@mui/material';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import AuthCard from '../components/AuthCard';
import { useAuth } from '../context/use-auth';

export default function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [name, setName] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await login({ name, password });
      navigate('/');
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
    } catch (err: any) {
      const message =
        err?.response?.data?.errors?.name?.[0] ||
        err?.response?.data?.message ||
        'Не удалось войти. Проверьте данные.';
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthCard title="Вход" subtitle="Войдите в систему управления качеством">
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        <TextField
          label="Логин"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
          fullWidth
          autoFocus
        />
        <TextField
          label="Пароль"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          fullWidth
        />
        <Button
          type="submit"
          variant="contained"
          size="large"
          disabled={loading}
          sx={{ mt: 1, py: 1.2 }}
        >
          {loading ? 'Входим...' : 'Войти'}
        </Button>
      </form>

      <Typography variant="body2" sx={{ mt: 3, textAlign: 'center' }}>
        Нет аккаунта?{' '}
        <Link component={RouterLink} to="/register" underline="hover">
          Зарегистрироваться
        </Link>
      </Typography>
    </AuthCard>
  );
}
