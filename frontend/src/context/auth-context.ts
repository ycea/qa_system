import { createContext } from 'react';
import type * as authApi from '../api/auth';

export type AuthContextType = {
  user: authApi.User | null;
  isLoading: boolean;
  login: (data: { name: string; password: string }) => Promise<void>;
  register: (data: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) => Promise<void>;
  logout: () => Promise<void>;
};

export const AuthContext = createContext<AuthContextType | undefined>(undefined);
