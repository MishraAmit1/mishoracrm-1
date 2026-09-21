/*
 * QR Kit printouts: draws each [data-qr] box as a scalable SVG QR in the browser
 * (qrcode-generator), so nothing about the shop is sent to a third-party service
 * and the QR stays sharp at any print size.
 */
(function () {
    'use strict';

    if (typeof qrcode !== 'function') { return; }

    Array.prototype.forEach.call(document.querySelectorAll('[data-qr]'), function (box) {
        var qr = qrcode(0, 'M');
        qr.addData(box.getAttribute('data-qr'));
        qr.make();
        box.innerHTML = qr.createSvgTag({
            cellSize: 4,
            margin: 0,
            scalable: true,
            alt: box.getAttribute('data-alt') || 'QR code'
        });
    });
})();
