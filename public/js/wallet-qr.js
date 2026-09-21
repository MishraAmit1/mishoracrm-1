/*
 * Customer wallet "Show at counter" QR. Fetches a fresh 90-second signed
 * payload every ~55s and draws it in the browser (qrcode-generator) — the
 * token never leaves the device, and a screenshot goes stale almost at once.
 */
(function () {
    'use strict';

    var box = document.getElementById('wallet-qr');
    if (!box || typeof qrcode !== 'function') { return; }

    var img = box.querySelector('img');
    var note = box.querySelector('[data-role="note"]');
    var url = box.getAttribute('data-url');
    var REFRESH_MS = 55000;
    var timer = null;

    function draw(payload) {
        var qr = qrcode(0, 'M');
        qr.addData(payload);
        qr.make();
        img.src = qr.createDataURL(6, 8);
        img.hidden = false;
    }

    function load() {
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store' })
            .then(function (res) {
                if (!res.ok) { throw new Error('bad status'); }
                return res.json();
            })
            .then(function (data) {
                draw(data.payload);
                note.textContent = 'Show this to the cashier. It refreshes automatically.';
            })
            .catch(function () {
                note.textContent = 'Could not load your code. Check your connection.';
            });
    }

    function start() {
        load();
        timer = setInterval(load, REFRESH_MS);
    }

    // Don't burn refreshes (or show a stale code) while the tab is in the background.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            clearInterval(timer);
        } else {
            clearInterval(timer);
            start();
        }
    });

    start();
})();
