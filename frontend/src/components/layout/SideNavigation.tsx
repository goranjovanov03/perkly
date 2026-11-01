import { NavLink } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';

interface NavItem {
  path: string;
  labelKey: string;
  capability?: string;
}

const navItems: NavItem[] = [
  { path: '/', labelKey: 'nav.home', capability: 'organization.view' },
  { path: '/notifications', labelKey: 'nav.notifications', capability: 'notifications.view' },
  { path: '/integrations', labelKey: 'nav.integrations', capability: 'integrations.manage' },
];

export function SideNavigation() {
  const { t } = useTranslation();
  const { capabilities } = useAuth();

  const filteredItems = navItems.filter(item => !item.capability || capabilities.includes(item.capability));

  return (
    <nav style={styles.nav} aria-label={t('nav.main')}>
      <ul style={styles.list}>
        {filteredItems.map(item => (
          <li key={item.path} style={styles.listItem}>
            <NavLink
              to={item.path}
              style={({ isActive }) => ({
                ...styles.link,
                ...(isActive ? styles.linkActive : {}),
              })}
            >
              {t(item.labelKey)}
            </NavLink>
          </li>
        ))}
      </ul>
    </nav>
  );
}

const styles: Record<string, React.CSSProperties> = {
  nav: {
    padding: '1rem 0',
    flexShrink: 0,
  },
  list: {
    listStyle: 'none',
    padding: 0,
    margin: 0,
  },
  listItem: {
    marginBottom: '0.25rem',
  },
  link: {
    display: 'block',
    padding: '0.75rem 1.5rem',
    color: '#555',
    textDecoration: 'none',
    fontSize: '0.9375rem',
    borderLeft: '3px solid transparent',
    transition: 'background-color 0.2s, color 0.2s',
  },
  linkActive: {
    backgroundColor: '#e8f0fe',
    color: '#1a73e8',
    borderLeftColor: '#1a73e8',
    fontWeight: 500,
  },
};