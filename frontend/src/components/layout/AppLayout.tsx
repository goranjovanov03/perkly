import { Outlet } from 'react-router-dom';
import { Header } from './Header';
import { SideNavigation } from './SideNavigation';
import { useMediaQuery } from '../../hooks/useMediaQuery';
import { useState } from 'react';

export function AppLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const isMobile = useMediaQuery('(max-width: 768px)');

  return (
    <div style={styles.container}>
      <Header />
      <div style={styles.mainContainer}>
        {isMobile && (
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            style={styles.mobileMenuButton}
            aria-expanded={sidebarOpen}
            aria-controls="sidebar"
            aria-label="Toggle navigation menu"
          >
            ☰
          </button>
        )}
        <aside
          id="sidebar"
          style={{
            ...styles.sidebar,
            ...(isMobile && {
              ...styles.sidebarMobile,
              transform: sidebarOpen ? 'translateX(0)' : 'translateX(-100%)',
            }),
          }}
        >
          <SideNavigation />
        </aside>
        {isMobile && sidebarOpen && (
          <div
            onClick={() => setSidebarOpen(false)}
            style={styles.backdrop}
            aria-hidden="true"
          />
        )}
        <main style={styles.main}>
          <Outlet />
        </main>
      </div>
    </div>
  );
}

const styles: Record<string, React.CSSProperties> = {
  container: {
    minHeight: '100vh',
    display: 'flex',
    flexDirection: 'column',
  },
  mainContainer: {
    display: 'flex',
    flex: 1,
    position: 'relative',
  },
  mobileMenuButton: {
    display: 'none',
    position: 'fixed',
    top: '1rem',
    left: '1rem',
    zIndex: 200,
    padding: '0.5rem',
    backgroundColor: 'white',
    border: '1px solid #ddd',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '1.25rem',
    boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
  },
  sidebar: {
    width: '240px',
    backgroundColor: '#fafafa',
    borderRight: '1px solid #eee',
    flexShrink: 0,
    height: 'calc(100vh - 60px)',
    position: 'sticky',
    top: '60px',
    overflowY: 'auto',
  },
  sidebarMobile: {
    position: 'fixed',
    top: '60px',
    left: 0,
    bottom: 0,
    zIndex: 150,
    transition: 'transform 0.3s ease',
    boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
  },
  backdrop: {
    position: 'fixed',
    top: '60px',
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0,0,0,0.3)',
    zIndex: 140,
  },
  main: {
    flex: 1,
    padding: '1.5rem',
    minWidth: 0,
    backgroundColor: '#f5f5f5',
  },
};