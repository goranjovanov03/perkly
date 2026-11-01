import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { LanguageSwitcher } from '../LanguageSwitcher';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../../lib/api';
import { listNotifications } from '@perkly/api-client';
import { Link } from 'react-router-dom';

export function Header() {
  const { t } = useTranslation();
  const { user, currentOrganization, logout, selectedOrganizationId, capabilities } = useAuth();

  const { data: unreadCount } = useQuery<number>({
    queryKey: ['notifications-unread', selectedOrganizationId],
    queryFn: async () => {
      const res = await listNotifications(apiClient, selectedOrganizationId!, undefined, true);
      return res.notifications.filter((n: { read_at: string | null }) => !n.read_at).length;
    },
    enabled: !!selectedOrganizationId && capabilities.includes('notifications.view'),
    refetchInterval: 30000,
    staleTime: 30000,
  });

  const hasNotifications = capabilities.includes('notifications.view');

  return (
    <header style={styles.header}>
      <div style={styles.left}>
        <h1 style={styles.title}>{currentOrganization?.name || t('header.appName')}</h1>
      </div>
      <div style={styles.right}>
        {hasNotifications && (
          <Link to="/notifications" style={styles.notificationBell} aria-label={t('notifications.title')}>
            <span style={styles.bellIcon}>🔔</span>
            {unreadCount && unreadCount > 0 && (
              <span style={styles.badge}>{unreadCount > 99 ? '99+' : unreadCount}</span>
            )}
          </Link>
        )}
        <LanguageSwitcher />
        <div style={styles.userInfo}>
          <span style={styles.userName}>{user?.name || ''}</span>
          <button onClick={logout} style={styles.logoutButton}>
            {t('auth.logout.button')}
          </button>
        </div>
      </div>
    </header>
  );
}

const styles: Record<string, React.CSSProperties> = {
  header: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '0.75rem 1.5rem',
    backgroundColor: 'white',
    borderBottom: '1px solid #eee',
    position: 'sticky',
    top: 0,
    zIndex: 100,
    flexWrap: 'wrap',
    gap: '0.5rem',
  },
  left: {
    display: 'flex',
    alignItems: 'center',
    gap: '1rem',
  },
  title: {
    margin: 0,
    fontSize: '1.125rem',
    fontWeight: 600,
    color: '#333',
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    maxWidth: '300px',
  },
  right: {
    display: 'flex',
    alignItems: 'center',
    gap: '1rem',
    flexWrap: 'wrap',
    justifyContent: 'flex-end',
  },
  notificationBell: {
    position: 'relative',
    textDecoration: 'none',
    color: '#333',
    fontSize: '1.25rem',
    padding: '0.25rem',
  },
  bellIcon: {
    display: 'block',
    lineHeight: 1,
  },
  badge: {
    position: 'absolute',
    top: '-4px',
    right: '-4px',
    minWidth: '18px',
    height: '18px',
    backgroundColor: '#dc3545',
    color: 'white',
    borderRadius: '9999px',
    fontSize: '0.625rem',
    fontWeight: 600,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    padding: '0 4px',
  },
  userInfo: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.75rem',
  },
  userName: {
    fontSize: '0.875rem',
    color: '#666',
    whiteSpace: 'nowrap',
  },
  logoutButton: {
    padding: '0.375rem 0.75rem',
    backgroundColor: '#dc3545',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
  },
};