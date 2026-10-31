import React, { useState, useEffect } from 'react';
import { View, Text, Button, StyleSheet, Alert, Pressable } from 'react-native';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { getMe, getOrgId, getOrganizations, saveOrgId, deleteToken, logoutAll, clearAuth } from '@/lib/api';

export default function HomeScreen() {
  const { t } = useTranslation();
  const router = useRouter();
  const [user, setUser] = useState<{ id: string; name: string; email: string } | null>(null);
  const [currentOrgId, setCurrentOrgId] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [showLogoutAll, setShowLogoutAll] = useState(false);
  const [currentOrg, setCurrentOrg] = useState<{ id: string; name: string; slug: string } | null>(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const [meResponse, orgId, orgsResponse] = await Promise.all([
        getMe(),
        getOrgId(),
        getOrganizations(),
      ]);
      setUser(meResponse.user);
      setCurrentOrgId(orgId);
      const org = orgsResponse.organizations.find((o) => o.id === orgId);
      if (org) {
        setCurrentOrg(org);
      }
    } catch (err: unknown) {
      if (err && typeof err === 'object' && 'error' in err) {
        const apiError = err as { error: { code?: string } };
        if (apiError.error?.code === 'UNAUTHENTICATED') {
          router.replace('/login');
        }
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleLogout = async () => {
    try {
      await deleteToken();
      await clearAuth();
      router.replace('/login');
    } catch {
      await clearAuth();
      router.replace('/login');
    }
  };

  const handleLogoutAll = async () => {
    try {
      await logoutAll();
      await clearAuth();
      router.replace('/login');
    } catch {
      await clearAuth();
      router.replace('/login');
    }
  };

  const handleSwitchOrg = () => {
    router.push('/select-org');
  };

  if (isLoading) {
    return (
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.safeArea}>
          <Text>{t('health.loading')}</Text>
        </SafeAreaView>
      </ThemedView>
    );
  }

  const showLogoutAllAlert = () => {
    Alert.alert(
      t('auth.logoutAll'),
      t('auth.confirmLogoutAll'),
      [
        { text: t('auth.cancel'), style: 'cancel' },
        { text: t('auth.logoutAll'), onPress: handleLogoutAll, style: 'destructive' },
      ],
      { onDismiss: () => setShowLogoutAll(false) }
    );
  };

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea}>
        <ThemedText type="title" style={styles.title}>
          {t('auth.welcome')}, {user?.name}
        </ThemedText>

        <View style={styles.infoContainer}>
          <ThemedText type="default" style={styles.label}>
            {t('auth.currentOrg')}: {currentOrg?.name}
          </ThemedText>
        </View>

        <View style={styles.buttonContainer}>
          <Pressable onPress={handleSwitchOrg} style={styles.button}>
            <ThemedText type="default" style={styles.buttonText}>
              {t('auth.switchOrg')}
            </ThemedText>
          </Pressable>
          <Pressable onPress={handleLogout} style={styles.dangerButton}>
            <ThemedText type="default" style={styles.dangerButtonText}>
              {t('auth.logout')}
            </ThemedText>
          </Pressable>
          <Pressable onPress={showLogoutAllAlert} style={styles.dangerButton}>
            <ThemedText type="default" style={styles.dangerButtonText}>
              {t('auth.logoutAll')}
            </ThemedText>
          </Pressable>
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
    gap: 20,
  },
  title: {
    textAlign: 'center',
    fontSize: 28,
    marginBottom: 10,
  },
  infoContainer: {
    padding: 16,
    borderWidth: 1,
    borderRadius: 8,
    gap: 8,
  },
  label: {
    fontSize: 16,
  },
  buttonContainer: {
    gap: 10,
    marginTop: 10,
  },
  button: {
    padding: 12,
    borderWidth: 1,
    borderRadius: 8,
    alignItems: 'center',
  },
  buttonText: {
    fontSize: 16,
  },
  dangerButton: {
    padding: 12,
    borderWidth: 1,
    borderColor: 'red',
    borderRadius: 8,
    alignItems: 'center',
  },
  dangerButtonText: {
    fontSize: 16,
    color: 'red',
  },
});