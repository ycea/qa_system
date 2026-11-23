import { Alert, Box, Button, MenuItem, Paper, TextField, Typography } from '@mui/material';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { fetchMetrics, setMetricValue, type Metric } from '../api/metrics';
import { extractErrorMessage } from '../utils/errors';

type Props = {
  projectId: number;
  bugId: number;
};

export default function BugMetricsSection({ projectId, bugId }: Props) {
  const queryClient = useQueryClient();
  const [drafts, setDrafts] = useState<Record<number, string>>({});
  const [error, setError] = useState<string | null>(null);

  const { data: metrics } = useQuery({
    queryKey: ['metrics', String(projectId)],
    queryFn: () => fetchMetrics(projectId),
    enabled: !!projectId,
  });

  const saveMutation = useMutation({
    mutationFn: (vars: { metric: Metric; value: string }) =>
      vars.metric.type === 'numeric'
        ? setMetricValue(projectId, bugId, vars.metric.id, { numeric_value: Number(vars.value) })
        : setMetricValue(projectId, bugId, vars.metric.id, { categorical_value: vars.value }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bug'] });
      setError(null);
    },
    onError: (err) => setError(extractErrorMessage(err, 'Не удалось сохранить метрику')),
  });

  if (!metrics || metrics.length === 0) {
    return null;
  }

  const save = (metric: Metric) => {
    const value = drafts[metric.id] ?? '';
    saveMutation.mutate({ metric, value });
  };

  return (
    <Paper sx={{ p: 2, mb: 3 }}>
      <Typography variant="h6" gutterBottom>
        Метрики
      </Typography>

      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
        {metrics.map((metric) => (
          <Box key={metric.id} sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
            {metric.type === 'numeric' ? (
              <TextField
                size="small"
                type="number"
                label={metric.name}
                value={drafts[metric.id] ?? ''}
                onChange={(e) => setDrafts((prev) => ({ ...prev, [metric.id]: e.target.value }))}
              />
            ) : (
              <TextField
                select
                size="small"
                label={metric.name}
                sx={{ minWidth: 200 }}
                value={drafts[metric.id] ?? ''}
                onChange={(e) => setDrafts((prev) => ({ ...prev, [metric.id]: e.target.value }))}
              >
                {(metric.options ?? []).map((option) => (
                  <MenuItem key={option} value={option}>
                    {option}
                  </MenuItem>
                ))}
              </TextField>
            )}
            <Button
              onClick={() => save(metric)}
              disabled={(drafts[metric.id] ?? '') === '' || saveMutation.isPending}
            >
              Сохранить
            </Button>
          </Box>
        ))}
      </Box>
    </Paper>
  );
}
