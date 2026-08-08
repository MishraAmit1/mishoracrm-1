@php
    $existingAttachments = isset($contact) ? $contact->attachments : collect();
@endphp
<div class="cf-section">
    <div class="cf-section-header">
        <div class="cf-section-icon" style="background:#FAEEDA">
            <i class="ti ti-paperclip" style="font-size:16px;color:#BA7517" aria-hidden="true"></i>
        </div>
        <div>
            <div class="cf-section-title">Visiting Card / Documents</div>
            <div class="cf-section-sub">Business card, ID proof ya related files</div>
        </div>
    </div>

    <div class="cf-field span-full">
        <input type="file" name="attachments[]" class="cf-input" multiple
            accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx">
        <span class="cf-field-hint">Images, PDFs, Word or Excel — up to 10 MB each, max 5 files</span>
        @error('attachments') <span class="cf-field-error">{{ $message }}</span> @enderror
        @error('attachments.*') <span class="cf-field-error">{{ $message }}</span> @enderror

        @if($existingAttachments->isNotEmpty())
        <div class="emp-attach-list" style="margin-top:10px">
            @foreach($existingAttachments as $att)
            <div class="emp-attach-item">
                <a href="{{ $att->url }}" target="_blank" rel="noopener">
                    <i class="ti ti-paperclip" style="font-size:12px" aria-hidden="true"></i>
                    {{ $att->original_name }}
                </a>
                <button type="submit" form="del-contact-attach-{{ $att->id }}" class="attach-del" title="Delete">&times;</button>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
