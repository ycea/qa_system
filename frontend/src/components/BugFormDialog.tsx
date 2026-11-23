import {
  Alert,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  MenuItem,
  TextField,
} from '@mui/material';
import { useState } from 'react';
import type { Bug, BugDictionaryRef, BugStatus } from '../api/bugs';

export type BugFormValues = {
  title: string;
  description: string;
  steps_to_reproduce: string;
  priority_id: number | '';
  severity_id: number | '';
  status_id: number | '';
  assignee_id: number | '';
};

const emptyValues: BugFormValues = {
  title: '',
  description: '',
  steps_to_reproduce: '',
  priority_id: '',
  severity_id: '',
  status_id: '',
  assignee_id: '',
};

const initialValues = (
  mode: 'create' | 'edit',
  bug: Bug | null | undefined,
  statuses: BugStatus[],
): BugFormValues => {
  if (mode === 'edit' && bug) {
    return {
      title: bug.title,
      description: bug.description ?? '',
      steps_to_reproduce: bug.steps_to_reproduce ?? '',
      priority_id: bug.priority_id,
      severity_id: bug.severity_id,
      status_id: bug.status_id,
      assignee_id: bug.assignee_id ?? '',
    };
  }

  return {
    ...emptyValues,
    status_id: statuses.find((status) => status.is_default)?.id ?? '',
  };
};

type Member = { id: number; name: string };

type Props = {
  open: boolean;
  mode: 'create' | 'edit';
  bug?: Bug | null;
  statuses: BugStatus[];
  priorities: BugDictionaryRef[];
  severities: BugDictionaryRef[];
  members: Member[];
  submitting?: boolean;
  error?: string | null;
  onSubmit: (values: BugFormValues) => void;
  onClose: () => void;
};

export default function BugFormDialog({
  open,
  mode,
  bug,
  statuses,
  priorities,
  severities,
  members,
  submitting,
  error,
  onSubmit,
  onClose,
}: Props) {
  const [values, setValues] = useState<BugFormValues>(() => initialValues(mode, bug, statuses));

  const set = (patch: Partial<BugFormValues>) => setValues((prev) => ({ ...prev, ...patch }));

  const asNumber = (value: string): number | '' => (value === '' ? '' : Number(value));

  const canSubmit =
    values.title.trim() !== '' &&
    values.description.trim() !== '' &&
    values.steps_to_reproduce.trim() !== '' &&
    values.priority_id !== '' &&
    values.severity_id !== '';

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle color="primary">
        {mode === 'create' ? 'Создать баг' : 'Редактировать баг'}
      </DialogTitle>
      <DialogContent>
        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}
        <TextField
          autoFocus
          margin="dense"
          label="Заголовок"
          fullWidth
          value={values.title}
          onChange={(e) => set({ title: e.target.value })}
        />
        <TextField
          margin="dense"
          label="Описание"
          fullWidth
          multiline
          rows={2}
          value={values.description}
          onChange={(e) => set({ description: e.target.value })}
        />
        <TextField
          margin="dense"
          label="Шаги воспроизведения"
          fullWidth
          multiline
          rows={2}
          value={values.steps_to_reproduce}
          onChange={(e) => set({ steps_to_reproduce: e.target.value })}
        />
        <TextField
          select
          margin="dense"
          label="Приоритет"
          fullWidth
          value={values.priority_id}
          onChange={(e) => set({ priority_id: asNumber(e.target.value) })}
        >
          {priorities.map((item) => (
            <MenuItem key={item.id} value={item.id}>
              {item.name}
            </MenuItem>
          ))}
        </TextField>
        <TextField
          select
          margin="dense"
          label="Серьёзность"
          fullWidth
          value={values.severity_id}
          onChange={(e) => set({ severity_id: asNumber(e.target.value) })}
        >
          {severities.map((item) => (
            <MenuItem key={item.id} value={item.id}>
              {item.name}
            </MenuItem>
          ))}
        </TextField>
        {mode === 'create' && (
          <TextField
            select
            margin="dense"
            label="Статус"
            fullWidth
            value={values.status_id}
            onChange={(e) => set({ status_id: asNumber(e.target.value) })}
          >
            {statuses.map((status) => (
              <MenuItem key={status.id} value={status.id}>
                {status.name}
              </MenuItem>
            ))}
          </TextField>
        )}
        <TextField
          select
          margin="dense"
          label="Исполнитель"
          fullWidth
          value={values.assignee_id}
          onChange={(e) => set({ assignee_id: asNumber(e.target.value) })}
        >
          <MenuItem value="">— не назначен —</MenuItem>
          {members.map((member) => (
            <MenuItem key={member.id} value={member.id}>
              {member.name}
            </MenuItem>
          ))}
        </TextField>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>Отмена</Button>
        <Button
          variant="contained"
          disabled={!canSubmit || submitting}
          onClick={() => onSubmit(values)}
        >
          Сохранить
        </Button>
      </DialogActions>
    </Dialog>
  );
}
