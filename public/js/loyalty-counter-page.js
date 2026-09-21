/*
 * /loyalty/counter — scan a customer's wallet QR (or type their phone), then
 * stamp / redeem / apply an offer in one tap. See loyalty-counter.js.
 */
(function () {
    'use strict';

    var root = document.getElementById('counter');
    if (!root || !window.LoyaltyCounter) { return; }

    var LC = window.LoyaltyCounter;
    var esc = LC.esc;
    var urls = {
        resolve: root.getAttribute('data-resolve'),
        stamp: root.getAttribute('data-stamp'),
        checkout: root.getAttribute('data-checkout')
    };

    var video = document.getElementById('ct-video');
    var scanBtn = document.getElementById('ct-scan');
    var stopBtn = document.getElementById('ct-scan-stop');
    var phoneForm = document.getElementById('ct-phone-form');
    var phoneInput = document.getElementById('ct-phone');
    var msg = document.getElementById('ct-msg');
    var result = document.getElementById('ct-result');

    var scanner = null;
    var current = null;

    function say(text, kind) {
        msg.textContent = text || '';
        msg.className = 'ct-msg' + (kind ? ' ' + kind : '');
    }

    function stopScan() {
        if (scanner) { scanner.stop(); scanner = null; }
        video.hidden = true;
        stopBtn.hidden = true;
        scanBtn.hidden = false;
    }

    function money(n) {
        return '₹' + Number(n).toLocaleString('en-IN', { maximumFractionDigits: 0 });
    }

    function render(payload) {
        var c = payload.card;
        var who = payload.contact;
        var stamps = c.mode === 'stamps' || c.mode === 'both';
        var points = c.mode === 'points' || c.mode === 'both';
        var html = '';

        html += '<div class="ct-head"><div><div class="ct-name">' + esc(who.name) + '</div>';
        html += '<div class="ct-sub">' + esc(who.phone || '') + (who.linked ? ' · wallet linked' : '') + '</div></div>';
        html += '<button type="button" class="btn btn-secondary btn-sm" data-action="clear">Done</button></div>';

        html += '<div class="ct-stats">';
        if (stamps) {
            html += '<div class="ct-stat"><div class="v">' + c.stamp_count + '/' + c.stamps_required + '</div><div class="l">stamps</div></div>';
            html += '<div class="ct-stat"><div class="v">' + c.rewards_unlocked + '</div><div class="l">free ' + (c.rewards_unlocked === 1 ? 'reward' : 'rewards') + ' unlocked</div></div>';
        }
        if (points) {
            html += '<div class="ct-stat"><div class="v">' + Number(c.points).toLocaleString('en-IN') + '</div><div class="l">points' + (c.tier_label ? ' · ' + esc(c.tier_label) : '') + '</div></div>';
            if (c.redeemable_value > 0) {
                html += '<div class="ct-stat"><div class="v">' + money(c.redeemable_value) + '</div><div class="l">redeemable</div></div>';
            }
        }
        html += '</div>';

        if (stamps) {
            html += '<div class="ct-actions"><button type="button" class="btn btn-primary" data-action="stamp">+1 Stamp</button>';
            if (c.rewards_unlocked > 0) {
                html += '<button type="button" class="btn btn-primary" data-action="reward">Redeem free ' + esc(c.stamp_reward || 'reward') + '</button>';
            }
            html += '</div>';
        }

        var canPoints = points && c.redeemable_value > 0;
        if (canPoints || payload.offers.length) {
            html += '<div class="ct-bill"><label for="ct-amount">Bill amount (₹) <span>needed to use points or an offer</span></label>';
            html += '<input type="number" id="ct-amount" min="1" step="any" inputmode="decimal" placeholder="e.g. 450"/></div>';
            html += '<div class="ct-actions">';
            if (canPoints) {
                html += '<button type="button" class="btn btn-secondary" data-action="points">Use points (up to ' + money(c.redeemable_value) + ')</button>';
            }
            payload.offers.forEach(function (o) {
                html += '<button type="button" class="btn btn-secondary" data-action="offer" data-code="' + esc(o.code) + '">' + esc(o.label) + ' · ' + esc(o.code) + '</button>';
            });
            html += '</div>';
        }

        if (!stamps && !canPoints && !payload.offers.length) {
            html += '<div class="ct-sub">Nothing to redeem right now.</div>';
        }

        result.innerHTML = html;
        result.hidden = false;
    }

    function show(payload) {
        current = payload;
        render(payload);
    }

    function setBusy(busy) {
        Array.prototype.forEach.call(result.querySelectorAll('button'), function (b) { b.disabled = busy; });
    }

    function amountValue() {
        var el = document.getElementById('ct-amount');
        return el && el.value ? el.value : null;
    }

    function act(url, body) {
        setBusy(true);
        say('Working…');

        LC.request(url, { body: Object.assign({ contact_id: current.contact.id }, body) })
            .then(function (res) {
                say(res.message, 'ok');
                show(res.card);
            })
            .catch(function (err) {
                say(err.message, 'err');
                setBusy(false);
            });
    }

    result.addEventListener('click', function (event) {
        var btn = event.target.closest('button[data-action]');
        if (!btn || !current) { return; }

        switch (btn.getAttribute('data-action')) {
            case 'clear':
                current = null;
                result.hidden = true;
                result.innerHTML = '';
                say('');
                break;
            case 'stamp':
                act(urls.stamp, {});
                break;
            case 'reward':
                act(urls.checkout, { redeem_reward: true, amount: amountValue() });
                break;
            case 'points':
                act(urls.checkout, { use_points: true, amount: amountValue() });
                break;
            case 'offer':
                act(urls.checkout, { offer_code: btn.getAttribute('data-code'), amount: amountValue() });
                break;
        }
    });

    scanBtn.addEventListener('click', function () {
        var lastBad = '';
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
                .then(function (payload) { show(payload); say(''); })
                .catch(function (err) { say(err.message, 'err'); });

            return true; // one good scan is enough — stop the camera
        }).then(function (s) {
            scanner = s;
            say("Point the camera at the customer's QR.");
        }).catch(function (err) {
            stopScan();
            say(err.message, 'err');
        });
    });

    stopBtn.addEventListener('click', function () {
        stopScan();
        say('');
    });

    // The scan may have stopped the camera on its own; put the buttons back.
    video.addEventListener('emptied', function () {
        if (!scanner) { video.hidden = true; stopBtn.hidden = true; scanBtn.hidden = false; }
    });

    phoneForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!phoneInput.value.trim()) { return; }

        say('Looking up…');
        LC.request(urls.resolve, { body: { phone: phoneInput.value.trim() } })
            .then(function (payload) { show(payload); say(''); phoneInput.value = ''; })
            .catch(function (err) { say(err.message, 'err'); });
    });
})();
