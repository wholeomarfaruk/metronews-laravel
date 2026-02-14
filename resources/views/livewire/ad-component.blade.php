<div class="">
    @if ($ad && $ad->status == 1 && $ad->expire_at > now())
        <a class="ad d-flex justify-content-center align-items-center mb-2 py-1" href="{{ $ad->link }}" target="_blank" >
            <img src="{{ $ad->image }}" style="max-height: {{ $height ?? 'inherit' }}"  alt="">
        </a>
    @endif
</div>
