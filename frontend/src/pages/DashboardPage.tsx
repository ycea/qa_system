import { Alert, Box, Button, Card, CardContent, CircularProgress, Typography } from '@mui/material';
import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { fetchDashboard } from '../api/dashboard';

export default function DashboardPage() {
  const { id } = useParams<{ id: string }>();
  const projectId = Number(id);
  const navigate = useNavigate();

  const { data, isLoading, isError } = useQuery({
    queryKey: ['dashboard', id],
    queryFn: () => fetchDashboard(projectId),
    enabled: !!projectId,
  });

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', mt: 8 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (isError || !data) {
    return <Alert severity="error">Не удалось загрузить статистику.</Alert>;
  }

  const widgets = [
    { label: 'Всего багов', value: data.total },
    { label: 'Открытые баги', value: data.open },
    { label: 'Закрытые за неделю', value: data.closed_week },
  ];

  return (
    <Box>
      <Button onClick={() => navigate(`/projects/${projectId}`)} sx={{ mb: 2 }}>
        ← Назад к проекту
      </Button>

      <Typography variant="h4" sx={{ mb: 3 }}>
        Дашборд
      </Typography>
      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))',
          gap: 3,
        }}
      >
        {widgets.map((widget) => (
          <Card key={widget.label}>
            <CardContent>
              <Typography color="text.secondary">{widget.label}</Typography>
              <Typography variant="h4" color="primary">
                {widget.value}
              </Typography>
            </CardContent>
          </Card>
        ))}
      </Box>
    </Box>
  );
}
