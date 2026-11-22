import { Box, Typography } from '@mui/material';
import { Bar, BarChart, CartesianGrid, Tooltip, XAxis, YAxis } from 'recharts';
import type { CategoricalIndicator, MetricAnalytics } from '../api/metrics';

type ChartPoint = { name: string; value: number; count: number };

/**
 * Точки графика: для числовой метрики — среднее по статусам, для категориальной — распределение значений.
 */
const buildData = (item: MetricAnalytics): ChartPoint[] => {
  if (item.metric.type === 'numeric') {
    return item.by_status.map((status) => ({
      name: status.status,
      value: status.avg ?? 0,
      count: status.count,
    }));
  }

  return (item.indicators as CategoricalIndicator[]).map((entry) => ({
    name: entry.value,
    value: entry.count,
    count: entry.count,
  }));
};

type TooltipPayload = { payload?: ChartPoint };

function ChartTooltip({ active, payload }: { active?: boolean; payload?: TooltipPayload[] }) {
  const point = active ? payload?.[0]?.payload : undefined;
  if (!point) {
    return null;
  }

  return (
    <Box
      sx={{ bgcolor: 'background.paper', border: 1, borderColor: 'divider', p: 1, borderRadius: 1 }}
    >
      <Typography variant="caption">{point.name}</Typography>
      <Typography variant="body2">Значение: {point.value}</Typography>
      <Typography variant="body2">Количество: {point.count}</Typography>
    </Box>
  );
}

export default function MetricChart({ item }: { item: MetricAnalytics }) {
  const data = buildData(item);

  if (data.length === 0) {
    return (
      <Typography color="text.secondary" variant="body2">
        Нет данных для графика.
      </Typography>
    );
  }

  return (
    <BarChart width={260} height={180} data={data}>
      <CartesianGrid strokeDasharray="3 3" />
      <XAxis dataKey="name" />
      <YAxis allowDecimals={item.metric.type === 'numeric'} />
      <Tooltip content={<ChartTooltip />} />
      <Bar dataKey="value" fill="#1976d2" />
    </BarChart>
  );
}
