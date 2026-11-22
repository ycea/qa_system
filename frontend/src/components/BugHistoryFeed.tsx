import { Box, List, ListItem, ListItemText, Typography } from '@mui/material';
import type { BugHistoryItem } from '../api/bugs';

const fieldLabels: Record<string, string> = {
  title: 'Заголовок',
  description: 'Описание',
  steps_to_reproduce: 'Шаги воспроизведения',
  priority_id: 'Приоритет',
  severity_id: 'Серьёзность',
  status: 'Статус',
  assignee: 'Исполнитель',
};

const historyText = (item: BugHistoryItem): string => {
  const label = item.field ? (fieldLabels[item.field] ?? item.field) : '';

  switch (item.action) {
    case 'created':
      return 'Баг создан';
    case 'status_changed':
      return `Статус: ${item.old_value ?? '—'} → ${item.new_value ?? '—'}`;
    case 'assignee_assigned':
      return `Исполнитель: ${item.old_value ?? 'не назначен'} → ${item.new_value ?? 'не назначен'}`;
    case 'updated':
      return `${label}: ${item.old_value ?? '—'} → ${item.new_value ?? '—'}`;
    default:
      return item.action;
  }
};

export default function BugHistoryFeed({ items }: { items: BugHistoryItem[] }) {
  if (items.length === 0) {
    return <Typography color="text.secondary">История изменений пуста.</Typography>;
  }

  return (
    <Box>
      <Typography variant="h6" gutterBottom>
        История изменений
      </Typography>
      <List dense>
        {items.map((item) => (
          <ListItem key={item.id} disableGutters>
            <ListItemText
              primary={historyText(item)}
              secondary={`${item.user?.name ?? '—'} · ${new Date(item.created_at).toLocaleString()}`}
            />
          </ListItem>
        ))}
      </List>
    </Box>
  );
}
