import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../lib/api';
import { listNotifications, markNotificationAsRead, markAllNotificationsAsRead } from '@perkly/api-client';
import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';

interface Notification {
  id: string;
  type: string;
  title_key: string;
  title_params?: Record<string, unknown> | null;
  body_key: string;
  body_params?: Record<string, unknown> | null;
  data?: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string;
}

interface NotificationsResponse {
  notifications: Notification[];
  next_cursor: string | null;
}

export function NotificationsPage() {
  const { t } = useTranslation();
  const { selectedOrganizationId, capabilities } = useAuth();
  const queryClient = useQueryClient();

  if (!capabilities.includes('notifications.view')) {
    return <div style={styles.noPermission}>{t('notifications.noPermission')}</div>;
  }
  const [cursor, setCursor] = useState<string | null>(null);
  const [unreadOnly, setUnreadOnly] = useState(false);
  const [hasMore, setHasMore] = useState(true);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);

  const { data, isLoading, isFetching, error } = useQuery<NotificationsResponse>({
    queryKey: ['notifications', selectedOrganizationId, cursor, unreadOnly],
    queryFn: () => listNotifications(apiClient, selectedOrganizationId!, cursor || undefined, unreadOnly),
    enabled: !!selectedOrganizationId,
    staleTime: 30000,
    refetchInterval: 30000,
    throwOnError: false,
    retry: false,
  });

  const unreadCountQuery = useQuery<NotificationsResponse, Error, number>({
    queryKey: ['notifications', selectedOrganizationId, null, true],
    queryFn: () => listNotifications(apiClient, selectedOrganizationId!, undefined, true),
    enabled: !!selectedOrganizationId,
    refetchInterval: 30000,
    select: (data) => data.notifications.filter(n => !n.read_at).length,
    throwOnError: false,
    retry: false,
  });

  useEffect(() => {
    if (typeof unreadCountQuery.data === 'number') {
      setUnreadCount(unreadCountQuery.data);
    }
  }, [unreadCountQuery.data]);

  const markAsReadMutation = useMutation({
    mutationFn: (id: string) => markNotificationAsRead(apiClient, selectedOrganizationId!, id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] });
    },
  });

  const markAllAsReadMutation = useMutation({
    mutationFn: () => markAllNotificationsAsRead(apiClient, selectedOrganizationId!),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] });
      setUnreadCount(0);
    },
  });

  useEffect(() => {
    if (data) {
      if (cursor === null) {
        setNotifications(data.notifications);
      } else {
        setNotifications(prev => [...prev, ...data.notifications]);
      }
      setHasMore(!!data.next_cursor);
    }
  }, [data, cursor]);

  const handleLoadMore = () => {
    if (data?.next_cursor) {
      setCursor(data.next_cursor);
    }
  };

  const handleMarkAsRead = (id: string) => {
    markAsReadMutation.mutate(id);
  };

  const handleMarkAllAsRead = () => {
    markAllAsReadMutation.mutate();
  };

  const translate = (key: string, params?: Record<string, unknown>) => {
    if (params) {
      return t(key, params as Record<string, string>);
    }
    return t(key);
  };

  if (!selectedOrganizationId) {
    return <div style={styles.empty}>{t('notifications.noOrganization')}</div>;
  }

  if (error) {
    return (
      <div style={styles.error}>
        {t('notifications.bellError')}
      </div>
    );
  }

  return (
    <div style={styles.container}>
      <header style={styles.header}>
        <h1 style={styles.title}>{t('notifications.title')}</h1>
        <div style={styles.headerActions}>
          <label style={styles.filterLabel}>
            <input
              type="checkbox"
              checked={unreadOnly}
              onChange={(e) => {
                setUnreadOnly(e.target.checked);
                setCursor(null);
                setNotifications([]);
              }}
              style={styles.checkbox}
            />
            {t('notifications.unreadOnly')}
          </label>
          <Link to="/notifications/preferences" style={styles.preferencesLink}>
            {t('notifications.preferences')}
          </Link>
          {unreadCount > 0 && (
            <button onClick={handleMarkAllAsRead} disabled={markAllAsReadMutation.isPending} style={styles.markAllButton}>
              {markAllAsReadMutation.isPending ? t('common.loading') : t('notifications.markAllRead')}
            </button>
          )}
        </div>
      </header>

      {isLoading ? (
        <div style={styles.loading}>{t('common.loading')}</div>
      ) : notifications.length === 0 ? (
        <div style={styles.empty}>{t('notifications.empty')}</div>
      ) : (
        <>
          <ul style={styles.list}>
            {notifications.map(notification => (
              <li key={notification.id} style={{ ...styles.listItem, opacity: notification.read_at ? 0.6 : 1 }}>
                <article style={styles.notification}>
                  <header style={styles.notificationHeader}>
                    <h2 style={styles.notificationTitle}>
                      {translate(notification.title_key, notification.title_params as Record<string, string> | undefined)}
                    </h2>
                    <time style={styles.notificationTime} dateTime={notification.created_at}>
                      {new Date(notification.created_at).toLocaleString()}
                    </time>
                  </header>
                  <div style={styles.notificationBody}>
                    {translate(notification.body_key, notification.body_params as Record<string, string> | undefined)}
                  </div>
                  {!notification.read_at && (
                    <button
                      onClick={() => handleMarkAsRead(notification.id)}
                      disabled={markAsReadMutation.isPending}
                      style={styles.markReadButton}
                    >
                      {t('notifications.markRead')}
                    </button>
                  )}
                </article>
              </li>
            ))}
          </ul>
          {hasMore && (
            <div style={styles.loadMore}>
              <button onClick={handleLoadMore} disabled={isFetching} style={styles.loadMoreButton}>
                {isFetching ? t('common.loading') : t('notifications.loadMore')}
              </button>
            </div>
          )}
        </>
      )}
    </div>
  );
}

