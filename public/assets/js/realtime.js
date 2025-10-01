/* Modern Forum - Real-time JavaScript Client */

class RealTimeClient {
    constructor(serverUrl = 'ws://localhost:8080', token = '') {
        this.serverUrl = serverUrl;
        this.token = token;
        this.ws = null;
        this.connected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.eventHandlers = {};
        this.rooms = new Set();
        this.typingUsers = new Map();
        this.onlineUsers = new Map();
        
        // Auto-connect if token is provided
        if (this.token) {
            this.connect();
        }
    }

    connect() {
        try {
            this.ws = new WebSocket(this.serverUrl);
            
            this.ws.onopen = () => {
                console.log('WebSocket connected');
                this.connected = true;
                this.reconnectAttempts = 0;
                
                // Authenticate
                this.send('auth', { token: this.token });
                this.emit('connected');
            };
            
            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleMessage(data);
                } catch (error) {
                    console.error('Failed to parse WebSocket message:', error);
                }
            };
            
            this.ws.onclose = () => {
                console.log('WebSocket disconnected');
                this.connected = false;
                this.emit('disconnected');
                this.handleReconnect();
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.emit('error', { error: error.message });
            };
            
        } catch (error) {
            console.error('WebSocket connection failed:', error);
            this.handleReconnect();
        }
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
        this.connected = false;
        this.emit('disconnected');
    }

    send(type, data = {}) {
        if (!this.connected || !this.ws) {
            console.warn('WebSocket not connected');
            return false;
        }

        try {
            const message = { type, ...data };
            this.ws.send(JSON.stringify(message));
            return true;
        } catch (error) {
            console.error('Failed to send WebSocket message:', error);
            return false;
        }
    }

    joinRoom(room) {
        if (this.send('join_room', { room })) {
            this.rooms.add(room);
            this.emit('room_joining', { room });
        }
    }

    leaveRoom(room) {
        if (this.send('leave_room', { room })) {
            this.rooms.delete(room);
            this.emit('room_leaving', { room });
        }
    }

    sendMessage(room, message) {
        return this.send('message', { room, message });
    }

    startTyping(room) {
        return this.send('typing', { room });
    }

    stopTyping(room) {
        return this.send('stop_typing', { room });
    }

    updatePresence(status) {
        return this.send('presence', { status });
    }

    on(event, handler) {
        if (!this.eventHandlers[event]) {
            this.eventHandlers[event] = [];
        }
        this.eventHandlers[event].push(handler);
    }

    off(event, handler = null) {
        if (handler === null) {
            delete this.eventHandlers[event];
        } else {
            const handlers = this.eventHandlers[event] || [];
            const index = handlers.indexOf(handler);
            if (index > -1) {
                handlers.splice(index, 1);
            }
        }
    }

    emit(event, data = {}) {
        const handlers = this.eventHandlers[event] || [];
        handlers.forEach(handler => {
            try {
                handler(data);
            } catch (error) {
                console.error('Event handler error:', error);
            }
        });
    }

    handleMessage(data) {
        switch (data.type) {
            case 'auth_success':
                this.emit('authenticated', data);
                break;
            case 'room_joined':
                this.emit('room_joined', data);
                break;
            case 'user_joined':
                this.emit('user_joined', data);
                this.updateOnlineUsers(data.user, 'online');
                break;
            case 'user_left':
                this.emit('user_left', data);
                this.updateOnlineUsers(data.user, 'offline');
                break;
            case 'message':
                this.emit('message', data);
                this.handleNewMessage(data);
                break;
            case 'typing':
                this.emit('typing', data);
                this.handleTyping(data);
                break;
            case 'stop_typing':
                this.emit('stop_typing', data);
                this.handleStopTyping(data);
                break;
            case 'presence_update':
                this.emit('presence_update', data);
                this.updateOnlineUsers(data.user, data.status);
                break;
            case 'notification':
                this.emit('notification', data);
                this.handleNotification(data);
                break;
            case 'error':
                this.emit('error', data);
                break;
            default:
                this.emit('unknown_message', data);
        }
    }

    handleNewMessage(data) {
        // Add message to chat if it's a chat room
        if (data.room.startsWith('chat_')) {
            this.addMessageToChat(data.room, data);
        }
        
        // Update unread count
        this.updateUnreadCount(data.room);
        
        // Show notification if not in current room
        if (data.room !== this.getCurrentRoom()) {
            this.showMessageNotification(data);
        }
    }

    handleTyping(data) {
        const room = data.room;
        const user = data.user;
        
        if (!this.typingUsers.has(room)) {
            this.typingUsers.set(room, new Set());
        }
        
        this.typingUsers.get(room).add(user.id);
        this.updateTypingIndicator(room);
        
        // Auto-stop typing after 3 seconds
        setTimeout(() => {
            this.stopTyping(room);
        }, 3000);
    }

    handleStopTyping(data) {
        const room = data.room;
        const user = data.user;
        
        if (this.typingUsers.has(room)) {
            this.typingUsers.get(room).delete(user.id);
            this.updateTypingIndicator(room);
        }
    }

    handleNotification(data) {
        // Show browser notification
        if (Notification.permission === 'granted') {
            new Notification('New Notification', {
                body: data.data.message || 'You have a new notification',
                icon: '/assets/images/notification-icon.png'
            });
        }
        
        // Update notification count
        this.updateNotificationCount();
        
        // Show in-app notification
        this.showInAppNotification(data.data);
    }

    updateOnlineUsers(user, status) {
        this.onlineUsers.set(user.id, { ...user, status });
        this.emit('online_users_updated', Array.from(this.onlineUsers.values()));
    }

    updateTypingIndicator(room) {
        const typingUsers = this.typingUsers.get(room) || new Set();
        const typingElement = document.querySelector(`[data-room="${room}"] .typing-indicator`);
        
        if (typingElement) {
            if (typingUsers.size > 0) {
                const usernames = Array.from(typingUsers).map(userId => {
                    const user = this.onlineUsers.get(userId);
                    return user ? user.username : 'Someone';
                });
                
                const text = typingUsers.size === 1 
                    ? `${usernames[0]} is typing...`
                    : `${usernames.slice(0, -1).join(', ')} and ${usernames[usernames.length - 1]} are typing...`;
                
                typingElement.textContent = text;
                typingElement.style.display = 'block';
            } else {
                typingElement.style.display = 'none';
            }
        }
    }

    addMessageToChat(room, message) {
        const chatContainer = document.querySelector(`[data-room="${room}"] .chat-messages`);
        if (!chatContainer) return;
        
        const messageElement = this.createMessageElement(message);
        chatContainer.appendChild(messageElement);
        
        // Scroll to bottom
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    createMessageElement(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message';
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <img src="${message.user.avatar || '/assets/images/default-avatar.png'}" 
                     alt="${message.user.username}">
            </div>
            <div class="message-content">
                <div class="message-header">
                    <span class="message-author">${message.user.username}</span>
                    <span class="message-time">${this.formatTime(message.timestamp)}</span>
                </div>
                <div class="message-text">${this.escapeHtml(message.message)}</div>
            </div>
        `;
        
        return messageDiv;
    }

    updateUnreadCount(room) {
        const unreadElement = document.querySelector(`[data-room="${room}"] .unread-count`);
        if (unreadElement) {
            const currentCount = parseInt(unreadElement.textContent) || 0;
            unreadElement.textContent = currentCount + 1;
            unreadElement.style.display = currentCount === 0 ? 'block' : 'block';
        }
    }

    updateNotificationCount() {
        const notificationElement = document.querySelector('.notification-count');
        if (notificationElement) {
            const currentCount = parseInt(notificationElement.textContent) || 0;
            notificationElement.textContent = currentCount + 1;
            notificationElement.style.display = 'block';
        }
    }

    showMessageNotification(message) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'message-notification';
        toast.innerHTML = `
            <div class="notification-avatar">
                <img src="${message.user.avatar || '/assets/images/default-avatar.png'}" 
                     alt="${message.user.username}">
            </div>
            <div class="notification-content">
                <div class="notification-title">${message.user.username}</div>
                <div class="notification-message">${this.escapeHtml(message.message)}</div>
            </div>
            <button class="notification-close">&times;</button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
        
        // Close button
        toast.querySelector('.notification-close').addEventListener('click', () => {
            toast.remove();
        });
    }

    showInAppNotification(data) {
        // Show notification in the UI
        const notification = document.createElement('div');
        notification.className = 'in-app-notification';
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="notification-text">
                ${data.message || 'You have a new notification'}
            </div>
        `;
        
        document.querySelector('.notifications-container')?.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    getCurrentRoom() {
        // Get current room from URL or active tab
        const activeTab = document.querySelector('.chat-tab.active');
        return activeTab ? activeTab.dataset.room : null;
    }

    formatTime(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    handleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            this.emit('reconnect_failed');
            return;
        }

        this.reconnectAttempts++;
        
        this.emit('reconnecting', {
            attempt: this.reconnectAttempts,
            maxAttempts: this.maxReconnectAttempts
        });

        // Exponential backoff
        const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
        
        setTimeout(() => {
            this.connect();
        }, delay);
    }

    // Public methods
    isConnected() {
        return this.connected;
    }

    getReconnectAttempts() {
        return this.reconnectAttempts;
    }

    setMaxReconnectAttempts(max) {
        this.maxReconnectAttempts = max;
    }

    setReconnectDelay(delay) {
        this.reconnectDelay = delay;
    }

    getOnlineUsers() {
        return Array.from(this.onlineUsers.values());
    }

    getTypingUsers(room) {
        return Array.from(this.typingUsers.get(room) || []);
    }
}

// Global instance
window.realTimeClient = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get WebSocket token from meta tag or session
    const token = document.querySelector('meta[name="ws-token"]')?.getAttribute('content') || '';
    
    if (token) {
        window.realTimeClient = new RealTimeClient('ws://localhost:8080', token);
        
        // Set up event handlers
        window.realTimeClient.on('connected', () => {
            console.log('Real-time client connected');
            document.body.classList.add('realtime-connected');
        });
        
        window.realTimeClient.on('disconnected', () => {
            console.log('Real-time client disconnected');
            document.body.classList.remove('realtime-connected');
        });
        
        window.realTimeClient.on('reconnecting', (data) => {
            console.log(`Reconnecting... (${data.attempt}/${data.maxAttempts})`);
        });
        
        window.realTimeClient.on('reconnect_failed', () => {
            console.log('Failed to reconnect to real-time server');
        });
        
        window.realTimeClient.on('error', (data) => {
            console.error('Real-time error:', data.error);
        });
        
        window.realTimeClient.on('notification', (data) => {
            // Handle notifications
            if (window.modernForum && window.modernForum.showNotification) {
                window.modernForum.showNotification(data.data.message || 'New notification', 'info');
            }
        });
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RealTimeClient;
}