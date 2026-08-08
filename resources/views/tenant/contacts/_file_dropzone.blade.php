@php
    $existing = $existing ?? collect();
    $extIconMap = [
        'pdf'  => 'ti-file-type-pdf',
        'doc'  => 'ti-file-type-doc', 'docx' => 'ti-file-type-doc',
        'xls'  => 'ti-file-type-xls', 'xlsx' => 'ti-file-type-xls',
    ];
@endphp
<div class="dz" data-dropzone>
    <input type="file" name="{{ $name }}" class="dz-input" multiple
        accept="{{ $accept ?? '.jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx' }}">

    <div class="dz-drop" data-dropzone-trigger>
        <div class="dz-icon"><i class="ti ti-cloud-upload" aria-hidden="true"></i></div>
        <div class="dz-text"><strong>Click to upload</strong> or drag &amp; drop</div>
        <div class="dz-hint">{{ $hint ?? 'Images, PDF, Word or Excel — up to 10 MB each, max 5 files' }}</div>
    </div>

    <div class="dz-preview" data-dropzone-preview></div>

    @if($existing->isNotEmpty())
    <div class="dz-existing">
        <div class="dz-existing-label">Uploaded ({{ $existing->count() }})</div>
        @foreach($existing as $att)
        @php $ext = strtolower(pathinfo($att->original_name, PATHINFO_EXTENSION)); @endphp
        <div class="dz-item">
            <div class="dz-item-icon {{ $att->isImage() ? 'dz-item-thumb' : '' }}">
                @if($att->isImage())
                <img src="{{ $att->url }}" alt="">
                @else
                <i class="ti {{ $extIconMap[$ext] ?? 'ti-file' }}" aria-hidden="true"></i>
                @endif
            </div>
            <div class="dz-item-meta">
                <a href="{{ $att->url }}" target="_blank" rel="noopener" class="dz-item-name">{{ $att->original_name }}</a>
                <div class="dz-item-size">{{ $att->file_size_human }}</div>
            </div>
            <button type="submit" form="{{ $deleteFormPrefix }}-{{ $att->id }}" class="dz-item-remove" title="Delete">&times;</button>
        </div>
        @endforeach
    </div>
    @endif
</div>
