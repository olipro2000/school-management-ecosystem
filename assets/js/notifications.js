// Push Notifications
let notificationPermission = false;

function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            notificationPermission = permission === 'granted';
            if (notificationPermission) {
                showNotification('Notifications Enabled', 'You will now receive push notifications', 'success');
            }
        });
    }
}

function showNotification(title, message, type = 'info') {
    if ('Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification(title, {
            body: message,
            icon: '/skl/assets/images/logo.png',
            badge: '/skl/assets/images/badge.png',
            tag: type,
            requireInteraction: false
        });
        
        notification.onclick = function() {
            window.focus();
            notification.close();
        };
        
        setTimeout(() => notification.close(), 5000);
    }
}

function checkNewMessages() {
    fetch('../controllers/notifications.php?action=messages')
        .then(res => res.json())
        .then(data => {
            if (data.count > 0) {
                showNotification('New Messages', `You have ${data.count} unread message(s)`, 'info');
            }
        })
        .catch(err => console.log(err));
}

function checkNewAnnouncements() {
    fetch('../controllers/notifications.php?action=announcements')
        .then(res => res.json())
        .then(data => {
            if (data.count > 0) {
                showNotification('New Announcement', data.title, 'info');
            }
        })
        .catch(err => console.log(err));
}

document.addEventListener('DOMContentLoaded', function() {
    if ('Notification' in window && Notification.permission === 'default') {
        setTimeout(requestNotificationPermission, 2000);
    }
    
    if (Notification.permission === 'granted') {
        setInterval(checkNewMessages, 60000);
        setInterval(checkNewAnnouncements, 120000);
    }
});
