import { AppBar, Box, Container, Toolbar, Typography } from '@mui/material';
import { Outlet } from 'react-router-dom';

export default function MainLayout() {
  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
      <AppBar position="static">
        <Toolbar>
          <Typography variant="h6">QMS</Typography>
        </Toolbar>
      </AppBar>
      <Container sx={{ mt: 4, flex: 1 }}>
        <Outlet />
      </Container>
    </Box>
  );
}
