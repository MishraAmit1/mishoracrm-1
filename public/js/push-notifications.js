import { PushNotifications } from '@capacitor/push-notifications';


alert("Step 1 - Script Loaded");

async function initPush() {

    alert("Step 2 - initPush");

    const permission = await PushNotifications.requestPermissions();

    alert("Step 3 - Permission", permission);

    await PushNotifications.register();

    alert("Step 4 - register() called");

    PushNotifications.addListener("registration", (token) => {
        alert("Step 5 - TOKEN", token.value);
    });

    PushNotifications.addListener("registrationError", (error) => {
        alert("Step 6 - Registration Error", error);
    });
}

document.addEventListener("DOMContentLoaded", initPush);
// alert(window.Capacitor)
// alert(navigator.userAgent)