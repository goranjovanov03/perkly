import { apiClient } from '../lib/api';
import type { LoginCredentials, ForgotPasswordData, ResetPasswordData, User, Organization, ApiError } from '../types/auth';
import { ORGANIZATION_STORAGE_KEY } from '../types/auth';

function getErrorMessage(error: unknown, defaultMessage: string): string {
  if (error && typeof error === 'object' && 'error' in error) {
    const apiError = error as ApiError;
    return apiError.error?.message || defaultMessage;
  }
  return defaultMessage;
}

function getFieldErrors(error: unknown): Record<string, string[]> | undefined {
  if (error && typeof error === 'object' && 'error' in error) {
    const apiError = error as ApiError;
    return apiError.error?.fields;
  }
  return undefined;
}

function getAuthHeaders(): Record<string, string> {
  const headers: Record<string, string> = {};
  const orgId = localStorage.getItem(ORGANIZATION_STORAGE_KEY);
  if (orgId) {
    headers['X-Organization-Id'] = orgId;
  }
  return headers;
}

export async function getCsrfCookie(): Promise<void> {
  const baseUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8001/api';
  const response = await fetch(`${baseUrl.replace('/api', '')}/sanctum/csrf-cookie`, {
    credentials: 'include',
  });
  if (!response.ok) {
    throw new Error('Failed to get CSRF cookie');
  }
}

export async function login(credentials: LoginCredentials): Promise<{ user: User }> {
  await getCsrfCookie();
  
  const response = await apiClient.POST('/v1/auth/login', {
    body: credentials,
    credentials: 'include',
  });

  if (response.error) {
    throw response.error;
  }

  return response.data as { user: User };
}

export async function logout(): Promise<void> {
  const response = await apiClient.POST('/v1/auth/logout', {
    credentials: 'include',
  });

  if (response.error) {
    throw response.error;
  }
}

export async function logoutAll(): Promise<void> {
  const response = await apiClient.POST('/v1/auth/logout-all', {
    credentials: 'include',
  });

  if (response.error) {
    throw response.error;
  }
}

export async function getMe(): Promise<{ user: User }> {
  const response = await apiClient.GET('/v1/me', {
    credentials: 'include',
  });

  if (response.error) {
    throw response.error;
  }

  return response.data as { user: User };
}

export async function getOrganizations(): Promise<{ organizations: Organization[] }> {
  const response = await apiClient.GET('/v1/me/organizations', {
    credentials: 'include',
    headers: getAuthHeaders(),
  });

  if (response.error) {
    throw response.error;
  }

  return response.data as { organizations: Organization[] };
}

export async function completeOnboarding(): Promise<{ user: User }> {
  const response = await apiClient.POST('/v1/me/onboarding/complete', {
    credentials: 'include',
    headers: getAuthHeaders(),
  });

  if (response.error) {
    throw response.error;
  }

  return response.data as { user: User };
}

export async function forgotPassword(data: ForgotPasswordData): Promise<{ message: string }> {
  const response = await apiClient.POST('/v1/auth/forgot-password', {
    body: data,
    credentials: 'include',
  });

  if (response.error) {
    throw response.error;
  }

  return response.data as { message: string };
}

export async function resetPassword(data: ResetPasswordData): Promise<{ message: string }> {
  const response = await apiClient.POST('/v1/auth/reset-password', {
    body: data,
    credentials: 'include',
  });

  if (response.error) {
    throw response.error;
  }

  return response.data as { message: string };
}

export { getErrorMessage, getFieldErrors };