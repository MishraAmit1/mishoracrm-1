<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Printable QR material for the shop's window / counter / tables. The QR is drawn
// in the browser (public/js/qr-kit.js) and the page is printed or saved as a PDF
// from the browser's print dialog — no server-side PDF or third-party QR service.
class LoyaltyQrKitController extends Controller
{
    public const TEMPLATES = [
        'poster' => ['label' => 'Window poster', 'size' => 'A4 portrait', 'desc' => 'Big and bold for a door or window.'],
        'stand'  => ['label' => 'Counter stand', 'size' => 'A5 portrait', 'desc' => 'Sits by the till in a small acrylic stand.'],
        'tent'   => ['label' => 'Table tent', 'size' => 'A4 landscape, fold in half', 'desc' => 'Fold along the dotted line — reads from both sides of the table.'],
    ];

    public function index(): View
    {
        $tenant = auth()->user()->tenant;

        return view('tenant.loyalty.qr-kit.index', [
            'tenant'    => $tenant,
            'templates' => self::TEMPLATES,
            'joinUrl'   => $tenant->loyaltyWelcomeUrl(),
            'walletUrl' => Tenant::portalLoginUrl(),
            'headline'  => $this->defaultHeadline($tenant),
        ]);
    }

    public function show(Request $request, string $template): View
    {
        abort_unless(isset(self::TEMPLATES[$template]), 404);

        $data = $request->validate([
            'qr'       => ['nullable', 'in:join,wallet'],
            'headline' => ['nullable', 'string', 'max:90'],
        ]);

        $tenant  = auth()->user()->tenant;
        $joinUrl = $tenant->loyaltyWelcomeUrl();

        // "Join" needs the WhatsApp welcome capture to be configured; otherwise
        // the wallet sign-in link is the only meaningful target.
        $qr = ($data['qr'] ?? ($joinUrl ? 'join' : 'wallet')) === 'join' && $joinUrl ? 'join' : 'wallet';

        return view('tenant.loyalty.qr-kit.print', [
            'tenant'   => $tenant,
            'template' => $template,
            'meta'     => self::TEMPLATES[$template],
            'qr'       => $qr,
            'qrUrl'    => $qr === 'join' ? $joinUrl : Tenant::portalLoginUrl(),
            'walletUrl' => Tenant::portalLoginUrl(),
            'headline' => trim((string) ($data['headline'] ?? '')) ?: $this->defaultHeadline($tenant),
            'subline'  => $qr === 'join'
                ? 'Scan to join our rewards club on WhatsApp'
                : 'Scan to open your rewards wallet',
        ]);
    }

    private function defaultHeadline(Tenant $tenant): string
    {
        $s      = $tenant->loyaltySettings();
        $reward = trim((string) ($s['stamp_reward'] ?? '')) ?: 'a free reward';

        return match ($s['mode'] ?? 'points') {
            'stamps' => 'Collect ' . (int) $s['stamps_required'] . ' stamps, get ' . $reward . '!',
            'both'   => 'Earn points & collect stamps on every visit',
            default  => 'Earn rewards every time you visit',
        };
    }
}
