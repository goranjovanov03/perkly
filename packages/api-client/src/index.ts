import createClient, { type Client, type Middleware } from 'openapi-fetch';
import type { paths } from './schema';

export type ApiClient = Client<paths>;

const acceptJsonMiddleware: Middleware = {
  onRequest: async ({ request }) => {
    request.headers.set('Accept', 'application/json');
    return request;
  },
};

export function createApiClient(baseUrl: string): ApiClient {
  const client = createClient<paths>({ baseUrl });
  client.use(acceptJsonMiddleware);
  return client;
}