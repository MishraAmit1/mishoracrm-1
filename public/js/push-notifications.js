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
