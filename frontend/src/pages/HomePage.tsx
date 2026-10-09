import { Typography } from '@mui/material';
import { useEffect, useState } from 'react';
import { fetchHealth } from '../api/health';

export default function HomePage() {
  const [status, setStatus] = useState('...');

  useEffect(() => {
    fetchHealth()
      .then((d) => setStatus(d.status))
      .catch(() => setStatus('error'));
  }, []);

  return <Typography>API status: {status}</Typography>;
}