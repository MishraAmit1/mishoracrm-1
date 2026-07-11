(function () {

    console.log("Capacitor Helper Loaded");

    window.CapacitorHelper = {

        isCapacitor() {
            return !!window.Capacitor;
        },

        isAndroid() {
            return window.Capacitor?.getPlatform?.() === "android";
        },

        isIOS() {
            return window.Capacitor?.getPlatform?.() === "ios";
        },

        isMobileApp() {
            return this.isCapacitor();
        }

    };

    if (!CapacitorHelper.isCapacitor()) return;

    var html = document.documentElement;
    html.classList.add('is-capacitor-app');
    if (CapacitorHelper.isAndroid()) html.classList.add('is-android-app');
    if (CapacitorHelper.isIOS()) html.classList.add('is-ios-app');

    /* ── Hardware back button (Android) ─────────────────────────────
       Close any open overlay first; otherwise go back in app history;
       only exit the app from the dashboard (nothing left to go back to). */
    var App = window.Capacitor?.Plugins?.App;
    if (App && App.addListener) {
        App.addListener('backButton', function () {
            var openDrop = document.querySelector('.drop-menu.open');
            var bellDrop = document.getElementById('bellDropdown');
            var sidebar = document.getElementById('sidebar');
            var confirmBackdrop = document.getElementById('confirmBackdrop');

            if (confirmBackdrop && confirmBackdrop.style.display !== 'none') {
                if (typeof closeConfirm === 'function') closeConfirm();
                return;
            }
            if (bellDrop && bellDrop.style.display === 'block') {
                bellDrop.style.display = 'none';
                return;
            }
            if (openDrop) {
                openDrop.classList.remove('open');
                return;
            }
            if (sidebar && sidebar.classList.contains('mobile-open') && typeof closeMobile === 'function') {
                closeMobile();
                return;
            }
            if (window.history.length > 1) {
                window.history.back();
                return;
            }
            App.exitApp();
        });
    }

})();
alert("Capacitor Object:", window.Capacitor);

alert("Plugins:", window.Capacitor?.Plugins);

alert("Push Plugin:", window.Capacitor?.Plugins?.PushNotifications);