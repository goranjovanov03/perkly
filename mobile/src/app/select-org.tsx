import { View, Text, FlatList, StyleSheet, Pressable } from 'react-native';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { getOrganizations, saveOrgId } from '@/lib/api';

import React, { useState, useEffect } from 'react';

type Organization = { id: string; name: string; slug: string };

export default function SelectOrgScreen() {
  const { t } = useTranslation();
  const router = useRouter();
  const [organizations, setOrganizations] = useState<Organization[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadOrganizations();
  }, []);

  const loadOrganizations = async () => {
    try {
      const response = await getOrganizations();
      setOrganizations(response.organizations);
    } catch (err: unknown) {
      if (err && typeof err === 'object' && 'error' in err) {
        const apiError = err as { error: { message?: string } };
        setError(apiError.error?.message || t('auth.loginError'));
      } else {
        setError(t('auth.networkError'));
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleSelectOrg = async (orgId: string) => {
    await saveOrgId(orgId);
    router.replace('/(tabs)');
  };

  const renderOrgItem = ({ item }: { item: Organization }) => (
    <Pressable style={styles.orgItem} onPress={() => handleSelectOrg(item.id)}>
      <ThemedText type="default" style={styles.orgName}>
        {item.name}
      </ThemedText>
      <ThemedText type="default" style={styles.orgSlug}>
        {item.slug}
      </ThemedText>
    </Pressable>
  );

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea}>
        <ThemedText type="title" style={styles.title}>
          {t('auth.selectOrganization')}
        </ThemedText>

        {error && (
          <ThemedText type="default" style={styles.errorText}>
            {error}
          </ThemedText>
        )}

        {isLoading ? (
          <View style={styles.loadingContainer}>
            <Text>{t('health.loading')}</Text>
          </View>
        ) : (
          <FlatList
            data={organizations}
            keyExtractor={(item) => item.id}
            renderItem={renderOrgItem}
            contentContainerStyle={styles.listContent}
          />
        )}
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
    gap: 20,
  },
  title: {
    textAlign: 'center',
    fontSize: 28,
    marginBottom: 10,
  },
  errorText: {
    color: 'red',
    textAlign: 'center',
    marginBottom: 10,
  },
  loadingContainer: {
    alignItems: 'center',
    padding: 20,
  },
  listContent: {
    gap: 10,
  },
  orgItem: {
    borderWidth: 1,
    borderRadius: 8,
    padding: 16,
    gap: 4,
  },
  orgName: {
    fontSize: 18,
    fontWeight: '600',
  },
  orgSlug: {
    fontSize: 14,
    opacity: 0.7,
  },
});