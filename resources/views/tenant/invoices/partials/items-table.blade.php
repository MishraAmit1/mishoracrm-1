<table class="items-table">
    <thead>
        <tr>
            <th style="width:4%;">#</th>
            <th style="width:36%;">Description</th>
            <th class="c" style="width:8%;">HSN/SAC</th>
            <th class="c" style="width:8%;">Qty</th>
            <th class="r" style="width:14%;">Unit Price (₹)</th>
            <th class="c" style="width:8%;">GST %</th>
            <th class="r" style="width:10%;">GST Amt (₹)</th>
            <th class="r" style="width:12%;">Total (₹)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $i => $item)
            @php
                $qty       = floatval($item['quantity'] ?? 0);
                $rate      = floatval($item['rate']     ?? 0);
                $taxPct    = floatval($item['tax_percent'] ?? $invoice->tax_percent ?? 18);
                $lineBase  = $qty * $rate;
                $lineGst   = round($lineBase * $taxPct / 100, 2);
                $lineTotal = round($lineBase + $lineGst, 2);
            @endphp
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>
                    {{ $item['description'] ?? '' }}
                    @if(!empty($item['hsn']))
                        <div style="font-size:9.5px;color:#94a3b8;margin-top:2px;">HSN: {{ $item['hsn'] }}</div>
                    @endif
                </td>
                <td class="c">{{ $item['hsn'] ?? '—' }}</td>
                <td class="c">{{ $qty }}</td>
                <td class="r">{{ number_format($rate, 2) }}</td>
                <td class="c">{{ number_format($taxPct, 0) }}%</td>
                <td class="r">{{ number_format($lineGst, 2) }}</td>
                <td class="r" style="font-weight:600;">{{ number_format($lineTotal, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
