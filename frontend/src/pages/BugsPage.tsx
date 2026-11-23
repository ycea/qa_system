import { Add } from '@mui/icons-material';
import {
  Alert,
  Box,
  Button,
  CircularProgress,
  MenuItem,
  Pagination,
  TextField,
  Typography,
} from '@mui/material';
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
import { PER_PAGE_OPTIONS } from '../api/types';
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
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState<number>(30);

  const { data: meta } = useQuery({
    queryKey: ['project-meta', id],
    queryFn: () => fetchProjectMeta(projectId),
    enabled: !!projectId,
  });

  const {
    data: bugsResult,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['bugs', id, filters, page, perPage],
    queryFn: () => fetchBugs(projectId, filters, { page, per_page: perPage }),
    enabled: !!projectId,
  });

  const bugs = bugsResult?.data ?? [];

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
      <Button onClick={() => navigate(`/projects/${projectId}`)} sx={{ mb: 2 }}>
        ← Назад к проекту
      </Button>

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
        onChange={(patch) => {
          setFilters((prev) => ({ ...prev, ...patch }));
          setPage(1);
        }}
        onReset={() => {
          setFilters(emptyFilters);
          setPage(1);
        }}
      />

      <BugsTable
        bugs={bugs}
        statuses={meta?.statuses ?? []}
        members={meta?.members ?? []}
        onStatusChange={(bugId, statusId) => statusMutation.mutate({ bugId, statusId })}
        onAssigneeChange={(bugId, assigneeId) => assignMutation.mutate({ bugId, assigneeId })}
        onOpen={(bug) => navigate(`/projects/${projectId}/bugs/${bug.id}`)}
      />

      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mt: 2 }}>
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
          count={bugsResult?.last_page ?? 1}
          page={page}
          onChange={(_, value) => setPage(value)}
        />
      </Box>

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
