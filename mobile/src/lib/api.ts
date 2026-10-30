import { createApiClient } from '@perkly/api-client';

const baseUrl = process.env.EXPO_PUBLIC_API_URL || 'http://10.0.2.2:8001/api';

export const apiClient = createApiClient(baseUrl);