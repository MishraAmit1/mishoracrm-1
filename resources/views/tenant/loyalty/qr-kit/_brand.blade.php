<div class="brand">
    @if($tenant->logo)
    <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}"/>
    @else
    <div class="mark">{{ strtoupper(mb_substr($tenant->name, 0, 1)) }}</div>
    @endif
    <div class="name">{{ $tenant->name }}</div>
</div>
