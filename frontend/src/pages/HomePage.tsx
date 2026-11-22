import { Add } from '@mui/icons-material';
import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  MenuItem,
  Pagination,
  TextField,
  Typography,
} from '@mui/material';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { createProject, fetchProjects } from '../api/projects';
import { PER_PAGE_OPTIONS } from '../api/types';

export default function HomePage() {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState<number>(30);

  const {
    data: projectsResult,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['projects', page, perPage],
    queryFn: () => fetchProjects({ page, per_page: perPage }),
  });

  const projects = projectsResult?.data ?? [];

  const mutation = useMutation({
    mutationFn: createProject,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      setOpen(false);
      setName('');
      setDescription('');
      setError(null);
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onError: (err: any) => {
      setError(err?.response?.data?.message || 'Ошибка при создании проекта');
    },
  });

  const handleCreate = () => {
    if (!name.trim()) {
      setError('Название обязательно');
      return;
    }
    mutation.mutate({ name, description });
  };

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 4 }}>
        <Typography variant="h4" color="primary" component="h1">
          Мои проекты
        </Typography>
        <Button variant="contained" startIcon={<Add />} onClick={() => setOpen(true)}>
          Создать проект
        </Button>
      </Box>

      {isLoading && (
        <Box sx={{ display: 'flex', justifyContent: 'center', mt: 8 }}>
          <CircularProgress />
        </Box>
      )}

      {isError && (
        <Alert severity="error" sx={{ mb: 2 }}>
          Не удалось загрузить проекты
        </Alert>
      )}

      {!isLoading && projects.length === 0 && (
        <Typography color="text.secondary">У вас пока нет проектов. Создайте первый!</Typography>
      )}

      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
          gap: 3,
        }}
      >
        {projects.map((project) => (
          <Card
            key={project.id}
            sx={{
              cursor: 'pointer',
              transition: '0.2s',
              '&:hover': { boxShadow: 6, transform: 'translateY(-2px)' },
            }}
            onClick={() => navigate(`/projects/${project.id}`)}
          >
            <CardContent>
              <Typography variant="h6" gutterBottom noWrap>
                {project.name}
              </Typography>
              <Typography
                variant="body2"
                color="text.secondary"
                sx={{ mb: 2, height: 40, overflow: 'hidden' }}
              >
                {project.description || 'Без описания'}
              </Typography>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <Typography variant="body2" color="text.secondary">
                  Открытых багов:
                </Typography>
                <Typography variant="h6" color="primary">
                  {project.bugs_count || 0}
                </Typography>
              </Box>
            </CardContent>
          </Card>
        ))}
      </Box>

      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mt: 3 }}>
        <TextField
          select
          size="small"
          label="На странице"
          value={perPage}
          onChange={(e) => {
            setPerPage(Number(e.target.value));
            setPage(1);
          }}
          sx={{ minWidth: 150 }}
        >
          {PER_PAGE_OPTIONS.map((option) => (
            <MenuItem key={option} value={option}>
              {option}
            </MenuItem>
          ))}
        </TextField>
        <Pagination
          count={projectsResult?.last_page ?? 1}
          page={page}
          onChange={(_, value) => setPage(value)}
        />
      </Box>

      <Dialog open={open} onClose={() => setOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle color="primary">Создать новый проект</DialogTitle>
        <DialogContent>
          {error && (
            <Alert severity="error" sx={{ mb: 2 }}>
              {error}
            </Alert>
          )}
          <TextField
            autoFocus
            margin="dense"
            name="name"
            label="Название проекта"
            fullWidth
            value={name}
            onChange={(e) => setName(e.target.value)}
          />
          <TextField
            margin="dense"
            label="Описание (опционально)"
            fullWidth
            multiline
            rows={3}
            name="description"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            slotProps={{ htmlInput: { maxLength: 500 } }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>Отмена</Button>
          <Button
            name="create_project"
            onClick={handleCreate}
            variant="contained"
            disabled={mutation.isPending}
          >
            {mutation.isPending ? 'Создаем...' : 'Создать'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
