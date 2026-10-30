import { createApiClient } from '@perkly/api-client';

const baseUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8001/api';

export const apiClient = createApiClient(baseUrl);