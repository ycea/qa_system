import { Box, Button, MenuItem, TextField } from '@mui/material';
import type { BugDictionaryRef, BugFilters, BugStatus } from '../api/bugs';

type Member = { id: number; name: string };

type Props = {
  filters: BugFilters;
  statuses: BugStatus[];
  priorities: BugDictionaryRef[];
  severities: BugDictionaryRef[];
  members: Member[];
  onChange: (patch: Partial<BugFilters>) => void;
  onReset: () => void;
};

export default function BugFiltersBar({
  filters,
  statuses,
  priorities,
  severities,
  members,
  onChange,
  onReset,
}: Props) {
  const toNumber = (value: string): number | '' => (value === '' ? '' : Number(value));

  return (
    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 2, alignItems: 'center', mb: 2 }}>
      <TextField
        size="small"
        label="Поиск по заголовку"
        value={filters.search ?? ''}
        onChange={(e) => onChange({ search: e.target.value })}
      />
      <TextField
        select
        size="small"
        label="Статус"
        value={filters.status_id ?? ''}
        onChange={(e) => onChange({ status_id: toNumber(e.target.value) })}
        sx={{ minWidth: 160 }}
      >
        <MenuItem value="">Все</MenuItem>
        {statuses.map((status) => (
          <MenuItem key={status.id} value={status.id}>
            {status.name}
          </MenuItem>
        ))}
      </TextField>
      <TextField
        select
        size="small"
        label="Приоритет"
        value={filters.priority_id ?? ''}
        onChange={(e) => onChange({ priority_id: toNumber(e.target.value) })}
        sx={{ minWidth: 160 }}
      >
        <MenuItem value="">Все</MenuItem>
        {priorities.map((item) => (
          <MenuItem key={item.id} value={item.id}>
            {item.name}
          </MenuItem>
        ))}
      </TextField>
      <TextField
        select
        size="small"
        label="Серьёзность"
        value={filters.severity_id ?? ''}
        onChange={(e) => onChange({ severity_id: toNumber(e.target.value) })}
        sx={{ minWidth: 160 }}
      >
        <MenuItem value="">Все</MenuItem>
        {severities.map((item) => (
          <MenuItem key={item.id} value={item.id}>
            {item.name}
          </MenuItem>
        ))}
      </TextField>
      <TextField
        select
        size="small"
        label="Исполнитель"
        value={filters.assignee_id ?? ''}
        onChange={(e) => onChange({ assignee_id: toNumber(e.target.value) })}
        sx={{ minWidth: 160 }}
      >
        <MenuItem value="">Все</MenuItem>
        {members.map((member) => (
          <MenuItem key={member.id} value={member.id}>
            {member.name}
          </MenuItem>
        ))}
      </TextField>
      <Button onClick={onReset}>Сбросить всё</Button>
    </Box>
  );
}
