import { createApiClient, type ApiClient } from '@perkly/api-client';
import * as SecureStore from 'expo-secure-store';
import * as Device from 'expo-device';

const baseUrl = process.env.EXPO_PUBLIC_API_URL || 'http://10.0.2.2:8001/api';

const TOKEN_KEY = 'auth_token';
const ORG_ID_KEY = 'org_id';

let apiClientInstance: { client: ApiClient; baseUrl: string } | null = null;

async function getAuthHeaders(): Promise<Record<string, string>> {
  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  const orgId = await SecureStore.getItemAsync(ORG_ID_KEY);
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  if (orgId) {
    headers['X-Organization-Id'] = orgId;
  }
  return headers;
}

async function createApiClientInstance(): Promise<{ client: ApiClient; baseUrl: string }> {
  const { createApiClient } = await import('@perkly/api-client');
  return createApiClient(baseUrl);
}

export async function getApiClient(): Promise<{ client: ApiClient; baseUrl: string }> {
  if (!apiClientInstance) {
    apiClientInstance = await createApiClientInstance();
  }
  return apiClientInstance;
}

export async function saveToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

export async function getToken(): Promise<string | null> {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function clearToken(): Promise<void> {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}

export async function saveOrgId(orgId: string): Promise<void> {
  await SecureStore.setItemAsync(ORG_ID_KEY, orgId);
}

export async function getOrgId(): Promise<string | null> {
  return SecureStore.getItemAsync(ORG_ID_KEY);
}

export async function clearOrgId(): Promise<void> {
  await SecureStore.deleteItemAsync(ORG_ID_KEY);
}

export async function clearAuth(): Promise<void> {
  await clearToken();
  await clearOrgId();
}

export async function loginRequest(
  email: string,
  password: string
): Promise<{ token: string }> {
  const { client } = await getApiClient();
  const deviceName = Device.modelName || 'mobile';
  const response = await client.POST('/v1/auth/token', {
    body: { email, password, device_name: deviceName },
    headers: { Accept: 'application/json' },
  });
  if (response.error) {
    throw response.error;
  }
  return response.data as { token: string };
}

export async function getOrganizations(): Promise<{ organizations: Array<{ id: string; name: string; slug: string }> }> {
  const { client } = await getApiClient();
  const headers = await getAuthHeaders();
  const response = await client.GET('/v1/me/organizations', { headers });
  if (response.error) {
    throw response.error;
  }
  return response.data as { organizations: Array<{ id: string; name: string; slug: string }> };
}

export async function deleteToken(): Promise<void> {
  const { client } = await getApiClient();
  const headers = await getAuthHeaders();
  const response = await client.DELETE('/v1/auth/token', { headers });
  if (response.error) {
    throw response.error;
  }
}

export async function logoutAll(): Promise<void> {
  const { client } = await getApiClient();
  const headers = await getAuthHeaders();
  const response = await client.POST('/v1/auth/logout-all', { headers });
  if (response.error) {
    throw response.error;
  }
}

export async function getMe(): Promise<{ user: { id: string; name: string; email: string } }> {
  const { client } = await getApiClient();
  const headers = await getAuthHeaders();
  const response = await client.GET('/v1/me', { headers });
  if (response.error) {
    throw response.error;
  }
  return response.data as { user: { id: string; name: string; email: string } };
}