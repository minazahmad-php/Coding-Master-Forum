import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useThemeStore } from '../stores/themeStore';
import HomeScreen from '../screens/HomeScreen';
import ForumsScreen from '../screens/ForumsScreen';
import MessagesScreen from '../screens/MessagesScreen';
import ProfileScreen from '../screens/ProfileScreen';
import ThreadScreen from '../screens/ThreadScreen';
import CreateThreadScreen from '../screens/CreateThreadScreen';
import SearchScreen from '../screens/SearchScreen';
import SettingsScreen from '../screens/SettingsScreen';
import NotificationsScreen from '../screens/NotificationsScreen';
import ChatScreen from '../screens/ChatScreen';
import VideoCallScreen from '../screens/VideoCallScreen';
import AnalyticsScreen from '../screens/AnalyticsScreen';
import MarketplaceScreen from '../screens/MarketplaceScreen';
import AchievementsScreen from '../screens/AchievementsScreen';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

const HomeStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="HomeMain" 
      component={HomeScreen}
      options={{ title: 'Home' }}
    />
    <Stack.Screen 
      name="Thread" 
      component={ThreadScreen}
      options={{ title: 'Thread' }}
    />
    <Stack.Screen 
      name="CreateThread" 
      component={CreateThreadScreen}
      options={{ title: 'Create Thread' }}
    />
    <Stack.Screen 
      name="Search" 
      component={SearchScreen}
      options={{ title: 'Search' }}
    />
  </Stack.Navigator>
);

const ForumsStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="ForumsMain" 
      component={ForumsScreen}
      options={{ title: 'Forums' }}
    />
    <Stack.Screen 
      name="Thread" 
      component={ThreadScreen}
      options={{ title: 'Thread' }}
    />
  </Stack.Navigator>
);

const MessagesStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="MessagesMain" 
      component={MessagesScreen}
      options={{ title: 'Messages' }}
    />
    <Stack.Screen 
      name="Chat" 
      component={ChatScreen}
      options={{ title: 'Chat' }}
    />
    <Stack.Screen 
      name="VideoCall" 
      component={VideoCallScreen}
      options={{ title: 'Video Call' }}
    />
  </Stack.Navigator>
);

const ProfileStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="ProfileMain" 
      component={ProfileScreen}
      options={{ title: 'Profile' }}
    />
    <Stack.Screen 
      name="Settings" 
      component={SettingsScreen}
      options={{ title: 'Settings' }}
    />
    <Stack.Screen 
      name="Notifications" 
      component={NotificationsScreen}
      options={{ title: 'Notifications' }}
    />
    <Stack.Screen 
      name="Analytics" 
      component={AnalyticsScreen}
      options={{ title: 'Analytics' }}
    />
    <Stack.Screen 
      name="Achievements" 
      component={AchievementsScreen}
      options={{ title: 'Achievements' }}
    />
    <Stack.Screen 
      name="Marketplace" 
      component={MarketplaceScreen}
      options={{ title: 'Marketplace' }}
    />
  </Stack.Navigator>
);

const MainNavigator: React.FC = () => {
  const { isDarkMode } = useThemeStore();

  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        tabBarIcon: ({ focused, color, size }) => {
          let iconName: string;

          switch (route.name) {
            case 'Home':
              iconName = 'home';
              break;
            case 'Forums':
              iconName = 'forum';
              break;
            case 'Messages':
              iconName = 'message';
              break;
            case 'Profile':
              iconName = 'person';
              break;
            default:
              iconName = 'help';
          }

          return <Icon name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: isDarkMode ? '#BB86FC' : '#6200EE',
        tabBarInactiveTintColor: isDarkMode ? '#757575' : '#9E9E9E',
        tabBarStyle: {
          backgroundColor: isDarkMode ? '#121212' : '#FFFFFF',
          borderTopColor: isDarkMode ? '#333333' : '#E0E0E0',
        },
        headerStyle: {
          backgroundColor: isDarkMode ? '#1E1E1E' : '#6200EE',
        },
        headerTintColor: isDarkMode ? '#FFFFFF' : '#FFFFFF',
      })}
    >
      <Tab.Screen name="Home" component={HomeStack} />
      <Tab.Screen name="Forums" component={ForumsStack} />
      <Tab.Screen name="Messages" component={MessagesStack} />
      <Tab.Screen name="Profile" component={ProfileStack} />
    </Tab.Navigator>
  );
};

export default MainNavigator;