import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../lib/api';
import { listIntegrations, createIntegration, testIntegration, updateIntegrationSecrets } from '@perkly/api-client';
import { useState } from 'react';

interface Integration {
  id: string;
  type: string;
  name: string;
  config: Record<string, unknown>;
  status: 'active' | 'inactive' | 'error';
  last_health_check_at: string | null;
  last_health_check_result: Record<string, unknown> | null;
  created_at: string;
  updated_at: string;
}

interface IntegrationsResponse {
  integrations: Integration[];
}

export function IntegrationsPage() {
  const { t } = useTranslation();
  const { selectedOrganizationId, capabilities } = useAuth();
  const queryClient = useQueryClient();
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showSecretsModal, setShowSecretsModal] = useState<Integration | null>(null);
  const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);

  const hasManageCapability = capabilities.includes('integrations.manage');

  const { data, isLoading, error } = useQuery<IntegrationsResponse>({
    queryKey: ['integrations', selectedOrganizationId],
    queryFn: () => listIntegrations(apiClient, selectedOrganizationId!),
    enabled: !!selectedOrganizationId,
    throwOnError: false,
    retry: false,
  });

  if (error) {
    return <div style={styles.error}>{t('integrations.loadError')}</div>;
  }

  const createMutation = useMutation({
    mutationFn: (body: { type: string; name: string; config: Record<string, unknown> }) => 
      createIntegration(apiClient, selectedOrganizationId!, body),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['integrations'] });
      setShowCreateModal(false);
    },
  });

  const testMutation = useMutation({
    mutationFn: (id: string) => testIntegration(apiClient, selectedOrganizationId!, id),
    onSuccess: (result) => {
      setTestResult({ success: result.success, message: result.message });
      queryClient.invalidateQueries({ queryKey: ['integrations'] });
    },
  });

  const secretsMutation = useMutation({
    mutationFn: ({ id, secrets }: { id: string; secrets: Record<string, string> }) => 
      updateIntegrationSecrets(apiClient, selectedOrganizationId!, id, secrets),
    onSuccess: () => {
      setShowSecretsModal(null);
      queryClient.invalidateQueries({ queryKey: ['integrations'] });
    },
  });

  const [createForm, setCreateForm] = useState({ type: '', name: '', config: {} as Record<string, unknown> });
  const [secretsForm, setSecretsForm] = useState<Record<string, string>>({});

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate(createForm);
  };

  const handleSecretsSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (showSecretsModal) {
      secretsMutation.mutate({ id: showSecretsModal.id, secrets: secretsForm });
    }
  };

  const handleTest = (id: string) => {
    testMutation.mutate(id);
  };

  const handleOpenSecrets = (integration: Integration) => {
    setShowSecretsModal(integration);
    setSecretsForm({});
  };

  if (!selectedOrganizationId) {
    return <div style={styles.empty}>{t('integrations.noOrganization')}</div>;
  }

  if (!hasManageCapability) {
    return <div style={styles.empty}>{t('integrations.noPermission')}</div>;
  }

  return (
    <div style={styles.container}>
      <header style={styles.header}>
        <h1 style={styles.title}>{t('integrations.title')}</h1>
        <button onClick={() => setShowCreateModal(true)} style={styles.createButton}>
          {t('integrations.create')}
        </button>
      </header>

      {isLoading ? (
        <div style={styles.loading}>{t('common.loading')}</div>
      ) : data?.integrations.length === 0 ? (
        <div style={styles.empty}>{t('integrations.empty')}</div>
      ) : (
        <div style={styles.grid}>
          {data?.integrations.map(integration => (
            <article key={integration.id} style={styles.card}>
              <header style={styles.cardHeader}>
                <div>
                  <h2 style={styles.cardTitle}>{integration.name}</h2>
                  <span style={styles.cardType}>{integration.type}</span>
                </div>
                <span style={{
                  ...styles.statusBadge,
                  backgroundColor: integration.status === 'active' ? '#d4edda' : integration.status === 'inactive' ? '#f8f9fa' : '#f8d7da',
                  color: integration.status === 'active' ? '#155724' : integration.status === 'inactive' ? '#6c757d' : '#721c24',
                }}>
                  {t(`integrations.status.${integration.status}`)}
                </span>
              </header>
              <div style={styles.cardMeta}>
                <div style={styles.metaItem}>
                  <label>{t('integrations.lastHealthCheck')}</label>
                  <span>{integration.last_health_check_at ? new Date(integration.last_health_check_at).toLocaleString() : t('integrations.never')}</span>
                </div>
                {integration.last_health_check_result && (
                  <div style={styles.metaItem}>
                    <label>{t('integrations.lastResult')}</label>
                    <span>{JSON.stringify(integration.last_health_check_result)}</span>
                  </div>
                )}
              </div>
              <footer style={styles.cardActions}>
                <button onClick={() => handleOpenSecrets(integration)} style={styles.secretsButton}>
                  {t('integrations.secrets')}
                </button>
                <button onClick={() => handleTest(integration.id)} disabled={testMutation.isPending} style={styles.testButton}>
                  {testMutation.isPending ? t('common.loading') : t('integrations.test')}
                </button>
              </footer>
            </article>
          ))}
        </div>
      )}

      {showCreateModal && (
        <Modal onClose={() => setShowCreateModal(false)} title={t('integrations.createTitle')}>
          <form onSubmit={handleCreateSubmit} style={styles.form}>
            <div style={styles.formGroup}>
              <label htmlFor="integration-type" style={styles.label}>{t('integrations.type')}</label>
              <input
                id="integration-type"
                type="text"
                value={createForm.type}
                onChange={(e) => setCreateForm({ ...createForm, type: e.target.value })}
                required
                style={styles.input}
              />
            </div>
            <div style={styles.formGroup}>
              <label htmlFor="integration-name" style={styles.label}>{t('integrations.name')}</label>
              <input
                id="integration-name"
                type="text"
                value={createForm.name}
                onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                required
                style={styles.input}
              />
            </div>
            <div style={styles.formGroup}>
              <label htmlFor="integration-config" style={styles.label}>{t('integrations.config')}</label>
              <textarea
                id="integration-config"
                value={JSON.stringify(createForm.config, null, 2)}
                onChange={(e) => {
                  try {
                    setCreateForm({ ...createForm, config: JSON.parse(e.target.value) });
                  } catch {
                    // ignore invalid JSON
                  }
                }}
                style={{ ...styles.input, minHeight: '100px', fontFamily: 'monospace' }}
                placeholder="{}"
              />
            </div>
            <div style={styles.formActions}>
              <button type="button" onClick={() => setShowCreateModal(false)} style={styles.cancelButton}>
                {t('common.cancel')}
              </button>
              <button type="submit" disabled={createMutation.isPending} style={styles.submitButton}>
                {createMutation.isPending ? t('common.loading') : t('common.save')}
              </button>
            </div>
          </form>
        </Modal>
      )}

      {showSecretsModal && (
        <Modal onClose={() => setShowSecretsModal(null)} title={t('integrations.secretsTitle', { name: showSecretsModal.name })}>
          <form onSubmit={handleSecretsSubmit} style={styles.form}>
            <p style={styles.secretsHint}>{t('integrations.secretsHint')}</p>
            <div style={styles.formGroup}>
              <label style={styles.label}>{t('integrations.secretKeys')}</label>
              <div style={styles.secretsFields}>
                <input
                  type="password"
                  placeholder={t('integrations.secretKeyPlaceholder')}
                  onChange={(e) => setSecretsForm({ ...secretsForm, [e.target.name]: e.target.value })}
                  name="key1"
                  style={styles.input}
                />
                <input
                  type="password"
                  placeholder={t('integrations.secretValuePlaceholder')}
                  onChange={(e) => setSecretsForm({ ...secretsForm, [e.target.name]: e.target.value })}
                  name="value1"
                  style={styles.input}
                />
              </div>
            </div>
            <div style={styles.formActions}>
              <button type="button" onClick={() => setShowSecretsModal(null)} style={styles.cancelButton}>
                {t('common.cancel')}
              </button>
              <button type="submit" disabled={secretsMutation.isPending} style={styles.submitButton}>
                {secretsMutation.isPending ? t('common.loading') : t('common.save')}
              </button>
            </div>
          </form>
        </Modal>
      )}

      {testResult && (
        <TestResultModal
          result={testResult}
          onClose={() => setTestResult(null)}
        />
      )}
    </div>
  );
}

