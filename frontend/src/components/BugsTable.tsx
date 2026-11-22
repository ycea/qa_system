import {
  Link,
  MenuItem,
  Select,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';
import type { Bug, BugDictionaryRef, BugStatus } from '../api/bugs';
import { toNullableId } from '../utils/values';

type Member = { id: number; name: string };

type Props = {
  bugs: Bug[];
  statuses: BugStatus[];
  members: Member[];
  onStatusChange: (bugId: number, statusId: number) => void;
  onAssigneeChange: (bugId: number, assigneeId: number | null) => void;
  onOpen: (bug: Bug) => void;
};

export default function BugsTable({
  bugs,
  statuses,
  members,
  onStatusChange,
  onAssigneeChange,
  onOpen,
}: Props) {
  if (bugs.length === 0) {
    return <Typography color="text.secondary">Багов не найдено.</Typography>;
  }

  const refName = (ref?: BugDictionaryRef) => ref?.name ?? '—';

  return (
    <TableContainer>
      <Table>
        <TableHead>
          <TableRow>
            <TableCell>Заголовок</TableCell>
            <TableCell>Статус</TableCell>
            <TableCell>Приоритет</TableCell>
            <TableCell>Серьёзность</TableCell>
            <TableCell>Исполнитель</TableCell>
          </TableRow>
        </TableHead>
        <TableBody>
          {bugs.map((bug) => (
            <TableRow key={bug.id}>
              <TableCell>
                <Link
                  component="button"
                  type="button"
                  onClick={() => onOpen(bug)}
                  underline="hover"
                >
                  {bug.title}
                </Link>
              </TableCell>
              <TableCell>
                <Select
                  size="small"
                  value={bug.status_id}
                  onChange={(e) => onStatusChange(bug.id, Number(e.target.value))}
                  inputProps={{ 'aria-label': `Статус: ${bug.title}` }}
                >
                  {statuses.map((status) => (
                    <MenuItem key={status.id} value={status.id}>
                      {status.name}
                    </MenuItem>
                  ))}
                </Select>
              </TableCell>
              <TableCell>{refName(bug.priority)}</TableCell>
              <TableCell>{refName(bug.severity)}</TableCell>
              <TableCell>
                <Select
                  size="small"
                  displayEmpty
                  value={bug.assignee_id ?? ''}
                  onChange={(e) => onAssigneeChange(bug.id, toNullableId(e.target.value))}
                  inputProps={{ 'aria-label': `Исполнитель: ${bug.title}` }}
                >
                  <MenuItem value="">— не назначен —</MenuItem>
                  {members.map((member) => (
                    <MenuItem key={member.id} value={member.id}>
                      {member.name}
                    </MenuItem>
                  ))}
                </Select>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </TableContainer>
  );
}
