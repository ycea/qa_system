import { Alert, Button, Link, TextField, Typography } from '@mui/material';
import type { ChangeEvent, FormEvent } from 'react';
import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import AuthCard from '../components/AuthCard';
import { useAuth } from '../context/use-auth';

type FormState = {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
};

export default function RegisterPage() {
  const { register } = useAuth();
  const navigate = useNavigate();

  const [form, setForm] = useState<FormState>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleChange = (field: keyof FormState) => (e: ChangeEvent<HTMLInputElement>) => {
    setForm((prev) => ({ ...prev, [field]: e.target.value }));
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await register(form);
      navigate('/');
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
    } catch (err: any) {
      const errors = err?.response?.data?.errors as Record<string, string[]> | undefined;
      const firstError = errors ? Object.values(errors)[0]?.[0] : undefined;
      setError(firstError || err?.response?.data?.message || 'Не удалось зарегистрироваться.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthCard title="Регистрация" subtitle="Создайте аккаунт для работы в системе">
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        <TextField
          label="Логин"
          value={form.name}
          onChange={handleChange('name')}
          required
          fullWidth
          autoFocus
        />
        <TextField
          label="Email"
          type="email"
          value={form.email}
          onChange={handleChange('email')}
          required
          fullWidth
        />
        <TextField
          label="Пароль"
          type="password"
          value={form.password}
          onChange={handleChange('password')}
          required
          fullWidth
        />
        <TextField
          label="Подтверждение пароля"
          type="password"
          value={form.password_confirmation}
          onChange={handleChange('password_confirmation')}
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
          {loading ? 'Регистрируем...' : 'Зарегистрироваться'}
        </Button>
      </form>

      <Typography variant="body2" sx={{ mt: 3, textAlign: 'center' }}>
        Уже есть аккаунт?{' '}
        <Link component={RouterLink} to="/login" underline="hover">
          Войти
        </Link>
      </Typography>
    </AuthCard>
  );
}
