import { Add, Delete, Edit } from '@mui/icons-material';
import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  CircularProgress,
  IconButton,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Tooltip,
  Typography,
} from '@mui/material';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Bar, BarChart, CartesianGrid, Tooltip as ChartTooltip, XAxis, YAxis } from 'recharts';
import {
  createMetric,
  deleteMetric,
  fetchAnalytics,
  fetchMetrics,
  metricExportUrl,
  updateMetric,
  type CategoricalIndicator,
  type Metric,
  type MetricAnalytics,
} from '../api/metrics';
import { fetchProject } from '../api/projects';
import MetricFormDialog, { type MetricFormValues } from '../components/MetricFormDialog';
import { useAuth } from '../context/use-auth';
import { extractErrorMessage } from '../utils/errors';

const toPayload = (values: MetricFormValues) => ({
  name: values.name,
  type: values.type,
  options:
    values.type === 'categorical'
      ? values.options
          .split(',')
          .map((option) => option.trim())
          .filter(Boolean)
      : undefined,
});

const indicatorsText = (item: MetricAnalytics): string => {
  if (item.metric.type === 'numeric') {
    const indicators = item.indicators as Record<string, number>;
    return `Среднее: ${indicators.avg} · Мин: ${indicators.min} · Макс: ${indicators.max} · Сумма: ${indicators.sum}`;
  }

  const distribution = item.indicators as CategoricalIndicator[];
  return distribution.length
    ? distribution.map((entry) => `${entry.value}: ${entry.count}`).join(' · ')
    : 'Нет значений';
};

export default function MetricsPage() {
  const { id } = useParams<{ id: string }>();
  const projectId = Number(id);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { user } = useAuth();

  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Metric | null>(null);
  const [error, setError] = useState<string | null>(null);

  const { data: project } = useQuery({
    queryKey: ['project', id],
    queryFn: () => fetchProject(projectId),
    enabled: !!projectId,
  });

  const {
    data: metrics,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['metrics', id],
    queryFn: () => fetchMetrics(projectId),
    enabled: !!projectId,
  });

  const { data: analytics } = useQuery({
    queryKey: ['metrics-analytics', id],
    queryFn: () => fetchAnalytics(projectId),
    enabled: !!projectId,
  });

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['metrics', id] });
    queryClient.invalidateQueries({ queryKey: ['metrics-analytics', id] });
  };

  const saveMutation = useMutation({
    mutationFn: (values: MetricFormValues) =>
      editing
        ? updateMetric(projectId, editing.id, toPayload(values))
        : createMetric(projectId, toPayload(values)),
    onSuccess: () => {
      invalidate();
      setDialogOpen(false);
      setEditing(null);
      setError(null);
    },
    onError: (err) => setError(extractErrorMessage(err, 'Не удалось сохранить метрику')),
  });

  const deleteMutation = useMutation({
    mutationFn: (metricId: number) => deleteMetric(projectId, metricId),
    onSuccess: invalidate,
    onError: (err) => setError(extractErrorMessage(err, 'Не удалось удалить метрику')),
  });

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', mt: 8 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (isError) {
    return <Alert severity="error">Не удалось загрузить метрики.</Alert>;
  }

  const isOwner = project?.owner_id === user?.id;
  const myMembership = project?.members?.find((member) => member.id === user?.id);
  const isAdmin = isOwner || myMembership?.pivot.role === 'admin';

  const chartData = (analytics ?? []).map((item) => ({
    name: item.metric.name,
    count: item.count,
  }));

  const openCreate = () => {
    setEditing(null);
    setError(null);
    setDialogOpen(true);
  };

  const openEdit = (metric: Metric) => {
    setEditing(metric);
    setError(null);
    setDialogOpen(true);
  };

  return (
    <Box>
      <Button onClick={() => navigate(`/projects/${projectId}`)} sx={{ mb: 2 }}>
        ← Назад к проекту
      </Button>

      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h4">Метрики</Typography>
        <Box sx={{ display: 'flex', gap: 1 }}>
          <Button component="a" href={metricExportUrl(projectId)} download>
            Экспорт в Excel
          </Button>
          {isAdmin && (
            <Button variant="contained" startIcon={<Add />} onClick={openCreate}>
              Создать метрику
            </Button>
          )}
        </Box>
      </Box>

      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      {metrics && metrics.length === 0 ? (
        <Typography color="text.secondary">Метрик пока нет.</Typography>
      ) : (
        <TableContainer component={Paper} sx={{ mb: 3 }}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Название</TableCell>
                <TableCell>Тип</TableCell>
                <TableCell>Варианты</TableCell>
                {isAdmin && <TableCell align="right">Действия</TableCell>}
              </TableRow>
            </TableHead>
            <TableBody>
              {metrics?.map((metric) => (
                <TableRow key={metric.id}>
                  <TableCell>{metric.name}</TableCell>
                  <TableCell>{metric.type === 'numeric' ? 'Числовая' : 'Категориальная'}</TableCell>
                  <TableCell>{(metric.options ?? []).join(', ') || '—'}</TableCell>
                  {isAdmin && (
                    <TableCell align="right">
                      <Tooltip title="Редактировать">
                        <IconButton onClick={() => openEdit(metric)}>
                          <Edit />
                        </IconButton>
                      </Tooltip>
                      <Tooltip title="Удалить">
                        <IconButton
                          color="error"
                          onClick={() => {
                            if (confirm(`Удалить метрику «${metric.name}»?`)) {
                              deleteMutation.mutate(metric.id);
                            }
                          }}
                        >
                          <Delete />
                        </IconButton>
                      </Tooltip>
                    </TableCell>
                  )}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      )}

      <Typography variant="h5" gutterBottom>
        Аналитика
      </Typography>

      {analytics && analytics.length > 0 && (
        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))',
            gap: 2,
            mb: 3,
          }}
        >
          {analytics.map((item) => (
            <Card key={item.metric.id}>
              <CardContent>
                <Typography variant="h6">{item.metric.name}</Typography>
                <Typography color="text.secondary" variant="body2">
                  Всего значений: {item.count}
                </Typography>
                <Typography variant="body2">{indicatorsText(item)}</Typography>
              </CardContent>
            </Card>
          ))}
        </Box>
      )}

      {chartData.length > 0 && (
        <Paper sx={{ p: 2 }}>
          <BarChart width={520} height={260} data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis allowDecimals={false} />
            <ChartTooltip />
            <Bar dataKey="count" fill="#1976d2" />
          </BarChart>
        </Paper>
      )}

      {dialogOpen && (
        <MetricFormDialog
          open
          metric={editing}
          submitting={saveMutation.isPending}
          error={error}
          onSubmit={(values) => saveMutation.mutate(values)}
          onClose={() => {
            setDialogOpen(false);
            setEditing(null);
            setError(null);
          }}
        />
      )}
    </Box>
  );
}