function Modal({ isOpen = true, onClose, title, children }: { isOpen?: boolean; onClose: () => void; title: string; children: React.ReactNode }) {
  if (!isOpen) return null;
  return (
    <div style={modalStyles.overlay} onClick={onClose} role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <div style={modalStyles.modal} onClick={e => e.stopPropagation()}>
        <header style={modalStyles.header}>
          <h2 id="modal-title" style={modalStyles.title}>{title}</h2>
          <button onClick={onClose} style={modalStyles.closeButton} aria-label="Close">×</button>
        </header>
        <div style={modalStyles.content}>{children}</div>
      </div>
    </div>
  );
}

function TestResultModal({ result, onClose }: { result: { success: boolean; message: string }; onClose: () => void }) {
  const { t } = useTranslation();
  return (
    <Modal isOpen onClose={onClose} title={t('integrations.testResult')}>
      <div style={{
        padding: '1rem',
        backgroundColor: result.success ? '#d4edda' : '#f8d7da',
        borderRadius: '4px',
        color: result.success ? '#155724' : '#721c24',
      }}>
        {result.message}
      </div>
      <div style={modalStyles.formActions}>
        <button onClick={onClose} style={modalStyles.submitButton}>{t('common.close')}</button>
      </div>
    </Modal>
  );
}

