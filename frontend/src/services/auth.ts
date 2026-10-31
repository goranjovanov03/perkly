import { apiClient, apiBaseUrl, initCsrfCookie } from '../lib/api';
import type { LoginCredentials, ForgotPasswordData, ResetPasswordData, User, Organization, ApiError } from '../types/auth';
import { ORGANIZATION_STORAGE_KEY } from '../types/auth';
import { login as apiLogin, logout as apiLogout, logoutAll as apiLogoutAll, forgotPassword as apiForgotPassword, resetPassword as apiResetPassword } from '@perkly/api-client';

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

export async function login(credentials: LoginCredentials): Promise<{ user: User }> {
  await initCsrfCookie();
  
  const response = await apiLogin(apiClient, apiBaseUrl, credentials.email, credentials.password);
  return response;
}

export async function logout(): Promise<void> {
  await apiLogout(apiClient, apiBaseUrl);
}

export async function logoutAll(): Promise<void> {
  await apiLogoutAll(apiClient, apiBaseUrl);
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
  await initCsrfCookie();
  const response = await apiForgotPassword(apiClient, apiBaseUrl, data.email);
  return response;
}

export async function resetPassword(data: ResetPasswordData): Promise<{ message: string }> {
  await initCsrfCookie();
  const response = await apiResetPassword(apiClient, apiBaseUrl, data.token, data.email, data.password, data.password_confirmation);
  return response;
}

export { getErrorMessage, getFieldErrors };