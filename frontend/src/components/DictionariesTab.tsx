import { Add, Delete, Edit } from '@mui/icons-material';
import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  Tab,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  Tabs,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import {
  createDictionaryItem,
  deleteDictionaryItem,
  fetchDictionaries,
  updateDictionaryItem,
  type DictionaryItem,
  type DictionaryType,
} from '../api/dictionaries';

const tabLabels: Record<DictionaryType, string> = {
  statuses: 'Статусы',
  priorities: 'Приоритеты',
  severities: 'Серьёзности',
};

export default function DictionariesTab({ projectId }: { projectId: number }) {
  const queryClient = useQueryClient();
  const [type, setType] = useState<DictionaryType>('statuses');

  const {
    data: items,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['dictionaries', projectId, type],
    queryFn: () => fetchDictionaries(projectId, type),
  });

  // Диалог создания/редактирования
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<DictionaryItem | null>(null);
  const [name, setName] = useState('');
  const [error, setError] = useState<string | null>(null);

  const openCreate = () => {
    setEditing(null);
    setName('');
    setError(null);
    setDialogOpen(true);
  };

  const openEdit = (item: DictionaryItem) => {
    setEditing(item);
    setName(item.name);
    setError(null);
    setDialogOpen(true);
  };

  const saveMutation = useMutation({
    mutationFn: () =>
      editing
        ? updateDictionaryItem(projectId, type, editing.id, name)
        : createDictionaryItem(projectId, type, name),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['dictionaries', projectId, type] });
      setDialogOpen(false);
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onError: (err: any) => {
      setError(
        err?.response?.data?.errors?.name?.[0] ||
          err?.response?.data?.message ||
          'Не удалось сохранить',
      );
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteDictionaryItem(projectId, type, id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['dictionaries', projectId, type] });
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onError: (err: any) => {
      alert(err?.response?.data?.message || 'Не удалось удалить');
    },
  });

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6">Справочники проекта</Typography>
        <Button variant="contained" startIcon={<Add />} onClick={openCreate}>
          Добавить в «{tabLabels[type]}»
        </Button>
      </Box>

      <Tabs value={type} onChange={(_, v) => setType(v as DictionaryType)} sx={{ mb: 2 }}>
        <Tab label="Статусы" value="statuses" />
        <Tab label="Приоритеты" value="priorities" />
        <Tab label="Серьёзности" value="severities" />
      </Tabs>

      {isLoading && (
        <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
          <CircularProgress />
        </Box>
      )}

      {isError && <Alert severity="error">Не удалось загрузить справочник.</Alert>}

      {items && (
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Название</TableCell>
              <TableCell align="right">Действия</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {items.map((item) => (
              <TableRow key={item.id}>
                <TableCell>
                  {item.name}
                  {item.is_default && (
                    <Typography
                      component="span"
                      variant="caption"
                      color="text.secondary"
                      sx={{ ml: 1 }}
                    >
                      (по умолчанию)
                    </Typography>
                  )}
                </TableCell>
                <TableCell align="right">
                  <Tooltip title="Редактировать">
                    <IconButton onClick={() => openEdit(item)}>
                      <Edit />
                    </IconButton>
                  </Tooltip>
                  <Tooltip
                    title={
                      item.in_use
                        ? 'Используется в багах — удаление запрещено'
                        : item.is_default
                          ? 'Нельзя удалить дефолтный статус'
                          : 'Удалить'
                    }
                  >
                    <span>
                      <IconButton
                        color="error"
                        disabled={item.in_use || item.is_default}
                        onClick={() => {
                          if (confirm(`Удалить «${item.name}»?`)) {
                            deleteMutation.mutate(item.id);
                          }
                        }}
                      >
                        <Delete />
                      </IconButton>
                    </span>
                  </Tooltip>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      <Dialog open={dialogOpen} onClose={() => setDialogOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle color="primary">{editing ? 'Редактировать' : 'Создать'} элемент</DialogTitle>
        <DialogContent>
          {error && (
            <Alert severity="error" sx={{ mb: 2 }}>
              {error}
            </Alert>
          )}
          <TextField
            autoFocus
            margin="dense"
            label="Название"
            fullWidth
            value={name}
            onChange={(e) => setName(e.target.value)}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDialogOpen(false)}>Отмена</Button>
          <Button
            variant="contained"
            onClick={() => saveMutation.mutate()}
            disabled={saveMutation.isPending || !name.trim()}
          >
            Сохранить
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
