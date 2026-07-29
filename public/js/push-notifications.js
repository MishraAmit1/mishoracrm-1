// alert("Step 1 - Script Loaded");

// async function initPush() {

  //  alert("Step 2 - initPush");

    // const Capacitor = window.Capacitor || window.CapacitorCore;
    // const PushNotifications = Capacitor?.Plugins?.PushNotifications;

    // if (!PushNotifications) {
        // alert("Step 2b - PushNotifications plugin not available");
    //     return;
    // }

    // PushNotifications.addListener("registration", (token) => {
        // alert("Step 5 - TOKEN " + token.value);
    // });

    // PushNotifications.addListener("registrationError", (error) => {
        // alert("Step 6 - Registration Error " + JSON.stringify(error));
    // });

    // const permission = await PushNotifications.requestPermissions();

    // alert("Step 3 - Permission " + JSON.stringify(permission));

    // await PushNotifications.register();

    // alert("Step 4 - register() called");
// }

// document.addEventListener("DOMContentLoaded", initPush);
// alert(window.Capacitor)
// alert(navigator.userAgent)

import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';

async function initPush() {

    if (!Capacitor.isNativePlatform()) {
        return;
    }

    const permission = await PushNotifications.requestPermissions();

    if (permission.receive !== 'granted') {
        return;
    }

    PushNotifications.addListener('registration', async (token) => {

        alert("FCM Token: " + token.value);

        await saveToken(token.value);

    });

    PushNotifications.addListener('registrationError', (error) => {

        alert("Registration Error: " + JSON.stringify(error));

    });

    await PushNotifications.register();
}

async function saveToken(token) {

    try {

        await fetch('/device-token', {

            method: 'POST',

            headers: {

                'Content-Type': 'application/json',

                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .content

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

        alert("Device token saved.");

    } catch (e) {

        alert.error(e);

    }

}

document.addEventListener("DOMContentLoaded", initPush);