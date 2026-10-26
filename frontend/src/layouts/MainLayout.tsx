import { AppBar, Avatar, Box, Button, Container, Toolbar, Typography } from '@mui/material';
import { Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/use-auth';

export default function MainLayout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <Box
      sx={{
        display: 'flex',
        flexDirection: 'column',
        minHeight: '100vh',
        width: '100%',
        bgcolor: 'background.default',
      }}
    >
      <AppBar
        position="sticky"
        elevation={0}
        sx={{
          bgcolor: 'background.paper',
          color: 'text.primary',
          borderBottom: 1,
          borderColor: 'divider',
        }}
      >
        <Toolbar
          sx={{
            display: 'flex',
            justifyContent: 'space-between',
            minHeight: 64,
          }}
        >
          <Typography
            variant="h6"
            onClick={() => navigate('/')}
            sx={{
              fontWeight: 800,
              letterSpacing: 2,
              color: 'primary.main',
              cursor: 'pointer',
              userSelect: 'none',
            }}
          >
            QMS
          </Typography>

          <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
            {user && (
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                <Avatar
                  sx={{
                    width: 34,
                    height: 34,
                    bgcolor: 'secondary.main',
                    color: 'secondary.contrastText',
                    fontSize: 15,
                    fontWeight: 700,
                  }}
                >
                  {user.name.charAt(0).toUpperCase()}
                </Avatar>
                <Typography variant="body2" sx={{ fontWeight: 500 }}>
                  {user.name}
                </Typography>
              </Box>
            )}
            <Button
              color="primary"
              variant="outlined"
              size="small"
              onClick={handleLogout}
              sx={{ borderRadius: 2 }}
            >
              Выйти
            </Button>
          </Box>
        </Toolbar>
      </AppBar>

      <Box
        component="main"
        sx={{
          flex: 1,
          width: '100%',
          display: 'flex',
          justifyContent: 'center',
          py: { xs: 3, md: 5 },
        }}
      >
        <Container maxWidth="lg" sx={{ px: { xs: 2, sm: 3 } }}>
          <Outlet />
        </Container>
      </Box>
    </Box>
  );
}
