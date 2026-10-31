import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { OrganizationPicker } from '../components/auth/OrganizationPicker';

export function HomePage() {
  const { t } = useTranslation();
  const { user, organizations, selectedOrganizationId, selectOrganization, logout, logoutAll } = useAuth();

  const currentOrg = organizations.find((org) => org.id === selectedOrganizationId);

  return (
    <div style={styles.container}>
      <header style={styles.header}>
        <h1 style={styles.title}>{t('home.welcome', { name: user?.name || 'User' })}</h1>
        <div style={styles.actions}>
          {organizations.length > 1 && (
            <button onClick={() => selectOrganization('')} style={styles.orgButton}>
              {currentOrg ? t('home.currentOrganization', { orgName: currentOrg.name }) : t('home.noOrganization')}
            </button>
          )}
          <button onClick={logout} style={styles.logoutButton}>
            {t('auth.logout.button')}
          </button>
          <button onClick={logoutAll} style={styles.logoutAllButton}>
            {t('auth.logout.logoutAll')}
          </button>
        </div>
      </header>

      <main style={styles.main}>
        {currentOrg && (
          <div style={styles.orgInfo}>
            <h2>{t('home.currentOrganization', { orgName: currentOrg.name })}</h2>
            <p>{t('home.currentOrganization', { orgName: currentOrg.name })}</p>
          </div>
        )}

        {!currentOrg && organizations.length > 0 && (
          <div style={styles.orgInfo}>
            <h2>{t('auth.organizationPicker.title')}</h2>
            <p>{t('auth.organizationPicker.description')}</p>
          </div>
        )}

        {organizations.length > 1 && !currentOrg && (
          <OrganizationPicker organizations={organizations} onSelect={selectOrganization} />
        )}
      </main>
    </div>
  );
}

const styles: Record<string, React.CSSProperties> = {
  container: {
    minHeight: '100vh',
    backgroundColor: '#f5f5f5',
  },
  header: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '1.5rem 2rem',
    backgroundColor: 'white',
    borderBottom: '1px solid #eee',
  },
  title: {
    margin: 0,
    color: '#333',
  },
  actions: {
    display: 'flex',
    gap: '0.75rem',
    alignItems: 'center',
  },
  orgButton: {
    padding: '0.5rem 1rem',
    backgroundColor: '#f5f5f5',
    border: '1px solid #ddd',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
  },
  logoutButton: {
    padding: '0.5rem 1rem',
    backgroundColor: '#0066cc',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
  },
  logoutAllButton: {
    padding: '0.5rem 1rem',
    backgroundColor: '#dc3545',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
  },
  main: {
    padding: '2rem',
    maxWidth: '800px',
    margin: '0 auto',
  },
  orgInfo: {
    backgroundColor: 'white',
    padding: '1.5rem',
    borderRadius: '8px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
  },
};