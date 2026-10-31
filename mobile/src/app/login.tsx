import { View, Text, TextInput, Button, ActivityIndicator, StyleSheet, Pressable } from 'react-native';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { loginRequest, saveToken, getOrganizations, saveOrgId } from '@/lib/api';

export default function LoginScreen() {
  const { t } = useTranslation();
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]> | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  const handleLogin = async () => {
    setError(null);
    setFieldErrors(null);
    setIsLoading(true);

    try {
      const { token } = await loginRequest(email, password);
      await saveToken(token);
      const orgsResponse = await getOrganizations();
      const organizations = orgsResponse.organizations;
      if (organizations.length === 1) {
        await saveOrgId(organizations[0].id);
        router.replace('/(tabs)');
      } else {
        router.replace('/select-org');
      }
    } catch (err: unknown) {
      if (err && typeof err === 'object' && 'error' in err) {
        const apiError = err as { error: { code?: string; message?: string; fields?: Record<string, string[]> } };
        setError(apiError.error?.message || t('auth.loginError'));
        if (apiError.error?.fields) {
          setFieldErrors(apiError.error.fields);
        }
      } else {
        setError(t('auth.networkError'));
      }
    } finally {
      setIsLoading(false);
    }
  };

  const getFieldError = (field: string): string | undefined => {
    return fieldErrors?.[field]?.[0];
  };

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea}>
        <ThemedText type="title" style={styles.title}>
          {t('auth.login')}
        </ThemedText>

        {error && (
          <ThemedText type="default" style={styles.errorText}>
            {error}
          </ThemedText>
        )}

        <View style={styles.inputContainer}>
          <ThemedText type="default" style={styles.label}>
            {t('auth.email')}
          </ThemedText>
          <TextInput
            style={[styles.input, fieldErrors?.email && styles.inputError]}
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
            autoComplete="email"
            placeholder={t('auth.email')}
            editable={!isLoading}
          />
          {getFieldError('email') && (
            <ThemedText type="default" style={styles.fieldError}>
              {getFieldError('email')}
            </ThemedText>
          )}
        </View>

        <View style={styles.inputContainer}>
          <ThemedText type="default" style={styles.label}>
            {t('auth.password')}
          </ThemedText>
          <TextInput
            style={[styles.input, fieldErrors?.password && styles.inputError]}
            value={password}
            onChangeText={setPassword}
            secureTextEntry
            autoComplete="password"
            placeholder={t('auth.password')}
            editable={!isLoading}
          />
          {getFieldError('password') && (
            <ThemedText type="default" style={styles.fieldError}>
              {getFieldError('password')}
            </ThemedText>
          )}
        </View>

        <Button
          title={isLoading ? t('auth.loggingIn') : t('auth.login')}
          onPress={handleLogin}
          disabled={isLoading}
        />

        <Pressable onPress={handleLogin} disabled={isLoading} style={styles.retryButton}>
          <ThemedText type="default" style={styles.retryButtonText}>
            {t('auth.retry')}
          </ThemedText>
        </Pressable>
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
  inputContainer: {
    width: '100%',
    gap: 5,
  },
  label: {
    fontWeight: '600',
    marginBottom: 5,
  },
  input: {
    borderWidth: 1,
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
  },
  inputError: {
    borderColor: 'red',
  },
  fieldError: {
    color: 'red',
    fontSize: 13,
  },
  retryButton: {
    marginTop: 10,
    padding: 12,
    borderWidth: 1,
    borderRadius: 8,
    alignItems: 'center',
  },
  retryButtonText: {
    fontSize: 16,
  },
});