const styles: Record<string, React.CSSProperties> = {
  container: {
    maxWidth: '1000px',
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
  createButton: {
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
  grid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))',
    gap: '1rem',
  },
  card: {
    backgroundColor: 'white',
    borderRadius: '8px',
    boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    overflow: 'hidden',
    display: 'flex',
    flexDirection: 'column',
  },
  cardHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    padding: '1rem',
    borderBottom: '1px solid #eee',
  },
  cardTitle: {
    margin: '0 0 0.25rem',
    fontSize: '1rem',
    fontWeight: 600,
    color: '#333',
  },
  cardType: {
    fontSize: '0.75rem',
    color: '#666',
    textTransform: 'uppercase',
  },
  statusBadge: {
    padding: '0.25rem 0.5rem',
    borderRadius: '9999px',
    fontSize: '0.75rem',
    fontWeight: 500,
    whiteSpace: 'nowrap',
  },
  cardMeta: {
    padding: '1rem',
    borderBottom: '1px solid #eee',
    fontSize: '0.8125rem',
  },
  metaItem: {
    marginBottom: '0.5rem',
  },
  cardActions: {
    display: 'flex',
    gap: '0.5rem',
    padding: '1rem',
    borderTop: '1px solid #eee',
  },
  secretsButton: {
    flex: 1,
    padding: '0.5rem',
    backgroundColor: '#f5f5f5',
    color: '#333',
    border: '1px solid #ddd',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.8125rem',
  },
  testButton: {
    flex: 1,
    padding: '0.5rem',
    backgroundColor: '#1a73e8',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.8125rem',
  },
  form: {
    display: 'flex',
    flexDirection: 'column',
    gap: '1rem',
  },
  formGroup: {
    display: 'flex',
    flexDirection: 'column',
    gap: '0.375rem',
  },
  label: {
    fontSize: '0.875rem',
    fontWeight: 500,
    color: '#333',
  },
  input: {
    padding: '0.5rem',
    border: '1px solid #ddd',
    borderRadius: '4px',
    fontSize: '0.875rem',
  },
  formActions: {
    display: 'flex',
    justifyContent: 'flex-end',
    gap: '0.5rem',
    marginTop: '0.5rem',
  },
  cancelButton: {
    padding: '0.5rem 1rem',
    backgroundColor: '#f5f5f5',
    color: '#333',
    border: '1px solid #ddd',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
  },
  submitButton: {
    padding: '0.5rem 1rem',
    backgroundColor: '#1a73e8',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.875rem',
  },
  secretsHint: {
    margin: 0,
    fontSize: '0.8125rem',
    color: '#666',
  },
  secretsFields: {
    display: 'flex',
    gap: '0.5rem',
  },
  error: {
    textAlign: 'center',
    padding: '3rem',
    color: '#dc3545',
  },
};

const modalStyles: Record<string, React.CSSProperties> = {
  overlay: {
    position: 'fixed',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0,0,0,0.5)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 1000,
    padding: '1rem',
  },
  modal: {
    backgroundColor: 'white',
    borderRadius: '8px',
    width: '100%',
    maxWidth: '480px',
    maxHeight: '90vh',
    overflowY: 'auto',
    boxShadow: '0 4px 20px rgba(0,0,0,0.15)',
  },
  header: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '1rem 1.5rem',
    borderBottom: '1px solid #eee',
  },
  title: {
    margin: 0,
    fontSize: '1.125rem',
    fontWeight: 600,
    color: '#333',
  },
  closeButton: {
    background: 'none',
    border: 'none',
    fontSize: '1.5rem',
    cursor: 'pointer',
    color: '#999',
    padding: 0,
    lineHeight: 1,
  },
  content: {
    padding: '1.5rem',
  },
};