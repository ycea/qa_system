import {
  Box,
  CircularProgress,
  MenuItem,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';
import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { fetchProjects } from '../api/projects';
import { fetchReport, type ReportPeriod } from '../api/reports';

const periodLabels: Record<ReportPeriod, string> = {
  day: 'День',
  week: 'Неделя',
  month: 'Месяц',
};

export default function ReportsPage() {
  const [projectId, setProjectId] = useState<number | ''>('');
  const [period, setPeriod] = useState<ReportPeriod>('week');

  const { data: projectsResult } = useQuery({
    queryKey: ['projects', 'select'],
    queryFn: () => fetchProjects({ page: 1, per_page: 100 }),
  });

  const projects = projectsResult?.data ?? [];

  const { data: report, isLoading } = useQuery({
    queryKey: ['report', projectId, period],
    queryFn: () => fetchReport(Number(projectId), period),
    enabled: projectId !== '',
  });

  return (
    <Box>
      <Typography variant="h4" sx={{ mb: 3 }}>
        Отчёты
      </Typography>

      <Box sx={{ display: 'flex', gap: 2, mb: 3, flexWrap: 'wrap' }}>
        <TextField
          select
          label="Проект"
          value={projectId}
          onChange={(e) => setProjectId(e.target.value === '' ? '' : Number(e.target.value))}
          sx={{ minWidth: 220 }}
        >
          <MenuItem value="">— выберите проект —</MenuItem>
          {projects.map((project) => (
            <MenuItem key={project.id} value={project.id}>
              {project.name}
            </MenuItem>
          ))}
        </TextField>

        <TextField
          select
          label="Период"
          value={period}
          onChange={(e) => setPeriod(e.target.value as ReportPeriod)}
          sx={{ minWidth: 160 }}
        >
          {(Object.keys(periodLabels) as ReportPeriod[]).map((key) => (
            <MenuItem key={key} value={key}>
              {periodLabels[key]}
            </MenuItem>
          ))}
        </TextField>
      </Box>

      {projectId === '' && (
        <Typography color="text.secondary">Выберите проект для построения отчёта.</Typography>
      )}

      {isLoading && <CircularProgress />}

      {report && (
        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Всего багов</TableCell>
                <TableCell>Закрыто</TableCell>
                <TableCell>Процент закрытых</TableCell>
                <TableCell>Среднее время жизни (ч)</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              <TableRow>
                <TableCell>{report.total}</TableCell>
                <TableCell>{report.closed}</TableCell>
                <TableCell>{report.closed_percent}%</TableCell>
                <TableCell>{report.avg_lifetime_hours}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </TableContainer>
      )}
    </Box>
  );
}
