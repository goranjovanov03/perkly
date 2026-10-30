import { useTranslation } from 'react-i18next';
import i18n from '../lib/i18n';

export function LanguageSwitcher() {
  const { t } = useTranslation();

  const changeLanguage = (lng: string) => {
    i18n.changeLanguage(lng);
    localStorage.setItem('language', lng);
  };

  return (
    <div>
      <label htmlFor="language-select">{t('language.switch')}:</label>
      <select
        id="language-select"
        value={i18n.language}
        onChange={(e) => changeLanguage(e.target.value)}
      >
        <option value="mk">{t('language.mk')}</option>
        <option value="en">{t('language.en')}</option>
      </select>
    </div>
  );
}