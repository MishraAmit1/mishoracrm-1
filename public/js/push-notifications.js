function getCapacitor() {
    return window.Capacitor || window.CapacitorCore;
}

async function initPush() {

    const Capacitor = getCapacitor();
    const PushNotifications = Capacitor?.Plugins?.PushNotifications;

    if (!Capacitor || !Capacitor.isNativePlatform()) {
        return;
    }

    if (!PushNotifications) {
        console.log("PushNotifications plugin not available");
        return;
    }

    const permission = await PushNotifications.requestPermissions();

    if (permission.receive !== 'granted') {
        return;
    }

    PushNotifications.addListener('registration', async (token) => {

        await saveToken(Capacitor, token.value);

    });

    PushNotifications.addListener('registrationError', (error) => {

        console.error("Push registration error:", error);

    });

    // ── App open hone ke waqt bhi notification dikhaye (foreground) ───
    PushNotifications.addListener('pushNotificationReceived', (notification) => {

        const LocalNotifications = Capacitor?.Plugins?.LocalNotifications;

        if (!LocalNotifications) {
            console.log("Push received (foreground):", notification);
            return;
        }

        LocalNotifications.schedule({
            notifications: [{
                id: Date.now() % 2147483647,
                title: notification.title || 'CRM Pro',
                body: notification.body || '',
                extra: notification.data || {}
            }]
        });

    });

    // ── Notification tap karne par seedha related page par le jaye ────
    PushNotifications.addListener('pushNotificationActionPerformed', (action) => {

        const url = action.notification?.data?.url
            ?? action.notification?.extra?.url;

        if (url) {
            window.location.href = url;
        }

    });

    // ── Foreground local notification tap karne par bhi navigate kare ──
    const LocalNotifications = Capacitor?.Plugins?.LocalNotifications;

    if (LocalNotifications) {
        LocalNotifications.addListener('localNotificationActionPerformed', (action) => {

            const url = action.notification?.extra?.url;

            if (url) {
                window.location.href = url;
            }

        });
    }

    await PushNotifications.register();
}

async function saveToken(Capacitor, token) {

    try {

        await fetch('/device-token', {

            method: 'POST',

            headers: {

                'Content-Type': 'application/json',

                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .content,

                'Accept': 'application/json'

            },

            credentials: 'same-origin',

            body: JSON.stringify({

                device_token: token,

                device_id: null,

                device_name: navigator.userAgent,

                platform: Capacitor.getPlatform(),

                app_version: '1.0.0'

            })

        });

    } catch (e) {

        console.error("Failed to save device token:", e);

    }

}

document.addEventListener("DOMContentLoaded", initPush);
