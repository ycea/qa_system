import { createBrowserRouter } from 'react-router-dom';
import MainLayout from '../layouts/MainLayout';
import BugDetailsPage from '../pages/BugDetailsPage';
import BugsPage from '../pages/BugsPage';
import DashboardPage from '../pages/DashboardPage';
import HomePage from '../pages/HomePage';
import LoginPage from '../pages/LoginPage';
import MetricsPage from '../pages/MetricsPage';
import NotFoundPage from '../pages/NotFoundPage';
import ProjectPage from '../pages/ProjectPage';
import RegisterPage from '../pages/RegisterPage';
import ReportsPage from '../pages/ReportsPage';
import ProtectedRoute from './ProtectedRoute';
export const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  { path: '/register', element: <RegisterPage /> },
  {
    path: '/',
    element: (
      <ProtectedRoute>
        <MainLayout />
      </ProtectedRoute>
    ),
    children: [
      { index: true, element: <HomePage /> },
      { path: '*', element: <NotFoundPage /> },
      { path: 'projects/:id', element: <ProjectPage /> },
      { path: 'projects/:id/bugs', element: <BugsPage /> },
      { path: 'projects/:id/bugs/:bugId', element: <BugDetailsPage /> },
      { path: 'projects/:id/dashboard', element: <DashboardPage /> },
      { path: 'projects/:id/metrics', element: <MetricsPage /> },
      { path: 'reports', element: <ReportsPage /> },
    ],
  },
]);
