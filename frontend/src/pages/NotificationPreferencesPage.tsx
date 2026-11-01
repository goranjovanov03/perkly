import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../lib/api';
import { getNotificationPreferences, updateNotificationPreferences } from '../services/auth';
import { useState } from 'react';

interface PreferencesResponse {
  preferences: Record<string, Record<string, boolean>>;
}

type Channel = 'in_app' | 'push' | 'email';

const channels: Channel[] = ['in_app', 'push', 'email'];

export function NotificationPreferencesPage() {
  const { t } = useTranslation();
  const { capabilities } = useAuth();
  const queryClient = useQueryClient();

  if (!capabilities.includes('notifications.preferences')) {
    return <div style={styles.noPermission}>{t('notifications.noPermission')}</div>;
  }
  const [expandedTypes, setExpandedTypes] = useState<Set<string>>(new Set());
  const [pendingChanges, setPendingChanges] = useState<Record<string, Record<string, boolean>>>({});

  const { data, isLoading } = useQuery<PreferencesResponse>({
    queryKey: ['notification-preferences'],
    queryFn: () => getNotificationPreferences(apiClient),
    staleTime: 60000,
  });

  const updateMutation = useMutation({
    mutationFn: (preferences: Array<{ channel: Channel; type: string; enabled: boolean }>) => 
      updateNotificationPreferences(apiClient, preferences),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notification-preferences'] });
      setPendingChanges({});
    },
  });

  const preferences = data?.preferences || {};
  const allTypes = new Set<string>();
  channels.forEach(channel => {
    Object.keys(preferences[channel] || {}).forEach(type => allTypes.add(type));
  });

  const getCurrentValue = (channel: Channel, type: string): boolean => {
    if (pendingChanges[channel]?.[type] !== undefined) {
      return pendingChanges[channel][type];
    }
    return preferences[channel]?.[type] ?? false;
  };

  const handleToggle = (channel: Channel, type: string, checked: boolean) => {
    setPendingChanges(prev => ({
      ...prev,
      [channel]: {
        ...prev[channel],
        [type]: checked,
      },
    }));
  };

  const handleSave = () => {
    const preferenceArray: Array<{ channel: Channel; type: string; enabled: boolean }> = [];
    Object.entries(pendingChanges).forEach(([channel, types]) => {
      Object.entries(types).forEach(([type, enabled]) => {
        preferenceArray.push({ channel: channel as Channel, type, enabled });
      });
    });
    if (preferenceArray.length > 0) {
      updateMutation.mutate(preferenceArray);
    }
  };

  const handleExpand = (type: string) => {
    setExpandedTypes(prev => {
      const next = new Set(prev);
      if (next.has(type)) {
        next.delete(type);
      } else {
        next.add(type);
      }
      return next;
    });
  };

  if (isLoading) {
    return <div style={styles.loading}>{t('common.loading')}</div>;
  }

  return (
    <div style={styles.container}>
      <h1 style={styles.title}>{t('notifications.preferencesTitle')}</h1>
      <p style={styles.description}>{t('notifications.preferencesDescription')}</p>

      {Object.keys(allTypes).length === 0 ? (
        <div style={styles.empty}>{t('notifications.preferencesEmpty')}</div>
      ) : (
        <>
          <div style={styles.tableContainer}>
            <table style={styles.table}>
              <thead>
                <tr style={styles.headerRow}>
                  <th style={styles.th}>{t('notifications.type')}</th>
                  {channels.map(channel => (
                    <th key={channel} style={styles.th}>
                      {t(`notifications.channels.${channel}`)}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {Array.from(allTypes).sort().flatMap(type => [
                  <tr key={type} style={styles.row}>
                    <td style={styles.td}>
                      <button
                        onClick={() => handleExpand(type)}
                        style={styles.typeButton}
                        aria-expanded={expandedTypes.has(type)}
                      >
                        <span style={styles.typeName}>{type}</span>
                        <span style={styles.expandIcon}>
                          {expandedTypes.has(type) ? '▼' : '▶'}
                        </span>
                      </button>
                    </td>
                    {channels.map(channel => (
                      <td key={channel} style={styles.td}>
                        <label style={styles.toggleLabel}>
                          <input
                            type="checkbox"
                            checked={getCurrentValue(channel, type)}
                            onChange={(e) => handleToggle(channel, type, e.target.checked)}
                            style={styles.checkbox}
                          />
                          <span style={styles.toggleSlider}></span>
                        </label>
                      </td>
                    ))}
                  </tr>,
                  expandedTypes.has(type) ? (
                    <tr key={`${type}-detail`} style={styles.detailRow}>
                      <td colSpan={4} style={styles.detailCell}>
                        <div style={styles.detailContent}>
                          <p>{t('notifications.preferencesDetail', { type })}</p>
                          {channels.map(channel => (
                            <div key={channel} style={styles.channelDetail}>
                              <strong>{t(`notifications.channels.${channel}`)}:</strong>
                              <span>{getCurrentValue(channel, type) ? t('common.enabled') : t('common.disabled')}</span>
                            </div>
                          ))}
                        </div>
                      </td>
                    </tr>
                  ) : null,
                ])}
              </tbody>
            </table>
          </div>

          {Object.keys(pendingChanges).length > 0 && (
            <div style={styles.saveBar}>
              <button onClick={handleSave} disabled={updateMutation.isPending} style={styles.saveButton}>
                {updateMutation.isPending ? t('common.loading') : t('common.save')}
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
    maxWidth: '900px',
    margin: '0 auto',
  },
  title: {
    margin: '0 0 0.5rem',
    color: '#333',
  },
  description: {
    margin: '0 0 1.5rem',
    color: '#666',
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
  tableContainer: {
    overflowX: 'auto',
    borderRadius: '8px',
    boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    backgroundColor: 'white',
  },
  table: {
    width: '100%',
    borderCollapse: 'collapse',
    minWidth: '600px',
  },
  headerRow: {
    backgroundColor: '#f8f9fa',
  },
  th: {
    padding: '0.75rem 1rem',
    textAlign: 'left',
    fontWeight: 600,
    color: '#333',
    borderBottom: '2px solid #dee2e6',
    whiteSpace: 'nowrap',
  },
  td: {
    padding: '0.75rem 1rem',
    borderBottom: '1px solid #eee',
    verticalAlign: 'middle',
  },
  row: {
    transition: 'background-color 0.1s',
  },
  typeButton: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.5rem',
    background: 'none',
    border: 'none',
    cursor: 'pointer',
    padding: 0,
    font: 'inherit',
    color: '#333',
    textAlign: 'left',
  },
  typeName: {
    fontFamily: 'monospace',
    fontSize: '0.875rem',
  },
  expandIcon: {
    fontSize: '0.75rem',
    color: '#999',
    transition: 'transform 0.2s',
  },
  detailRow: {
    backgroundColor: '#f8f9fa',
  },
  detailCell: {
    padding: 0,
  },
  detailContent: {
    padding: '1rem',
    borderTop: '1px solid #eee',
  },
  channelDetail: {
    display: 'flex',
    justifyContent: 'space-between',
    padding: '0.25rem 0',
    fontSize: '0.8125rem',
  },
  toggleLabel: {
    position: 'relative',
    display: 'inline-flex',
    alignItems: 'center',
    cursor: 'pointer',
  },
  checkbox: {
    position: 'absolute',
    opacity: 0,
    width: 0,
    height: 0,
  },
  toggleSlider: {
    position: 'relative',
    width: '44px',
    height: '24px',
    backgroundColor: '#ccc',
    borderRadius: '12px',
    transition: 'background-color 0.2s',
  },
  saveBar: {
    position: 'sticky',
    bottom: 0,
    marginTop: '1.5rem',
    padding: '1rem',
    backgroundColor: 'white',
    borderTop: '1px solid #eee',
    borderRadius: '0 0 8px 8px',
    boxShadow: '0 -2px 8px rgba(0,0,0,0.05)',
  },
  saveButton: {
    padding: '0.625rem 1.5rem',
    backgroundColor: '#1a73e8',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
    fontWeight: 500,
  },
  noPermission: {
    textAlign: 'center',
    padding: '3rem',
    color: '#999',
  },
};