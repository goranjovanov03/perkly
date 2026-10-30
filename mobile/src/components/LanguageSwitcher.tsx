import { View } from 'react-native';
import { Picker } from '@react-native-picker/picker';
import { useTranslation } from 'react-i18next';
import { changeLanguage } from '@/lib/i18n';

export function LanguageSwitcher() {
  const { t, i18n } = useTranslation();

  return (
    <View>
      <Picker
        selectedValue={i18n.language}
        onValueChange={(value: string) => changeLanguage(value)}>
        <Picker.Item label={t('language.mk')} value="mk" />
        <Picker.Item label={t('language.en')} value="en" />
      </Picker>
    </View>
  );
}