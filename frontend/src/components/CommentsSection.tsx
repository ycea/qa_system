import {
  Alert,
  Box,
  Button,
  List,
  ListItem,
  ListItemText,
  TextField,
  Typography,
} from '@mui/material';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { addComment, fetchComments } from '../api/comments';
import { extractErrorMessage } from '../utils/errors';

type Props = {
  projectId: number;
  bugId: number;
};

export default function CommentsSection({ projectId, bugId }: Props) {
  const queryClient = useQueryClient();
  const [body, setBody] = useState('');
  const [error, setError] = useState<string | null>(null);

  const { data: comments } = useQuery({
    queryKey: ['comments', projectId, bugId],
    queryFn: () => fetchComments(projectId, bugId),
    enabled: !!projectId && !!bugId,
  });

  const addMutation = useMutation({
    mutationFn: () => addComment(projectId, bugId, body.trim()),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['comments', projectId, bugId] });
      setBody('');
      setError(null);
    },
    onError: (err) => setError(extractErrorMessage(err, 'Не удалось добавить комментарий')),
  });

  return (
    <Box>
      <Typography variant="h6" gutterBottom>
        Комментарии
      </Typography>

      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      {comments && comments.length > 0 ? (
        <List dense>
          {comments.map((comment) => (
            <ListItem key={comment.id} disableGutters>
              <ListItemText
                primary={comment.body}
                secondary={`${comment.user?.name ?? '—'} · ${new Date(comment.created_at).toLocaleString()}`}
              />
            </ListItem>
          ))}
        </List>
      ) : (
        <Typography color="text.secondary" sx={{ mb: 2 }}>
          Пока нет комментариев.
        </Typography>
      )}

      <TextField
        label="Новый комментарий"
        fullWidth
        multiline
        rows={2}
        value={body}
        onChange={(e) => setBody(e.target.value)}
      />
      <Button
        variant="contained"
        sx={{ mt: 1 }}
        disabled={body.trim() === '' || addMutation.isPending}
        onClick={() => addMutation.mutate()}
      >
        Отправить
      </Button>
    </Box>
  );
}
