import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { OrganizationPicker } from '../components/auth/OrganizationPicker';

export function OrganizationPickerPage() {
  const { t } = useTranslation();
  const { organizations, selectOrganization, isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div style={styles.loadingContainer}>
        <div style={styles.spinner}></div>
        <p>{t('common.loading')}</p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  return (
    <OrganizationPicker
      organizations={organizations}
      onSelect={selectOrganization}
    />
  );
}

const styles: Record<string, React.CSSProperties> = {
  loadingContainer: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: '100vh',
    gap: '1rem',
  },
  spinner: {
    width: '40px',
    height: '40px',
    border: '3px solid #f3f3f3',
    borderTop: '3px solid #0066cc',
    borderRadius: '50%',
    animation: 'spin 1s linear infinite',
  },
};