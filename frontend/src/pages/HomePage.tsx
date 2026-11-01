import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { OrganizationPicker } from '../components/auth/OrganizationPicker';

export function HomePage() {
  const { t } = useTranslation();
  const { user, organizations, selectOrganization, currentOrganization } = useAuth();

  return (
    <div style={styles.container}>
      <h1 style={styles.title}>{t('home.welcome', { name: user?.name || 'User' })}</h1>
      
      {currentOrganization && (
        <div style={styles.orgInfo}>
          <h2 style={styles.orgName}>{t('home.currentOrganization', { orgName: currentOrganization.name })}</h2>
        </div>
      )}

      {!currentOrganization && organizations.length > 0 && (
        <div style={styles.orgInfo}>
          <h2>{t('auth.organizationPicker.title')}</h2>
          <p>{t('auth.organizationPicker.description')}</p>
        </div>
      )}

      {organizations.length > 1 && !currentOrganization && (
        <OrganizationPicker organizations={organizations} onSelect={selectOrganization} />
      )}
    </div>
  );
}

const styles: Record<string, React.CSSProperties> = {
  container: {
    maxWidth: '800px',
    margin: '0 auto',
  },
  title: {
    margin: '0 0 1.5rem',
    color: '#333',
  },
  orgInfo: {
    backgroundColor: 'white',
    padding: '1.5rem',
    borderRadius: '8px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
  },
  orgName: {
    margin: '0 0 0.5rem',
    color: '#333',
  },
};