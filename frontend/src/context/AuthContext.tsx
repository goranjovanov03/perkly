import { createContext, useContext, useState, useEffect, useCallback, useMemo, type ReactNode } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getBootstrapData, logout, logoutAll, login as loginApi } from '../services/auth';
import type { AuthState, LoginCredentials, ApiError } from '../types/auth';
import { ORGANIZATION_STORAGE_KEY, SESSION_EXPIRED_KEY } from '../types/auth';
import { getErrorMessage } from '../services/auth';
import i18n from '../lib/i18n';

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

  const { data: bootstrapData, error: bootstrapError, isLoading: bootstrapLoading, refetch: refetchBootstrap } = useQuery({
    queryKey: ['bootstrap'],
    queryFn: getBootstrapData,
    retry: false,
    staleTime: Infinity,
  });

  const loginMutation = useMutation({
    mutationFn: loginApi,
    onSuccess: async () => {
      await refetchBootstrap();
      const data = await refetchBootstrap();
      const organizations = data.data?.organizations || [];
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
    if (bootstrapData?.organizations) {
      const organizations = bootstrapData.organizations;
      const storedOrgId = localStorage.getItem(ORGANIZATION_STORAGE_KEY);
      
      if (storedOrgId && organizations.some(org => org.id === storedOrgId)) {
        setSelectedOrganizationId(storedOrgId);
      }
    }
  }, [bootstrapData]);

  useEffect(() => {
    if (bootstrapError) {
      const errorCode = bootstrapError && typeof bootstrapError === 'object' && 'error' in bootstrapError
        ? (bootstrapError as ApiError).error?.code
        : null;
      
      if (errorCode === 'UNAUTHENTICATED') {
        setSessionExpired(true);
        localStorage.setItem(SESSION_EXPIRED_KEY, 'true');
      }
    }
  }, [bootstrapError]);

  useEffect(() => {
    if (bootstrapData?.user?.locale && bootstrapData.user.locale !== i18n.language) {
      i18n.changeLanguage(bootstrapData.user.locale);
      localStorage.setItem('language', bootstrapData.user.locale);
    }
  }, [bootstrapData?.user?.locale]);

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
    queryClient.invalidateQueries({ queryKey: ['bootstrap'] });
  }, [queryClient]);

  const clearSessionExpired = useCallback(() => {
    setSessionExpired(false);
    localStorage.removeItem(SESSION_EXPIRED_KEY);
  }, []);

  const user = bootstrapData?.user || null;
  const organizations = bootstrapData?.organizations || [];
  const currentOrganization = bootstrapData?.current_organization || null;
  const capabilities = bootstrapData?.capabilities || [];
  const locales = bootstrapData?.locales || ['mk', 'en'];
  const defaultLocale = bootstrapData?.default_locale || 'mk';
  const isAuthenticated = !!user && !bootstrapError;
  const isLoading = bootstrapLoading;

  const value = useMemo(() => ({
    user,
    organizations,
    selectedOrganizationId,
    currentOrganization,
    capabilities,
    locales,
    defaultLocale,
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
    currentOrganization,
    capabilities,
    locales,
    defaultLocale,
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