import createClient, { type Client, type Middleware } from 'openapi-fetch';
import type { paths } from './schema';

export type ApiClient = Client<paths>;

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

export function createApiClient(baseUrl: string): { client: ApiClient; baseUrl: string } {
  const client = createClient<paths>({ baseUrl, credentials: 'include' });
  client.use(acceptJsonMiddleware);
  client.use(csrfMiddleware);
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
  if (error) throw error;
  return data!;
}

export async function logout(client: ApiClient, baseUrl: string): Promise<void> {
  await ensureCsrfCookie(baseUrl);
  const { error } = await client.POST('/v1/auth/logout');
  if (error) throw error;
}

export async function logoutAll(client: ApiClient, baseUrl: string): Promise<void> {
  await ensureCsrfCookie(baseUrl);
  const { error } = await client.POST('/v1/auth/logout-all');
  if (error) throw error;
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
  if (error) throw error;
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
  if (error) throw error;
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
  if (error) throw error;
  return data!;
}