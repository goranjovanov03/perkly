import createClient, { type Client, type Middleware } from 'openapi-fetch';
import type { paths } from './schema';

export type ApiClient = Client<paths>;

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly code: string,
    public readonly status: number,
    public readonly fields?: Record<string, string[]>
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

const ORGANIZATION_STORAGE_KEY = 'selectedOrganizationId';

const acceptJsonMiddleware: Middleware = {
  onRequest: async ({ request }) => {
    request.headers.set('Accept', 'application/json');
    return request;
  },
};

const csrfMiddleware: Middleware = {
  onRequest: async ({ request }) => {
    if (request.method === 'GET' || request.method === 'HEAD' || request.method === 'OPTIONS') {
      return request;
    }
    if (typeof document !== 'undefined') {
      const cookieString = document.cookie;
      const match = cookieString.match(/XSRF-TOKEN=([^;]+)/);
      if (match) {
        request.headers.set('X-XSRF-TOKEN', decodeURIComponent(match[1]));
      }
    }
    return request;
  },
};

const organizationMiddleware: Middleware = {
  onRequest: async ({ request }) => {
    if (typeof window !== 'undefined') {
      const orgId = localStorage.getItem(ORGANIZATION_STORAGE_KEY);
      if (orgId) {
        request.headers.set('X-Organization-Id', orgId);
      }
    }
    return request;
  },
};

export function createApiClient(baseUrl: string): { client: ApiClient; baseUrl: string } {
  const client = createClient<paths>({ baseUrl, credentials: 'include' });
  client.use(acceptJsonMiddleware);
  client.use(csrfMiddleware);
  client.use(organizationMiddleware);
  return { client, baseUrl };
}

let csrfCookieInitialized = false;

export async function ensureCsrfCookie(baseUrl: string): Promise<void> {
  if (csrfCookieInitialized) {
    return;
  }
  const origin = new URL(baseUrl).origin;
  await fetch(`${origin}/sanctum/csrf-cookie`, {
    method: 'GET',
    credentials: 'include',
  });
  csrfCookieInitialized = true;
}

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

