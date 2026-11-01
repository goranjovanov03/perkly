import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import type { ReactNode } from 'react';

export function SessionExpiredGuard({ children }: { children: ReactNode }) {
  const { t } = useTranslation();
  const { sessionExpired, clearSessionExpired } = useAuth();

  useEffect(() => {
    if (sessionExpired) {
      clearSessionExpired();
    }
  }, [sessionExpired, clearSessionExpired]);

  if (sessionExpired) {
    return (
      <div style={styles.container}>
        <div style={styles.card}>
          <h2 style={styles.title}>{t('auth.sessionExpired')}</h2>
          <p style={styles.message}>{t('auth.sessionExpired')}</p>
        </div>
      </div>
    );
  }

  return <>{children}</>;
}

const styles: Record<string, React.CSSProperties> = {
  container: {
    minHeight: '100vh',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    padding: '1rem',
    backgroundColor: '#f5f5f5',
  },
  card: {
    width: '100%',
    maxWidth: '400px',
    padding: '2rem',
    backgroundColor: 'white',
    borderRadius: '8px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
    textAlign: 'center',
  },
  title: {
    marginBottom: '1rem',
    color: '#c00',
  },
  message: {
    color: '#666',
  },
};