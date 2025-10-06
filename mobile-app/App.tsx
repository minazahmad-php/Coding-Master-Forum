import React, { useEffect, useState } from 'react';
import { StatusBar, Platform } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { Provider as PaperProvider } from 'react-native-paper';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import SplashScreen from 'react-native-splash-screen';
import PushNotification from 'react-native-push-notification';
import { QueryClient, QueryClientProvider } from 'react-query';
import { useAuthStore } from './src/stores/authStore';
import { useThemeStore } from './src/stores/themeStore';
import { theme } from './src/theme/theme';
import { darkTheme } from './src/theme/darkTheme';
import AuthNavigator from './src/navigation/AuthNavigator';
import MainNavigator from './src/navigation/MainNavigator';
import LoadingScreen from './src/screens/LoadingScreen';
import { initializeApp } from './src/services/appService';
import { setupPushNotifications } from './src/services/notificationService';

const Stack = createStackNavigator();
const queryClient = new QueryClient();

const App: React.FC = () => {
  const [isLoading, setIsLoading] = useState(true);
  const { isAuthenticated, initializeAuth } = useAuthStore();
  const { isDarkMode } = useThemeStore();

  useEffect(() => {
    const initialize = async () => {
      try {
        await initializeApp();
        await initializeAuth();
        setupPushNotifications();
        SplashScreen.hide();
      } catch (error) {
        console.error('App initialization error:', error);
      } finally {
        setIsLoading(false);
      }
    };

    initialize();
  }, []);

  if (isLoading) {
    return <LoadingScreen />;
  }

  const currentTheme = isDarkMode ? darkTheme : theme;

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <QueryClientProvider client={queryClient}>
        <PaperProvider theme={currentTheme}>
          <StatusBar
            barStyle={isDarkMode ? 'light-content' : 'dark-content'}
            backgroundColor={currentTheme.colors.primary}
          />
          <NavigationContainer>
            <Stack.Navigator screenOptions={{ headerShown: false }}>
              {isAuthenticated ? (
                <Stack.Screen name="Main" component={MainNavigator} />
              ) : (
                <Stack.Screen name="Auth" component={AuthNavigator} />
              )}
            </Stack.Navigator>
          </NavigationContainer>
        </PaperProvider>
      </QueryClientProvider>
    </GestureHandlerRootView>
  );
};

export default App;