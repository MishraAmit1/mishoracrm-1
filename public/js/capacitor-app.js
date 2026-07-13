function getCapacitor() {
    return window.Capacitor || window.CapacitorCore;
}

async function initPush() {

    const Capacitor = getCapacitor();

    alert("Capacitor Object: " + !!Capacitor);
    alert("Plugins: " + !!Capacitor?.Plugins);
    alert("Push Plugin: " + !!Capacitor?.Plugins?.PushNotifications);
    alert("Is Native Platform: " + !!Capacitor?.isNativePlatform?.());
    alert(Capacitor.getPlatform());
    alert(PushNotifications);
    if (!Capacitor || !Capacitor.isNativePlatform()) {
        console.log("Not running inside Capacitor");
        return;
    }

    const PushNotifications = Capacitor.Plugins?.PushNotifications;

    if (!PushNotifications) {
        console.log("PushNotifications plugin not available");
        return;
    }

    let permission = await PushNotifications.requestPermissions();

    if (permission.receive !== 'granted') {
        console.log("Notification Permission Denied");
        return;
    }

    await PushNotifications.register();

    PushNotifications.addListener('registration', async (token) => {

        console.log("FCM TOKEN:", token.value);

        await fetch('/device-token', {

            method: 'POST',

            headers: {

                'Content-Type': 'application/json',

                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]').content,

                'Accept': 'application/json'

            },

            body: JSON.stringify({

                fcm_token: token.value,

                platform: Capacitor.getPlatform()

            })

        });

    });

    PushNotifications.addListener('registrationError', err => {

        console.error(err);

    });

    PushNotifications.addListener('pushNotificationReceived', notification => {

        console.log(notification);

    });

    PushNotifications.addListener('pushNotificationActionPerformed', action => {

        console.log(action);

    });

}

document.addEventListener("DOMContentLoaded", initPush);
