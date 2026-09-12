<div style="max-width:520px;margin:0 auto;font-family:Arial,Helvetica,sans-serif;color:#33475B">
  <div style="background:#FF7A59;padding:20px 24px;border-radius:8px 8px 0 0">
    <h2 style="color:#fff;margin:0;font-size:18px">New sales enquiry</h2>
  </div>
  <div style="background:#fff;padding:24px;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px">
    <table style="width:100%;border-collapse:collapse;font-size:14px;color:#374151">
      <tr><td style="padding:6px 0;color:#6b7280;width:120px">Name</td><td style="padding:6px 0">{{ $enquiry->name }}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Company</td><td style="padding:6px 0">{{ $enquiry->company }}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Email</td><td style="padding:6px 0">{{ $enquiry->email }}</td></tr>
      @if($enquiry->phone)
      <tr><td style="padding:6px 0;color:#6b7280">Phone</td><td style="padding:6px 0">{{ $enquiry->phone }}</td></tr>
      @endif
      @if($enquiry->team_size)
      <tr><td style="padding:6px 0;color:#6b7280">Team size</td><td style="padding:6px 0">{{ $enquiry->team_size }}</td></tr>
      @endif
    </table>
    @if($enquiry->message)
    <p style="margin:16px 0 0;padding:14px;background:#f8fafc;border-radius:6px;line-height:1.6;white-space:pre-wrap">{{ $enquiry->message }}</p>
    @endif
    <p style="color:#9ca3af;font-size:12px;margin-top:20px;border-top:1px solid #f1f5f9;padding-top:16px">
      Submitted {{ $enquiry->created_at->format('d M Y, H:i') }} · Manage in Super Admin → Sales Enquiries.
    </p>
  </div>
</div>
