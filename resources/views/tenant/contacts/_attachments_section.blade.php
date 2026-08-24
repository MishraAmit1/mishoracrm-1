@php
    $existingAttachments = isset($contact) ? $contact->attachments : collect();
@endphp
<div class="cf-section">
    <div class="cf-section-header">
        <div class="cf-section-icon" style="background:var(--amber-dim)">
            <i class="ti ti-paperclip" style="font-size:16px;color:var(--amber)" aria-hidden="true"></i>
        </div>
        <div>
            <div class="cf-section-title">Visiting Card / Documents</div>
            <div class="cf-section-sub">Business card, ID proof ya related files</div>
        </div>
    </div>

    <div class="cf-field span-full">
        @include('tenant.contacts._file_dropzone', [
            'name'             => 'attachments[]',
            'existing'         => $existingAttachments,
            'deleteFormPrefix' => 'del-contact-attach',
        ])
        @error('attachments') <span class="cf-field-error">{{ $message }}</span> @enderror
        @error('attachments.*') <span class="cf-field-error">{{ $message }}</span> @enderror
    </div>
</div>
