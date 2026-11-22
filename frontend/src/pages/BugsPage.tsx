import { Add } from '@mui/icons-material';
import { Alert, Box, Button, CircularProgress, Typography } from '@mui/material';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
  assignBug,
  changeBugStatus,
  createBug,
  fetchBugs,
  type BugFilters,
  type BugPayload,
} from '../api/bugs';
import { fetchProjectMeta } from '../api/meta';
import BugFiltersBar from '../components/BugFiltersBar';
import BugFormDialog, { type BugFormValues } from '../components/BugFormDialog';
import BugsTable from '../components/BugsTable';
import { extractErrorMessage } from '../utils/errors';

const emptyFilters: BugFilters = {
  search: '',
  status_id: '',
  priority_id: '',
  severity_id: '',
  assignee_id: '',
};

const toPayload = (values: BugFormValues): BugPayload => ({
  title: values.title,
  description: values.description,
  steps_to_reproduce: values.steps_to_reproduce,
  priority_id: values.priority_id,
  severity_id: values.severity_id,
  status_id: values.status_id,
  assignee_id: values.assignee_id,
});

export default function BugsPage() {
  const { id } = useParams<{ id: string }>();
  const projectId = Number(id);
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [filters, setFilters] = useState<BugFilters>(emptyFilters);
  const [createOpen, setCreateOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const { data: meta } = useQuery({
    queryKey: ['project-meta', id],
    queryFn: () => fetchProjectMeta(projectId),
    enabled: !!projectId,
  });

  const {
    data: bugs,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['bugs', id, filters],
    queryFn: () => fetchBugs(projectId, filters),
    enabled: !!projectId,
  });

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['bugs', id] });
    queryClient.invalidateQueries({ queryKey: ['projects'] });
  };

  const createMutation = useMutation({
    mutationFn: (values: BugFormValues) => createBug(projectId, toPayload(values)),
    onSuccess: () => {
      invalidate();
      setCreateOpen(false);
      setError(null);
    },
    onError: (err) => setError(extractErrorMessage(err, 'Не удалось создать баг')),
  });

  const statusMutation = useMutation({
    mutationFn: (vars: { bugId: number; statusId: number }) =>
      changeBugStatus(projectId, vars.bugId, vars.statusId),
    onSuccess: invalidate,
  });

  const assignMutation = useMutation({
    mutationFn: (vars: { bugId: number; assigneeId: number | null }) =>
      assignBug(projectId, vars.bugId, vars.assigneeId),
    onSuccess: invalidate,
  });

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', mt: 8 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (isError) {
    return <Alert severity="error">Не удалось загрузить баги.</Alert>;
  }

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h4">Баги</Typography>
        <Button variant="contained" startIcon={<Add />} onClick={() => setCreateOpen(true)}>
          Создать баг
        </Button>
      </Box>

      <BugFiltersBar
        filters={filters}
        statuses={meta?.statuses ?? []}
        priorities={meta?.priorities ?? []}
        severities={meta?.severities ?? []}
        members={meta?.members ?? []}
        onChange={(patch) => setFilters((prev) => ({ ...prev, ...patch }))}
        onReset={() => setFilters(emptyFilters)}
      />

      <BugsTable
        bugs={bugs ?? []}
        statuses={meta?.statuses ?? []}
        members={meta?.members ?? []}
        onStatusChange={(bugId, statusId) => statusMutation.mutate({ bugId, statusId })}
        onAssigneeChange={(bugId, assigneeId) => assignMutation.mutate({ bugId, assigneeId })}
        onOpen={(bug) => navigate(`/projects/${projectId}/bugs/${bug.id}`)}
      />

      {createOpen && (
        <BugFormDialog
          open
          mode="create"
          statuses={meta?.statuses ?? []}
          priorities={meta?.priorities ?? []}
          severities={meta?.severities ?? []}
          members={meta?.members ?? []}
          submitting={createMutation.isPending}
          error={error}
          onSubmit={(values) => createMutation.mutate(values)}
          onClose={() => {
            setCreateOpen(false);
            setError(null);
          }}
        />
      )}
    </Box>
  );
}
