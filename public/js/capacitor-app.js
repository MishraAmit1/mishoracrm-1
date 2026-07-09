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

})();