import { Component, type ReactNode, type ErrorInfo } from 'react';
import { useTranslation } from 'react-i18next';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false, error: null };

  static getDerivedStateFromError(error: unknown): State {
    return { hasError: true, error: error instanceof Error ? error : new Error(String(error)) };
  }

  componentDidCatch(error: unknown, errorInfo: ErrorInfo): void {
    console.error('ErrorBoundary caught:', error, errorInfo);
  }

  render(): ReactNode {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }
      return <ErrorFallback error={this.state.error} />;
    }
    return this.props.children;
  }
}

function ErrorFallback({ error }: { error: Error | null }) {
  const { t } = useTranslation();
  const reload = () => window.location.reload();

  return (
    <div style={styles.container}>
      <h1 style={styles.title}>{t('errorBoundary.title')}</h1>
      <p style={styles.message}>{t('errorBoundary.message')}</p>
      {error && (
        <details style={styles.details}>
          <summary>{t('errorBoundary.details')}</summary>
          <pre style={styles.pre}>{error.message}</pre>
        </details>
      )}
      <button onClick={reload} style={styles.button}>{t('errorBoundary.reload')}</button>
    </div>
  );
}

const styles: Record<string, React.CSSProperties> = {
  container: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: '100vh',
    padding: '2rem',
    textAlign: 'center',
    backgroundColor: '#f5f5f5',
  },
  title: {
    margin: '0 0 1rem',
    color: '#dc3545',
    fontSize: '1.5rem',
  },
  message: {
    margin: '0 0 1.5rem',
    color: '#555',
    maxWidth: '500px',
  },
  details: {
    margin: '1rem 0',
    padding: '1rem',
    backgroundColor: '#fff',
    border: '1px solid #ddd',
    borderRadius: '4px',
    textAlign: 'left',
    maxWidth: '600px',
    width: '100%',
  },
  pre: {
    margin: 0,
    fontSize: '0.75rem',
    whiteSpace: 'pre-wrap',
    wordBreak: 'break-word',
    color: '#333',
  },
  button: {
    marginTop: '1rem',
    padding: '0.75rem 1.5rem',
    backgroundColor: '#1a73e8',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '1rem',
  },
};