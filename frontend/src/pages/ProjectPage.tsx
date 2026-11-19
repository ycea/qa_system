import { Delete } from '@mui/icons-material';
import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogContentText,
  DialogTitle,
  IconButton,
  Tab,
  Tabs,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { deleteProject, fetchProject } from '../api/projects';
import DictionariesTab from '../components/DictionariesTab';
import TeamTab from '../components/TeamTab';
import { useAuth } from '../context/use-auth';
export default function ProjectPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { user } = useAuth();
  const projectId = Number(id);
  const [tab, setTab] = useState(0);

  // Состояние для диалога удаления
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [confirmText, setConfirmText] = useState('');
  const [deleteError, setDeleteError] = useState<string | null>(null);
  const {
    data: project,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['project', id],
    queryFn: () => fetchProject(projectId),
    enabled: !!projectId,
  });

  const deleteMutation = useMutation({
    mutationFn: () => deleteProject(projectId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      navigate('/');
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onError: (err: any) => {
      setDeleteError(err?.response?.data?.message || 'Не удалось удалить проект');
    },
  });

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', mt: 8 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (isError || !project) {
    return <Typography>Проект не найден или доступ запрещён.</Typography>;
  }

  const isOwner = user?.id === project.owner_id;
  const myMembership = project.members?.find((m) => m.id === user?.id);
  const isAdmin = isOwner || myMembership?.pivot.role === 'admin';
  return (
    <Box>
      <Button onClick={() => navigate('/')} sx={{ mb: 2 }}>
        ← Назад к проектам
      </Button>

      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
        <Typography variant="h4">{project.name}</Typography>
        {isOwner && (
          <Tooltip title="Удалить проект">
            <IconButton color="error" onClick={() => setDeleteOpen(true)}>
              <Delete />
            </IconButton>
          </Tooltip>
        )}
      </Box>

      <Typography color="text.secondary" sx={{ mb: 3 }}>
        {project.description || 'Без описания'}
      </Typography>

      <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ mb: 3 }}>
        <Tab label="Обзор" />
        <Tab label="Команда" />
        {isAdmin && <Tab label="Справочники" />}
      </Tabs>

      {tab === 0 && (
        <Box>
          <Typography>
            Багов: <strong>{project.bugs_count}</strong>
          </Typography>
        </Box>
      )}

      {tab === 1 && <TeamTab projectId={projectId} />}
      {tab === 2 && isAdmin && <DictionariesTab projectId={projectId} />}
      {/* Диалог удаления проекта */}
      <Dialog open={deleteOpen} onClose={() => setDeleteOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle color="primary">Удалить проект?</DialogTitle>
        <DialogContent>
          <DialogContentText sx={{ mb: 2 }}>
            Это действие необратимо. Все баги, комментарии, метрики и участники будут удалены.
            Введите название проекта <strong>{project.name}</strong>, чтобы подтвердить.
          </DialogContentText>
          {deleteError && (
            <Alert severity="error" sx={{ mb: 2 }}>
              {deleteError}
            </Alert>
          )}
          <TextField
            autoFocus
            fullWidth
            label={`Введите "${project.name}"`}
            value={confirmText}
            onChange={(e) => setConfirmText(e.target.value)}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDeleteOpen(false)}>Отмена</Button>
          <Button
            color="error"
            variant="contained"
            disabled={confirmText !== project.name || deleteMutation.isPending}
            onClick={() => deleteMutation.mutate()}
          >
            {deleteMutation.isPending ? 'Удаляем...' : 'Удалить проект'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
