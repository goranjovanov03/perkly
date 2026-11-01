import { useTranslation } from 'react-i18next';
import i18n from '../lib/i18n';
import { useAuth } from '../context/AuthContext';
import { updateLocale } from '../services/auth';
import { apiClient } from '../lib/api';

export function LanguageSwitcher() {
  const { t } = useTranslation();
  const { user } = useAuth();

  const changeLanguage = async (lng: string) => {
    if (user) {
      await updateLocale(apiClient, lng as 'mk' | 'en');
    }
    i18n.changeLanguage(lng);
    localStorage.setItem('language', lng);
  };

  return (
    <div style={styles.container}>
      <label htmlFor="language-select" style={styles.label}>
        {t('language.switch')}:
      </label>
      <select
        id="language-select"
        value={i18n.language}
        onChange={(e) => changeLanguage(e.target.value)}
        style={styles.select}
      >
        <option value="mk">{t('language.mk')}</option>
        <option value="en">{t('language.en')}</option>
      </select>
    </div>
  );
}

const styles: Record<string, React.CSSProperties> = {
  container: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.5rem',
  },
  label: {
    fontSize: '0.875rem',
    color: '#555',
    whiteSpace: 'nowrap',
  },
  select: {
    padding: '0.375rem 0.75rem',
    border: '1px solid #ddd',
    borderRadius: '4px',
    backgroundColor: 'white',
    fontSize: '0.875rem',
    cursor: 'pointer',
  },
};