const styles: Record<string, React.CSSProperties> = {
  container: {
    maxWidth: '800px',
    margin: '0 auto',
  },
  header: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: '1.5rem',
    flexWrap: 'wrap',
    gap: '1rem',
  },
  title: {
    margin: 0,
    color: '#333',
  },
  headerActions: {
    display: 'flex',
    alignItems: 'center',
    gap: '1rem',
    flexWrap: 'wrap',
  },
  filterLabel: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.5rem',
    fontSize: '0.875rem',
    color: '#555',
    cursor: 'pointer',
  },
  checkbox: {
    width: '1rem',
    height: '1rem',
  },
  markAllButton: {
    padding: '0.5rem 1rem',
    backgroundColor: '#1a73e8',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
  },
  loading: {
    textAlign: 'center',
    padding: '3rem',
    color: '#666',
  },
  empty: {
    textAlign: 'center',
    padding: '3rem',
    color: '#999',
  },
  list: {
    listStyle: 'none',
    padding: 0,
    margin: 0,
  },
  listItem: {
    marginBottom: '1rem',
  },
  notification: {
    backgroundColor: 'white',
    padding: '1.25rem',
    borderRadius: '8px',
    boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    borderLeft: '4px solid #1a73e8',
  },
  notificationHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: '0.75rem',
    gap: '1rem',
  },
  notificationTitle: {
    margin: 0,
    fontSize: '1rem',
    fontWeight: 600,
    color: '#333',
    flex: 1,
  },
  notificationTime: {
    fontSize: '0.75rem',
    color: '#999',
    whiteSpace: 'nowrap',
    flexShrink: 0,
  },
  notificationBody: {
    color: '#555',
    lineHeight: 1.5,
    marginBottom: '0.75rem',
  },
  markReadButton: {
    padding: '0.375rem 0.75rem',
    backgroundColor: '#28a745',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.8125rem',
  },
  loadMore: {
    textAlign: 'center',
    marginTop: '1.5rem',
  },
  loadMoreButton: {
    padding: '0.5rem 1.5rem',
    backgroundColor: '#f5f5f5',
    color: '#333',
    border: '1px solid #ddd',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
  },
  preferencesLink: {
    padding: '0.5rem 1rem',
    backgroundColor: '#6c757d',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
    textDecoration: 'none',
  },
  noPermission: {
    textAlign: 'center',
    padding: '3rem',
    color: '#999',
  },
  error: {
    textAlign: 'center',
    padding: '3rem',
    color: '#dc3545',
  },
};