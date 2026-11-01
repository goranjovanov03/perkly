import { createContext, useContext, useState, useEffect, useCallback, useMemo, type ReactNode } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getMe, getOrganizations, logout, logoutAll, login as loginApi } from '../services/auth';
import type { AuthState, LoginCredentials, ApiError } from '../types/auth';
import { ORGANIZATION_STORAGE_KEY, SESSION_EXPIRED_KEY } from '../types/auth';
import { getErrorMessage } from '../services/auth';

interface AuthContextType extends AuthState {
  login: (credentials: LoginCredentials) => Promise<{ error?: string; fieldErrors?: Record<string, string[]> }>;
  logout: () => Promise<void>;
  logoutAll: () => Promise<void>;
  selectOrganization: (orgId: string) => void;
  clearSessionExpired: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const [selectedOrganizationId, setSelectedOrganizationId] = useState<string | null>(() => {
    return localStorage.getItem(ORGANIZATION_STORAGE_KEY);
  });
  const [sessionExpired, setSessionExpired] = useState<boolean>(() => {
    return localStorage.getItem(SESSION_EXPIRED_KEY) === 'true';
  });

  const { data: meData, error: meError, isLoading: meLoading, refetch: refetchMe } = useQuery({
    queryKey: ['me'],
    queryFn: getMe,
    retry: false,
    staleTime: Infinity,
  });

  const { data: orgsData, isLoading: orgsLoading, refetch: refetchOrgs } = useQuery({
    queryKey: ['organizations'],
    queryFn: getOrganizations,
    retry: false,
    enabled: !!meData && !meError,
    staleTime: Infinity,
  });

  const loginMutation = useMutation({
    mutationFn: loginApi,
    onSuccess: async () => {
      await refetchMe();
      const orgs = await refetchOrgs();
      const organizations = orgs.data?.organizations || [];
      if (organizations.length === 1) {
        setSelectedOrganizationId(organizations[0].id);
        localStorage.setItem(ORGANIZATION_STORAGE_KEY, organizations[0].id);
      }
    },
  });

  const logoutMutation = useMutation({
    mutationFn: logout,
    onSuccess: () => {
      queryClient.clear();
      setSelectedOrganizationId(null);
      localStorage.removeItem(ORGANIZATION_STORAGE_KEY);
    },
  });

  const logoutAllMutation = useMutation({
    mutationFn: logoutAll,
    onSuccess: () => {
      queryClient.clear();
      setSelectedOrganizationId(null);
      localStorage.removeItem(ORGANIZATION_STORAGE_KEY);
    },
  });

  useEffect(() => {
    if (orgsData?.organizations) {
      const organizations = orgsData.organizations;
      const storedOrgId = localStorage.getItem(ORGANIZATION_STORAGE_KEY);
      
      if (storedOrgId && organizations.some(org => org.id === storedOrgId)) {
        setSelectedOrganizationId(storedOrgId);
      }
    }
  }, [orgsData]);

  useEffect(() => {
    if (meError) {
      const errorCode = meError && typeof meError === 'object' && 'error' in meError
        ? (meError as ApiError).error?.code
        : null;
      
      if (errorCode === 'UNAUTHENTICATED') {
        setSessionExpired(true);
        localStorage.setItem(SESSION_EXPIRED_KEY, 'true');
      }
    }
  }, [meError]);

  const login = useCallback(async (credentials: LoginCredentials) => {
    try {
      await loginMutation.mutateAsync(credentials);
      return { error: undefined, fieldErrors: undefined };
    } catch (error) {
      return {
        error: getErrorMessage(error, 'Login failed'),
        fieldErrors: error && typeof error === 'object' && 'error' in error 
          ? (error as ApiError).error?.fields 
          : undefined,
      };
    }
  }, [loginMutation]);

  const selectOrganization = useCallback((orgId: string) => {
    setSelectedOrganizationId(orgId);
    localStorage.setItem(ORGANIZATION_STORAGE_KEY, orgId);
  }, []);

  const clearSessionExpired = useCallback(() => {
    setSessionExpired(false);
    localStorage.removeItem(SESSION_EXPIRED_KEY);
  }, []);

  const user = meData?.user || null;
  const organizations = orgsData?.organizations || [];
  const isAuthenticated = !!user && !meError;
  const isLoading = meLoading || (isAuthenticated && orgsLoading);

  const value = useMemo(() => ({
    user,
    organizations,
    selectedOrganizationId,
    isAuthenticated,
    isLoading,
    sessionExpired,
    login,
    logout: logoutMutation.mutateAsync,
    logoutAll: logoutAllMutation.mutateAsync,
    selectOrganization,
    clearSessionExpired,
  }), [
    user,
    organizations,
    selectedOrganizationId,
    isAuthenticated,
    isLoading,
    sessionExpired,
    loginMutation,
    logoutMutation,
    logoutAllMutation,
  ]);

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}