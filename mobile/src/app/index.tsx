import { View, Text, ActivityIndicator, StyleSheet } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { apiClient } from '@/lib/api';

export default function HealthScreen() {
  const { t } = useTranslation();

  const { data, error, isLoading } = useQuery({
    queryKey: ['health'],
    queryFn: async () => {
      const response = await apiClient.GET('/v1/health');
      if (response.error) {
        throw new Error(JSON.stringify(response.error));
      }
      return response.data;
    },
  });

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea}>
        <LanguageSwitcher />
        <ThemedText type="title" style={styles.title}>
          {t('health.title')}
        </ThemedText>
        <View style={styles.statusContainer}>
          <ThemedText type="default" style={styles.statusLabel}>
            {t('health.status')}: 
          </ThemedText>
          {isLoading && (
            <View style={styles.loadingContainer}>
              <ActivityIndicator size="large" />
              <ThemedText type="default" style={styles.loadingText}>
                {t('health.loading')}
              </ThemedText>
            </View>
          )}
          {data && (
            <ThemedText type="default" style={[styles.statusValue, styles.okText]}>
              {t('health.ok')}: {JSON.stringify(data)}
            </ThemedText>
          )}
          {error && (
            <ThemedText type="default" style={[styles.statusValue, styles.errorText]}>
              {t('health.error')}: {error.message}
            </ThemedText>
          )}
        </View>
      </SafeAreaView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
  },
  safeArea: {
    flex: 1,
    padding: 20,
    alignItems: 'center',
    gap: 20,
  },
  title: {
    textAlign: 'center',
    fontSize: 24,
  },
  statusContainer: {
    alignItems: 'center',
    gap: 10,
  },
  statusLabel: {
    fontWeight: 'bold',
  },
  statusValue: {
    fontSize: 16,
  },
  okText: {
    color: 'green',
  },
  errorText: {
    color: 'red',
  },
  loadingContainer: {
    alignItems: 'center',
    gap: 10,
  },
  loadingText: {
    marginTop: 5,
  },
});