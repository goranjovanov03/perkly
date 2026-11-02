import { apiClient, apiBaseUrl } from '../lib/api';
import type { LoginCredentials, ForgotPasswordData, ResetPasswordData, User, Organization, BootstrapResponse } from '../types/auth';
import { ORGANIZATION_STORAGE_KEY } from '../types/auth';
import { login as apiLogin, logout as apiLogout, logoutAll as apiLogoutAll, forgotPassword as apiForgotPassword, resetPassword as apiResetPassword, getBootstrap, updateLocale, getNotificationPreferences, updateNotificationPreferences, ApiError } from '@perkly/api-client';

function toApiError(error: unknown): ApiError {
  if (error && typeof error === 'object' && 'error' in error) {
    const apiError = error as { error?: { code?: string; message?: string; request_id?: string; fields?: Record<string, string[]> } };
    const err = apiError.error;
    return new ApiError(
      err?.message || 'API Error',
      err?.code || 'UNKNOWN_ERROR',
      0,
      err?.fields
    );
  }
  return new ApiError('Unknown error', 'UNKNOWN_ERROR', 0);
}

function getErrorMessage(error: unknown, defaultMessage: string): string {
  if (error instanceof ApiError) {
    return error.message || defaultMessage;
  }
  if (error && typeof error === 'object' && 'error' in error) {
    const apiError = error as { error?: { message?: string } };
    return apiError.error?.message || defaultMessage;
  }
  return defaultMessage;
}

function getFieldErrors(error: unknown): Record<string, string[]> | undefined {
  if (error instanceof ApiError) {
    return error.fields;
  }
  if (error && typeof error === 'object' && 'error' in error) {
    const apiError = error as { error?: { fields?: Record<string, string[]> } };
    return apiError.error?.fields;
  }
  return undefined;
}

export async function login(credentials: LoginCredentials): Promise<{ user: User }> {
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
    throw toApiError(response.error);
  }

  return response.data as { user: User };
}

export async function getBootstrapData(): Promise<BootstrapResponse> {
  const orgId = localStorage.getItem(ORGANIZATION_STORAGE_KEY) || undefined;
  const response = await getBootstrap(apiClient, orgId);
  return response as BootstrapResponse;
}

export async function getOrganizations(): Promise<{ organizations: Organization[] }> {
  const response = await apiClient.GET('/v1/me/organizations', {
    credentials: 'include',
  });

  if (response.error) {
    throw toApiError(response.error);
  }

  return response.data as { organizations: Organization[] };
}

export async function completeOnboarding(): Promise<{ user: User }> {
  const response = await apiClient.POST('/v1/me/onboarding/complete', {
    credentials: 'include',
  });

  if (response.error) {
    throw toApiError(response.error);
  }

  return response.data as { user: User };
}

export async function forgotPassword(data: ForgotPasswordData): Promise<{ message: string }> {
  const response = await apiForgotPassword(apiClient, apiBaseUrl, data.email);
  return response;
}

export async function resetPassword(data: ResetPasswordData): Promise<{ message: string }> {
  const response = await apiResetPassword(apiClient, apiBaseUrl, data.token, data.email, data.password, data.password_confirmation);
  return response;
}

export { getErrorMessage, getFieldErrors, updateLocale, getNotificationPreferences, updateNotificationPreferences };