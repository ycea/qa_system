import { Add, Delete } from '@mui/icons-material';
import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  MenuItem,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { addMember, fetchMembers, removeMember, type MemberRole } from '../api/members';

const roleLabels: Record<MemberRole, string> = {
  admin: 'Администратор',
  developer: 'Разработчик',
  tester: 'Тестировщик',
};

export default function TeamTab({ projectId }: { projectId: number }) {
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(false);
  const [login, setLogin] = useState('');
  const [role, setRole] = useState<MemberRole>('developer');
  const [error, setError] = useState<string | null>(null);

  const { data, isLoading, isError } = useQuery({
    queryKey: ['members', projectId],
    queryFn: () => fetchMembers(projectId),
  });

  const addMutation = useMutation({
    mutationFn: () => addMember(projectId, { name: login, role }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['members', projectId] });
      setOpen(false);
      setLogin('');
      setRole('developer');
      setError(null);
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onError: (err: any) => {
      setError(
        err?.response?.data?.errors?.name?.[0] ||
          err?.response?.data?.message ||
          'Не удалось добавить участника',
      );
    },
  });

  const removeMutation = useMutation({
    mutationFn: (userId: number) => removeMember(projectId, userId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['members', projectId] });
    },
  });

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (isError || !data) {
    return <Alert severity="error">Не удалось загрузить участников.</Alert>;
  }

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6">Состав команды</Typography>
        {data.can_manage && (
          <Button variant="contained" startIcon={<Add />} onClick={() => setOpen(true)}>
            Добавить участника
          </Button>
        )}
      </Box>

      {data.members.length === 0 ? (
        <Typography color="text.secondary">Пока нет участников.</Typography>
      ) : (
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Логин</TableCell>
              <TableCell>Email</TableCell>
              <TableCell>Роль</TableCell>
              {data.can_manage && <TableCell align="right">Действия</TableCell>}
            </TableRow>
          </TableHead>
          <TableBody>
            {data.members.map((m) => (
              <TableRow key={m.id}>
                <TableCell>{m.name}</TableCell>
                <TableCell>{m.email}</TableCell>
                <TableCell>{roleLabels[m.role]}</TableCell>
                {data.can_manage && (
                  <TableCell align="right">
                    <Tooltip
                      title={m.role === 'admin' ? 'Нельзя удалить администратора' : 'Удалить'}
                    >
                      <span>
                        <IconButton
                          color="error"
                          disabled={m.role === 'admin'}
                          onClick={() => {
                            if (confirm(`Удалить ${m.name} из проекта?`)) {
                              removeMutation.mutate(m.id);
                            }
                          }}
                        >
                          <Delete />
                        </IconButton>
                      </span>
                    </Tooltip>
                  </TableCell>
                )}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      <Dialog open={open} onClose={() => setOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle color="primary">Добавить участника</DialogTitle>
        <DialogContent>
          {error && (
            <Alert severity="error" sx={{ mb: 2 }}>
              {error}
            </Alert>
          )}
          <TextField
            autoFocus
            margin="dense"
            label="Логин пользователя"
            fullWidth
            value={login}
            onChange={(e) => setLogin(e.target.value)}
          />
          <TextField
            select
            margin="dense"
            label="Роль"
            fullWidth
            value={role}
            onChange={(e) => setRole(e.target.value as MemberRole)}
          >
            <MenuItem value="developer">Разработчик</MenuItem>
            <MenuItem value="tester">Тестировщик</MenuItem>
          </TextField>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>Отмена</Button>
          <Button
            variant="contained"
            onClick={() => addMutation.mutate()}
            disabled={addMutation.isPending}
          >
            {addMutation.isPending ? 'Добавляем...' : 'Добавить'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
