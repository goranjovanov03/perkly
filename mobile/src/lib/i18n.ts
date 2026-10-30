import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import * as SecureStore from 'expo-secure-store';
import mk from '../locales/mk.json';
import en from '../locales/en.json';

const STORAGE_KEY = 'language';

i18n.use(initReactI18next).init({
  resources: {
    mk: { translation: mk },
    en: { translation: en },
  },
  lng: 'mk',
  fallbackLng: 'mk',
  interpolation: {
    escapeValue: false,
  },
  react: {
    useSuspense: false,
  },
});

export async function loadSavedLanguage() {
  const savedLang = await SecureStore.getItemAsync(STORAGE_KEY);
  if (savedLang) {
    await i18n.changeLanguage(savedLang);
  }
}

export async function changeLanguage(lng: string) {
  await i18n.changeLanguage(lng);
  await SecureStore.setItemAsync(STORAGE_KEY, lng);
}

export default i18n;