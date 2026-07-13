alert("Step 1 - Script Loaded");

async function initPush() {

    alert("Step 2 - initPush");

    const Capacitor = window.Capacitor || window.CapacitorCore;
    const PushNotifications = Capacitor?.Plugins?.PushNotifications;

    if (!PushNotifications) {
        alert("Step 2b - PushNotifications plugin not available");
        return;
    }

    const permission = await PushNotifications.requestPermissions();

    alert("Step 3 - Permission " + JSON.stringify(permission));

    await PushNotifications.register();

    alert("Step 4 - register() called");

    PushNotifications.addListener("registration", (token) => {
        alert("Step 5 - TOKEN " + token.value);
    });

    PushNotifications.addListener("registrationError", (error) => {
        alert("Step 6 - Registration Error " + JSON.stringify(error));
    });
}

document.addEventListener("DOMContentLoaded", initPush);
// alert(window.Capacitor)
// alert(navigator.userAgent)