import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { LanguageSwitcher } from './components/LanguageSwitcher';
import { apiClient } from './lib/api';
import './lib/i18n';
import './App.css';

const queryClient = new QueryClient();

function HealthPage() {
  const { t } = useTranslation();

  const { data, error, isLoading } = useQuery({
    queryKey: ['health'],
    queryFn: async () => {
      const response = await apiClient.GET('/v1/health');
      if (response.error) {
        throw new Error(JSON.stringify(response.error));
      }
      return response.data;
    },
  });

  return (
    <div style={{ padding: '2rem', maxWidth: '600px', margin: '0 auto' }}>
      <LanguageSwitcher />
      <h1>{t('health.title')}</h1>
      <div style={{ marginTop: '1rem' }}>
        <strong>{t('health.status')}: </strong>
        {isLoading && <span>{t('health.loading')}</span>}
        {data && <span style={{ color: 'green' }}>{t('health.ok')}: {JSON.stringify(data)}</span>}
        {error && (
          <span style={{ color: 'red' }}>
            {t('health.error')}: {error.message}
          </span>
        )}
      </div>
    </div>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <HealthPage />
    </QueryClientProvider>
  );
}

export default App;