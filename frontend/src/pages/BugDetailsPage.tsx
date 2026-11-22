import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Divider,
  MenuItem,
  Paper,
  Select,
  Typography,
} from '@mui/material';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { assignBug, changeBugStatus, fetchBug, updateBug, type BugPayload } from '../api/bugs';
import { fetchProjectMeta } from '../api/meta';
import BugFormDialog, { type BugFormValues } from '../components/BugFormDialog';
import BugHistoryFeed from '../components/BugHistoryFeed';
import BugMetricsSection from '../components/BugMetricsSection';
import CommentsSection from '../components/CommentsSection';
import { extractErrorMessage } from '../utils/errors';
import { toNullableId } from '../utils/values';

const toPayload = (values: BugFormValues): BugPayload => ({
  title: values.title,
  description: values.description,
  steps_to_reproduce: values.steps_to_reproduce,
  priority_id: values.priority_id,
  severity_id: values.severity_id,
});

export default function BugDetailsPage() {
  const { id, bugId } = useParams<{ id: string; bugId: string }>();
  const projectId = Number(id);
  const numericBugId = Number(bugId);
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [editOpen, setEditOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const { data: meta } = useQuery({
    queryKey: ['project-meta', id],
    queryFn: () => fetchProjectMeta(projectId),
    enabled: !!projectId,
  });

  const {
    data: bug,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['bug', id, bugId],
    queryFn: () => fetchBug(projectId, numericBugId),
    enabled: !!projectId && !!numericBugId,
  });

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['bug', id, bugId] });
    queryClient.invalidateQueries({ queryKey: ['bugs', id] });
  };

  const updateMutation = useMutation({
    mutationFn: (values: BugFormValues) => updateBug(projectId, numericBugId, toPayload(values)),
    onSuccess: () => {
      refresh();
      setEditOpen(false);
      setError(null);
    },
    onError: (err) => setError(extractErrorMessage(err, 'Не удалось сохранить баг')),
  });

  const statusMutation = useMutation({
    mutationFn: (statusId: number) => changeBugStatus(projectId, numericBugId, statusId),
    onSuccess: refresh,
  });

  const assignMutation = useMutation({
    mutationFn: (assigneeId: number | null) => assignBug(projectId, numericBugId, assigneeId),
    onSuccess: refresh,
  });

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', mt: 8 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (isError || !bug) {
    return <Typography>Баг не найден или доступ запрещён.</Typography>;
  }

  return (
    <Box>
      <Button onClick={() => navigate(`/projects/${projectId}/bugs`)} sx={{ mb: 2 }}>
        ← Назад к списку багов
      </Button>

      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
        <Typography variant="h4">{bug.title}</Typography>
        <Button variant="outlined" onClick={() => setEditOpen(true)}>
          Редактировать
        </Button>
      </Box>

      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      <Paper sx={{ p: 2, mb: 3 }}>
        <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', mb: 2 }}>
          <Select
            size="small"
            value={bug.status_id}
            onChange={(e) => statusMutation.mutate(Number(e.target.value))}
            inputProps={{ 'aria-label': 'Статус' }}
          >
            {(meta?.statuses ?? []).map((status) => (
              <MenuItem key={status.id} value={status.id}>
                {status.name}
              </MenuItem>
            ))}
          </Select>
          <Select
            size="small"
            displayEmpty
            value={bug.assignee_id ?? ''}
            onChange={(e) => assignMutation.mutate(toNullableId(e.target.value))}
            inputProps={{ 'aria-label': 'Исполнитель' }}
          >
            <MenuItem value="">— не назначен —</MenuItem>
            {(meta?.members ?? []).map((member) => (
              <MenuItem key={member.id} value={member.id}>
                {member.name}
              </MenuItem>
            ))}
          </Select>
        </Box>

        <Typography variant="body2" color="text.secondary">
          Приоритет: {bug.priority?.name ?? '—'} · Серьёзность: {bug.severity?.name ?? '—'}
        </Typography>
        <Divider sx={{ my: 2 }} />
        <Typography variant="subtitle2">Описание</Typography>
        <Typography sx={{ mb: 2 }}>{bug.description || '—'}</Typography>
        <Typography variant="subtitle2">Шаги воспроизведения</Typography>
        <Typography>{bug.steps_to_reproduce || '—'}</Typography>
      </Paper>

      <BugHistoryFeed items={bug.histories ?? []} />

      <Divider sx={{ my: 3 }} />

      <BugMetricsSection projectId={projectId} bugId={numericBugId} />

      <Divider sx={{ my: 3 }} />

      <CommentsSection projectId={projectId} bugId={numericBugId} />

      {editOpen && (
        <BugFormDialog
          open
          mode="edit"
          bug={bug}
          statuses={meta?.statuses ?? []}
          priorities={meta?.priorities ?? []}
          severities={meta?.severities ?? []}
          members={meta?.members ?? []}
          submitting={updateMutation.isPending}
          error={error}
          onSubmit={(values) => updateMutation.mutate(values)}
          onClose={() => {
            setEditOpen(false);
            setError(null);
          }}
        />
      )}
    </Box>
  );
}
