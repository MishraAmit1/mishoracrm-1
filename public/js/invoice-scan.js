/*
 * Invoice page "Scan Customer": read the customer's wallet QR, confirm it's the
 * same customer the bill is for, then offer one-tap use of their points / offers
 * through the invoice's EXISTING redeem-points and apply-coupon endpoints.
 */
(function () {
    'use strict';

    var root = document.getElementById('inv-scan');
    if (!root || !window.LoyaltyCounter) { return; }

    var LC = window.LoyaltyCounter;
    var esc = LC.esc;

    var video = document.getElementById('inv-video');
    var scanBtn = document.getElementById('inv-scan-btn');
    var stopBtn = document.getElementById('inv-scan-stop');
    var msg = document.getElementById('inv-scan-msg');
    var actions = document.getElementById('inv-scan-actions');
    var redeemForm = document.getElementById('inv-redeem-form');
    var couponForm = document.getElementById('inv-coupon-form');
    var couponCode = document.getElementById('inv-coupon-code');

    var scanner = null;

    function say(html, kind) {
        msg.innerHTML = html || '';
        msg.style.color = kind === 'err' ? 'var(--red)' : (kind === 'ok' ? 'var(--green)' : 'var(--text-300)');
        msg.style.fontWeight = kind ? '600' : '400';
    }

    function stopScan() {
        if (scanner) { scanner.stop(); scanner = null; }
        video.hidden = true;
        stopBtn.hidden = true;
        scanBtn.hidden = false;
    }

    function button(label, onClick, primary) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn ' + (primary ? 'btn-primary' : 'btn-secondary');
        b.textContent = label;
        b.addEventListener('click', onClick);
        return b;
    }

    function handle(payload) {
        actions.innerHTML = '';

        if (String(payload.contact.id) !== String(root.getAttribute('data-contact'))) {
            say('This wallet belongs to <strong>' + esc(payload.contact.name) + '</strong>, but this bill is for <strong>'
                + esc(root.getAttribute('data-contact-name')) + '</strong>. Check the customer.', 'err');
            return;
        }

        var c = payload.card;
        say('Matched <strong>' + esc(payload.contact.name) + '</strong>.', 'ok');

        if (c.mode !== 'stamps' && c.redeemable_value > 0) {
            actions.appendChild(button('Use max points (₹' + Math.round(c.redeemable_value) + ')', function () {
                redeemForm.submit();
            }, true));
        }

        payload.offers.forEach(function (offer) {
            actions.appendChild(button('Apply ' + offer.label + ' · ' + offer.code, function () {
                couponCode.value = offer.code;
                couponForm.submit();
            }, false));
        });

        if (!actions.children.length) {
            say('Matched <strong>' + esc(payload.contact.name) + '</strong> — no points or offers to use right now.', 'ok');
        }
    }

    scanBtn.addEventListener('click', function () {
        var lastBad = '';
        actions.innerHTML = '';
        say('Starting camera…');
        video.hidden = false;
        scanBtn.hidden = true;
        stopBtn.hidden = false;

        LC.startScanner(video, function (text) {
            if (!LC.isWalletQr(text)) {
                if (text !== lastBad) {
                    lastBad = text;
                    say("That isn't a wallet QR. Ask the customer to open their wallet.", 'err');
                }
                return false;
            }

            say('Checking…');
            LC.request(text)
                .then(function (payload) { stopScan(); handle(payload); })
                .catch(function (err) { stopScan(); say(esc(err.message), 'err'); });

            return true;
        }).then(function (s) {
            scanner = s;
            say("Point the camera at the customer's QR.");
        }).catch(function (err) {
            stopScan();
            say(esc(err.message), 'err');
        });
    });

    stopBtn.addEventListener('click', function () {
        stopScan();
        say('');
    });
})();