export async function login(
  client: ApiClient,
  baseUrl: string,
  email: string,
  password: string
): Promise<paths['/v1/auth/login']['post']['responses'][200]['content']['application/json']> {
  await ensureCsrfCookie(baseUrl);
  const { data, error } = await client.POST('/v1/auth/login', {
    body: { email, password },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function logout(client: ApiClient, baseUrl: string): Promise<void> {
  await ensureCsrfCookie(baseUrl);
  const { error } = await client.POST('/v1/auth/logout');
  if (error) throw toApiError(error);
}

export async function logoutAll(client: ApiClient, baseUrl: string): Promise<void> {
  await ensureCsrfCookie(baseUrl);
  const { error } = await client.POST('/v1/auth/logout-all');
  if (error) throw toApiError(error);
}

export async function forgotPassword(
  client: ApiClient,
  baseUrl: string,
  email: string
): Promise<paths['/v1/auth/forgot-password']['post']['responses'][202]['content']['application/json']> {
  await ensureCsrfCookie(baseUrl);
  const { data, error } = await client.POST('/v1/auth/forgot-password', {
    body: { email },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function resetPassword(
  client: ApiClient,
  baseUrl: string,
  token: string,
  email: string,
  password: string,
  password_confirmation: string
): Promise<paths['/v1/auth/reset-password']['post']['responses'][200]['content']['application/json']> {
  await ensureCsrfCookie(baseUrl);
  const { data, error } = await client.POST('/v1/auth/reset-password', {
    body: { token, email, password, password_confirmation },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function updatePassword(
  client: ApiClient,
  baseUrl: string,
  current_password: string,
  password: string,
  password_confirmation: string
): Promise<paths['/v1/me']['patch']['responses'][200]['content']['application/json']> {
  await ensureCsrfCookie(baseUrl);
  const { data, error } = await client.PATCH('/v1/me', {
    body: { current_password, password, password_confirmation },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function getBootstrap(
  client: ApiClient,
  orgId?: string
): Promise<paths['/v1/bootstrap']['get']['responses'][200]['content']['application/json']> {
  const { data, error } = await client.GET('/v1/bootstrap', {
    params: { header: orgId ? { 'X-Organization-Id': orgId } : undefined },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function listNotifications(
  client: ApiClient,
  orgId: string,
  cursor?: string,
  unread?: boolean
): Promise<paths['/v1/org/notifications']['get']['responses'][200]['content']['application/json']> {
  const { data, error } = await client.GET('/v1/org/notifications', {
    params: { query: { cursor, unread }, header: {} as { 'X-Organization-Id': string } },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function markNotificationAsRead(
  client: ApiClient,
  orgId: string,
  id: string
): Promise<void> {
  const { error } = await client.POST('/v1/org/notifications/{id}/read', {
    params: { path: { id }, header: {} as { 'X-Organization-Id': string } },
  });
  if (error) throw toApiError(error);
}

export async function markAllNotificationsAsRead(
  client: ApiClient,
  orgId: string
): Promise<void> {
  const { error } = await client.POST('/v1/org/notifications/read-all', {
    params: { header: {} as { 'X-Organization-Id': string } },
  });
  if (error) throw toApiError(error);
}

export async function getNotificationPreferences(
  client: ApiClient
): Promise<paths['/v1/me/notification-preferences']['get']['responses'][200]['content']['application/json']> {
  const { data, error } = await client.GET('/v1/me/notification-preferences');
  if (error) throw toApiError(error);
  return data!;
}

export async function updateNotificationPreferences(
  client: ApiClient,
  preferences: paths['/v1/me/notification-preferences']['put']['requestBody']['content']['application/json']['preferences']
): Promise<paths['/v1/me/notification-preferences']['put']['responses'][200]['content']['application/json']> {
  const { data, error } = await client.PUT('/v1/me/notification-preferences', {
    body: { preferences },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function updateLocale(
  client: ApiClient,
  locale: 'mk' | 'en'
): Promise<paths['/v1/me/locale']['patch']['responses'][200]['content']['application/json']> {
  const { data, error } = await client.PATCH('/v1/me/locale', {
    body: { locale },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function listIntegrations(
  client: ApiClient,
  orgId: string
): Promise<paths['/v1/org/integrations']['get']['responses'][200]['content']['application/json']> {
  const { data, error } = await client.GET('/v1/org/integrations', {
    params: { header: {} as { 'X-Organization-Id': string } },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function createIntegration(
  client: ApiClient,
  orgId: string,
  body: paths['/v1/org/integrations']['post']['requestBody']['content']['application/json']
): Promise<paths['/v1/org/integrations']['post']['responses'][201]['content']['application/json']> {
  const { data, error } = await client.POST('/v1/org/integrations', {
    body,
    params: { header: {} as { 'X-Organization-Id': string } },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function updateIntegration(
  client: ApiClient,
  orgId: string,
  id: string,
  body: paths['/v1/org/integrations/{id}/config']['put']['requestBody']['content']['application/json']
): Promise<paths['/v1/org/integrations/{id}/config']['put']['responses'][200]['content']['application/json']> {
  const { data, error } = await client.PUT('/v1/org/integrations/{id}/config', {
    params: { path: { id }, header: {} as { 'X-Organization-Id': string } },
    body,
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function getIntegration(
  client: ApiClient,
  orgId: string,
  id: string
): Promise<paths['/v1/org/integrations/{id}']['get']['responses'][200]['content']['application/json']> {
  const { data, error } = await client.GET('/v1/org/integrations/{id}', {
    params: { path: { id }, header: {} as { 'X-Organization-Id': string } },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function testIntegration(
  client: ApiClient,
  orgId: string,
  id: string
): Promise<paths['/v1/org/integrations/{id}/test']['post']['responses'][200]['content']['application/json']> {
  const { data, error } = await client.POST('/v1/org/integrations/{id}/test', {
    params: { path: { id }, header: {} as { 'X-Organization-Id': string } },
  });
  if (error) throw toApiError(error);
  return data!;
}

export async function updateIntegrationSecrets(
  client: ApiClient,
  orgId: string,
  id: string,
  secrets: Record<string, string>
): Promise<void> {
  const { error } = await client.PUT('/v1/org/integrations/{id}/secrets', {
    params: { path: { id }, header: {} as { 'X-Organization-Id': string } },
    body: { secrets },
  });
  if (error) throw toApiError(error);
}