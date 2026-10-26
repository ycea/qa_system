import { Paper, Typography } from '@mui/material';

export default function HomePage() {
  return (
    <Paper
      elevation={0}
      sx={{
        p: { xs: 3, md: 5 },
        borderRadius: 3,
        border: 1,
        borderColor: 'divider',
      }}
    >
      <Typography variant="h4" gutterBottom>
        Добро пожаловать!
      </Typography>
      <Typography variant="body1" color="text.secondary">
        Скоро здесь появится список ваших проектов.
      </Typography>
    </Paper>
  );
}
