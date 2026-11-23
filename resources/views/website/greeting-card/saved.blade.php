{{--
    "Your card" page. A social app's browser (Facebook/Messenger…) hands this page to the phone's
    real browser (Chrome/Safari), where download and share work. Standalone (no site layout / DB).
--}}
<!doctype html>
<html lang="bn">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>আপনার শুভেচ্ছা কার্ড</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('fonts/greeting-card/noto-sans-bengali.css') }}">
    <style>
        body { font-family: "Noto Sans Bengali", sans-serif; background: #f3f5f9; }
        .gc-wrap { max-width: 520px; margin: 0 auto; padding: 1rem; }
        .gc-card-img { width: 100%; max-height: 62vh; object-fit: contain; border-radius: 10px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, .12); background: #fff; }
        .gc-btn-red { background: #d71920; border-color: #d71920; color: #fff; }
        .gc-btn-red:hover, .gc-btn-red:focus { background: #b5141a; border-color: #b5141a; color: #fff; }
        .gc-logo { max-width: 90px; }
    </style>
</head>

<body>
    <main class="gc-wrap">
        <div class="text-center mb-3">
            <a href="{{ url('/') }}"><img class="gc-logo" src="{{ asset('website/img/logo/logo-transparent.png') }}" alt="Metro News"></a>
        </div>

        @if ($available)
            <h1 class="h5 text-center fw-bold mb-3">আপনার শুভেচ্ছা কার্ড তৈরি হয়েছে!</h1>

            <img class="gc-card-img d-block mx-auto mb-3" src="{{ $viewUrl }}" alt="আপনার শুভেচ্ছা কার্ড">

            @if ($inApp)
                {{-- Still inside a social app (the browser hand-off fell back here) --}}
                <div class="alert alert-warning small">
                    <i class="fa-solid fa-hand-pointer"></i> ছবির উপর <b>চেপে ধরে রাখুন</b>, তারপর
                    <b>Download image / Save image</b> চাপুন।
                </div>
            @endif

            <div class="d-grid gap-2">
                <a id="gcDownload" class="btn gc-btn-red btn-lg" href="{{ $downloadUrl }}" download="greeting-card.jpg">
                    <i class="fa-solid fa-download"></i> ডাউনলোড করুন
                </a>
                <button type="button" id="gcShare" class="btn btn-outline-primary btn-lg d-none">
                    <i class="fa-solid fa-share-nodes"></i> শেয়ার করুন
                </button>
                <a class="btn btn-link" href="{{ $cardPageUrl }}">নতুন কার্ড তৈরি করুন</a>
            </div>
            <p class="small text-muted text-center mt-2 mb-0" id="gcAutoNote"></p>
            <p class="small text-muted text-center mt-2">এই কার্ডটি ১ ঘণ্টা পর সার্ভার থেকে মুছে যাবে।</p>
        @else
            <div class="alert alert-info text-center">
                কার্ডটির মেয়াদ শেষ হয়ে গেছে (১ ঘণ্টা)। নতুন করে কার্ড তৈরি করুন।
            </div>
            <div class="d-grid">
                <a class="btn gc-btn-red btn-lg" href="{{ $cardPageUrl }}">কার্ড তৈরি করুন</a>
            </div>
        @endif
    </main>

    @if ($available)
        @php
            $jsConfig = [
                'viewUrl' => $viewUrl,
                'downloadUrl' => $downloadUrl,
                'trackUrl' => route('greeting.card.downloaded'),
                'inApp' => $inApp,
                'isAndroid' => $isAndroid,
            ];
        @endphp
        <script>
            (function () {
                const CFG = @json($jsConfig);
                const csrf = document.querySelector('meta[name="csrf-token"]').content;

                // Share sheet (WhatsApp, Facebook, Save to Photos…). The file is fetched up front
                // so the tap shares immediately (share needs a fresh user tap).
                let file = null;
                fetch(CFG.viewUrl)
                    .then((r) => r.ok ? r.blob() : null)
                    .then((blob) => {
                        if (!blob) return;
                        const f = new File([blob], 'greeting-card.jpg', { type: 'image/jpeg' });
                        if (navigator.canShare && navigator.canShare({ files: [f] })) {
                            file = f;
                            document.getElementById('gcShare').classList.remove('d-none');
                        }
                    })
                    .catch(() => {});

                document.getElementById('gcShare').addEventListener('click', () => {
                    if (!file) return;
                    navigator.share({ files: [file], title: document.title })
                        .then(() => fetch(CFG.trackUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            body: new URLSearchParams({ type: 'share' }),
                            keepalive: true,
                        }).catch(() => {}))
                        .catch(() => {}); // AbortError = share sheet closed
                });

                // Opened in Chrome on Android from a social app: start the download right away (once).
                if (CFG.isAndroid && !CFG.inApp) {
                    let started = false;
                    try {
                        const key = 'gc-auto-dl:' + location.pathname;
                        started = sessionStorage.getItem(key) === '1';
                        sessionStorage.setItem(key, '1');
                    } catch (e) { /* storage blocked: still start once */ }
                    if (!started) {
                        document.getElementById('gcAutoNote').textContent =
                            'ডাউনলোড শুরু হয়েছে। না হলে "ডাউনলোড করুন" চাপুন।';
                        setTimeout(() => { window.location.href = CFG.downloadUrl; }, 600);
                    }
                }
            })();
        </script>
    @endif
</body>

</html>
