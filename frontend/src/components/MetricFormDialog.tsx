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
import type { Metric, MetricType } from '../api/metrics';

export type MetricFormValues = {
  name: string;
  type: MetricType;
  options: string;
};

const parseOptions = (raw: string): string[] =>
  raw
    .split(',')
    .map((option) => option.trim())
    .filter(Boolean);

const initialValues = (metric?: Metric | null): MetricFormValues =>
  metric
    ? { name: metric.name, type: metric.type, options: (metric.options ?? []).join(', ') }
    : { name: '', type: 'numeric', options: '' };

type Props = {
  open: boolean;
  metric?: Metric | null;
  submitting?: boolean;
  error?: string | null;
  onSubmit: (values: MetricFormValues) => void;
  onClose: () => void;
};

export default function MetricFormDialog({
  open,
  metric,
  submitting,
  error,
  onSubmit,
  onClose,
}: Props) {
  const [values, setValues] = useState<MetricFormValues>(() => initialValues(metric));

  const canSubmit =
    values.name.trim() !== '' &&
    (values.type === 'numeric' || parseOptions(values.options).length > 0);

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle color="primary">
        {metric ? 'Редактировать метрику' : 'Создать метрику'}
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
          label="Название метрики"
          fullWidth
          value={values.name}
          onChange={(e) => setValues((prev) => ({ ...prev, name: e.target.value }))}
        />
        <TextField
          select
          margin="dense"
          label="Тип метрики"
          fullWidth
          value={values.type}
          onChange={(e) => setValues((prev) => ({ ...prev, type: e.target.value as MetricType }))}
        >
          <MenuItem value="numeric">Числовая</MenuItem>
          <MenuItem value="categorical">Категориальная</MenuItem>
        </TextField>
        {values.type === 'categorical' && (
          <TextField
            margin="dense"
            label="Варианты (через запятую)"
            fullWidth
            value={values.options}
            onChange={(e) => setValues((prev) => ({ ...prev, options: e.target.value }))}
          />
        )}
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
