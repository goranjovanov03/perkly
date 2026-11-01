import { useTranslation } from 'react-i18next';
import type { Organization } from '../../types/auth';

interface OrganizationPickerProps {
  organizations: Organization[];
  onSelect: (orgId: string) => void;
}

export function OrganizationPicker({ organizations, onSelect }: OrganizationPickerProps) {
  const { t } = useTranslation();

  if (organizations.length <= 1) {
    return null;
  }

  return (
    <div style={styles.overlay}>
      <div style={styles.modal}>
        <h2 style={styles.title}>{t('auth.organizationPicker.title')}</h2>
        <p style={styles.description}>{t('auth.organizationPicker.description')}</p>
        <div style={styles.list}>
          {organizations.map((org: Organization) => (
            <button
              key={org.id}
              onClick={() => onSelect(org.id)}
              style={styles.orgButton}
            >
              <span style={styles.orgName}>{org.name}</span>
              <span style={styles.orgSlug}>{org.slug}</span>
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}

const styles: Record<string, React.CSSProperties> = {
  overlay: {
    position: 'fixed',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 1000,
    padding: '1rem',
  },
  modal: {
    backgroundColor: 'white',
    borderRadius: '8px',
    padding: '2rem',
    width: '100%',
    maxWidth: '400px',
    boxShadow: '0 4px 16px rgba(0,0,0,0.2)',
  },
  title: {
    marginBottom: '0.5rem',
    textAlign: 'center',
    color: '#333',
  },
  description: {
    marginBottom: '1.5rem',
    textAlign: 'center',
    color: '#666',
  },
  list: {
    display: 'flex',
    flexDirection: 'column',
    gap: '0.75rem',
  },
  orgButton: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '1rem',
    backgroundColor: '#f5f5f5',
    border: '1px solid #ddd',
    borderRadius: '4px',
    cursor: 'pointer',
    textAlign: 'left',
    width: '100%',
  },
  orgName: {
    fontWeight: 500,
    color: '#333',
  },
  orgSlug: {
    color: '#666',
    fontSize: '0.875rem',
  },
};