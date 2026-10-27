import { Box, Button, CircularProgress, Typography } from '@mui/material';
import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { fetchProject } from '../api/projects';

export default function ProjectPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const {
    data: project,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['project', id],
    queryFn: () => fetchProject(Number(id)),
    enabled: !!id,
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

  return (
    <Box>
      <Button onClick={() => navigate('/')} sx={{ mb: 2 }}>
        ← Назад к проектам
      </Button>
      <Typography variant="h4" gutterBottom>
        {project.name}
      </Typography>
      <Typography color="text.secondary" sx={{ mb: 3 }}>
        {project.description || 'Без описания'}
      </Typography>
      <Typography>
        Багов: <strong>{project.bugs_count}</strong>
      </Typography>
    </Box>
  );
}
