import { createApiClient, ensureCsrfCookie } from '@perkly/api-client';

const baseUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8001/api';

export const { client: apiClient, baseUrl: apiBaseUrl } = createApiClient(baseUrl);

export async function initCsrfCookie(): Promise<void> {
  await ensureCsrfCookie(baseUrl);
}