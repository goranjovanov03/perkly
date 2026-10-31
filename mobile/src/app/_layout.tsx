import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { DarkTheme, DefaultTheme, ThemeProvider } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { useColorScheme } from 'react-native';
import { useEffect, useState } from 'react';
import { Stack } from 'expo-router';

import { AnimatedSplashOverlay } from '@/components/animated-icon';
import i18n, { loadSavedLanguage } from '@/lib/i18n';
import { getToken, getOrgId, getMe, clearAuth } from '@/lib/api';

SplashScreen.preventAutoHideAsync();

const queryClient = new QueryClient();

export default function RootLayout() {
  const colorScheme = useColorScheme();
  const [isReady, setIsReady] = useState(false);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    loadSavedLanguage();
    checkAuth();
  }, []);

  const checkAuth = async () => {
    const token = await getToken();
    const orgId = await getOrgId();
    if (token && orgId) {
      try {
        await getMe();
        setIsAuthenticated(true);
      } catch (err: unknown) {
        if (err && typeof err === 'object' && 'error' in err) {
          const apiError = err as { error: { code?: string } };
          if (apiError.error?.code === 'UNAUTHENTICATED') {
            await clearAuth();
            setIsAuthenticated(false);
          } else {
            setIsAuthenticated(false);
          }
        } else {
          setIsAuthenticated(false);
        }
      }
    } else {
      setIsAuthenticated(false);
    }
    setIsReady(true);
  };

  if (!isReady) {
    return (
      <QueryClientProvider client={queryClient}>
        <ThemeProvider value={colorScheme === 'dark' ? DarkTheme : DefaultTheme}>
          <AnimatedSplashOverlay />
        </ThemeProvider>
      </QueryClientProvider>
    );
  }

  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider value={colorScheme === 'dark' ? DarkTheme : DefaultTheme}>
        <AnimatedSplashOverlay />
        <Stack screenOptions={{ headerShown: false }}>
          {isAuthenticated ? (
            <>
              <Stack.Screen name="(tabs)" options={{ title: 'Home' }} />
              <Stack.Screen name="select-org" options={{ title: 'Select Organization' }} />
            </>
          ) : (
            <>
              <Stack.Screen name="login" options={{ title: 'Login' }} />
            </>
          )}
        </Stack>
      </ThemeProvider>
    </QueryClientProvider>
  );
}