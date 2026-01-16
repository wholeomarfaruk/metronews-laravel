


<div>
    @if ($ad && $ad->status == 1 && $ad->expire_at > now())
    <div class="ad d-flex justify-content-center align-items-center mb-2">
        <a class="d-block w-100" href="{{ $ad->link }}" target="_blank">
    <img src="{{ $ad->image }}" class="w-100" alt="">
    </a>
    </div>
    @endif
</div>

