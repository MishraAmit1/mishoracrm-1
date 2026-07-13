import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';

async function initPush() {

    if (!Capacitor.isNativePlatform()) {
        console.log("Not running inside Capacitor");
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
alert("Capacitor Object:", window.Capacitor);

alert("Plugins:", window.Capacitor?.Plugins);

alert("Push Plugin:", window.Capacitor?.Plugins?.PushNotifications);
alert("Is Native Platform:", Capacitor.isNativePlatform());
