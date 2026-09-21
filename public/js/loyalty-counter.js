/*
 * Loyalty counter helpers — shared by the standalone /loyalty/counter page and
 * the invoice page's "Scan Customer" dialog. Needs jsQR (public/js/jsQR.js).
 *
 * The customer's wallet QR carries only a short-lived signed RELATIVE url
 * ("/loyalty/counter/scan/{id}?expires=…&signature=…"), so it is requested from
 * whichever shop subdomain the staff member is signed in to.
 */
(function (global) {
    'use strict';

    var SCAN_PREFIX = '/loyalty/counter/scan/';

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function firstError(json) {
        if (json && json.errors) {
            var key = Object.keys(json.errors)[0];
            if (key) { return json.errors[key][0]; }
        }
        return null;
    }

    // JSON in / JSON out. Rejects with an Error carrying a readable message.
    function request(url, options) {
        options = options || {};
        var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        if (options.body) {
            headers['Content-Type'] = 'application/json';
            headers['X-CSRF-TOKEN'] = csrf();
        }

        return fetch(url, {
            method: options.method || (options.body ? 'POST' : 'GET'),
            headers: headers,
            credentials: 'same-origin',
            body: options.body ? JSON.stringify(options.body) : undefined
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (json) {
                if (!res.ok) {
                    var message = json.message || firstError(json) || 'Something went wrong. Please try again.';
                    if (res.status === 403 && /signature/i.test(message)) {
                        message = 'That QR has expired. Ask the customer to show their code again.';
                    }
                    var err = new Error(message);
                    err.status = res.status;
                    throw err;
                }
                return json;
            });
        });
    }

    // Only our own wallet QR is acted on — any other QR the camera sees is ignored.
    function isWalletQr(text) {
        return typeof text === 'string' && text.indexOf(SCAN_PREFIX) === 0;
    }

    // Opens the rear camera and calls onText(text) for every QR it decodes.
    // Return true from onText to stop scanning. Resolves to { stop() }.
    function startScanner(video, onText) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return Promise.reject(new Error('Camera scanning needs a camera and a secure (https) connection. Use the phone number instead.'));
        }
        if (typeof global.jsQR !== 'function') {
            return Promise.reject(new Error('The QR scanner failed to load. Use the phone number instead.'));
        }

        return navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false }).then(function (stream) {
            var active = true;
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d', { willReadFrequently: true });

            function stop() {
                active = false;
                stream.getTracks().forEach(function (track) { track.stop(); });
                video.srcObject = null;
            }

            function tick() {
                if (!active) { return; }

                if (video.readyState === video.HAVE_ENOUGH_DATA && video.videoWidth) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    var frame = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    var code = global.jsQR(frame.data, frame.width, frame.height, { inversionAttempts: 'dontInvert' });

                    if (code && code.data && onText(code.data) === true) {
                        stop();
                        return;
                    }
                }
                requestAnimationFrame(tick);
            }

            video.srcObject = stream;
            video.setAttribute('playsinline', 'true');

            return video.play().then(function () {
                requestAnimationFrame(tick);
                return { stop: stop };
            });
        }).catch(function (err) {
            if (err && (err.name === 'NotAllowedError' || err.name === 'SecurityError')) {
                throw new Error('Camera permission was blocked. Allow it in the browser, or use the phone number instead.');
            }
            throw err;
        });
    }

    function esc(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    global.LoyaltyCounter = {
        request: request,
        startScanner: startScanner,
        isWalletQr: isWalletQr,
        esc: esc
    };
})(window);
