@extends('layouts.website')

@section('meta_title', $card['title'])
@section('meta_description', $card['description'])
@if ($card['template_url'])
    @section('meta_og_image', $card['template_url'])
@endif

@php
    $W = (int) $card['template_width'];
    $H = (int) $card['template_height'];
    $jsConfig = [
        'templateUrl' => $card['template_url'],
        'backgroundUrl' => $card['background_url'],
        'width' => $W,
        'height' => $H,
        'nameX' => (float) $card['name_x'],
        'nameY' => (float) $card['name_y'],
        'nameMaxWidth' => (float) $card['name_max_width'],
        'fontFamily' => $card['font_family'],
        'fontWeight' => $card['font_weight'],
        'color' => $card['name_color'],
        // Used only by the built-in design when no template PNG is uploaded.
        'logoUrl' => asset('website/img/logo/logo-transparent.png'),
        'siteTitle' => $card['site_title'],
        'siteHost' => $card['site_host'],
        // Download counter for the admin stats page
        'trackUrl' => route('greeting.card.downloaded'),
        'csrf' => csrf_token(),
    ];
    $defaultSize = (int) $card['font_size'];
@endphp

@push('styles')
    @foreach ($card['font_css'] as $fontCss)
        <link rel="stylesheet" href="{{ $fontCss }}">
    @endforeach
    <style>
        .gc-page { --gc-red: #d71920; --gc-navy: #1b4a8c; }
        .gc-hero {
            background: linear-gradient(120deg, var(--gc-navy), #12305c 55%, var(--gc-red));
            color: #fff; border-radius: 12px; padding: 1.25rem 1.5rem;
        }
        .gc-hero h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 .25rem; }
        .gc-hero p { margin: 0; opacity: .9; }
        .gc-panel { background: #fff; border: 1px solid #e6e6e6; border-radius: 12px; padding: 1.25rem; }
        .gc-label { font-weight: 600; color: var(--gc-navy); margin-bottom: .35rem; display: flex; justify-content: space-between; }
        .gc-label .gc-val { color: #6c757d; font-weight: 500; font-variant-numeric: tabular-nums; }
        .gc-drop {
            border: 2px dashed #c9d3e3; border-radius: 10px; padding: 1.25rem; text-align: center;
            cursor: pointer; background: #f7f9fc; transition: border-color .15s, background .15s;
        }
        .gc-drop:hover, .gc-drop.is-over { border-color: var(--gc-red); background: #fff5f5; }
        .gc-drop i { font-size: 1.75rem; color: var(--gc-red); }
        .gc-page .form-range::-webkit-slider-thumb { background: var(--gc-red); }
        .gc-page .form-range::-moz-range-thumb { background: var(--gc-red); }
        .gc-canvas-wrap { position: relative; max-width: 480px; margin: 0 auto; }
        /* width/height auto + both max limits keeps the card's aspect ratio while fitting the screen */
        #gcCanvas {
            width: auto; height: auto; max-width: 100%; max-height: 75vh; display: block; margin: 0 auto;
            border-radius: 8px; box-shadow: 0 6px 24px rgba(0, 0, 0, .12); cursor: pointer; background: #eef1f6;
        }
        /* Only capture touches once there is a photo to move, so the page still scrolls before that */
        #gcCanvas.has-photo { touch-action: none; cursor: grab; }
        #gcCanvas.is-dragging { cursor: grabbing; }
        #gcResult img { max-height: 70vh; }
        @media (max-width: 991.98px) {
            /* Phones/tablets: keep the card short enough that the sliders stay in reach */
            #gcCanvas { max-height: 60vh; }
        }
        @media (max-width: 575.98px) {
            .gc-hero { padding: 1rem; }
            .gc-hero h1 { font-size: 1.25rem; }
            .gc-hero p { font-size: .9rem; }
            .gc-panel { padding: .9rem; }
            .gc-drop { padding: 1rem .75rem; }
        }
        .gc-hint { font-size: .85rem; color: #6c757d; text-align: center; margin-top: .5rem; }
        .gc-btn-red { background: var(--gc-red); border-color: var(--gc-red); color: #fff; }
        .gc-btn-red:hover, .gc-btn-red:focus { background: #b5141a; border-color: #b5141a; color: #fff; }
        .gc-btn-red:disabled { background: #e48b8e; border-color: #e48b8e; }
        .gc-step { display: inline-flex; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: var(--gc-red);
            color: #fff; font-size: .8rem; align-items: center; justify-content: center; margin-right: .4rem; }
        .gc-controls[aria-disabled="true"] { opacity: .5; pointer-events: none; }
    </style>
@endpush

@section('content')
    <section class="gc-page">
        <div class="wrapper my-3">
            <div class="gc-hero mb-3">
                <h1>{{ $card['title'] }}</h1>
                @if ($card['description'])
                    <p>{{ $card['description'] }}</p>
                @endif
            </div>

                <div class="row g-3">
                    {{-- Controls --}}
                    <div class="col-lg-5 order-2 order-lg-1">
                        <div class="gc-panel">
                            {{-- 1. Photo --}}
                            <div class="mb-3">
                                <div class="gc-label"><span><span class="gc-step">১</span>আপনার ছবি</span></div>
                                <label class="gc-drop d-block mb-0" id="gcDrop" for="gcFile">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <div class="fw-semibold mt-1" id="gcDropText">ছবি নির্বাচন করুন বা এখানে টেনে আনুন</div>
                                    <small class="text-muted">আপনার ছবি কোথাও আপলোড হয় না</small>
                                </label>
                                <input type="file" id="gcFile" accept="image/*" class="d-none">
                            </div>

                            <div class="gc-controls mb-3" id="gcPhotoControls" aria-disabled="true">
                                <div class="gc-label">
                                    <span><i class="fa-solid fa-magnifying-glass-plus me-1"></i> ছবি জুম</span>
                                    <span class="gc-val" id="gcZoomVal">100%</span>
                                </div>
                                <input type="range" class="form-range" id="gcZoom" min="0.3" max="4" step="0.01" value="1">
                                <div class="d-flex gap-2 mt-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="gcRotate">
                                        <i class="fa-solid fa-rotate-right"></i> ঘোরান
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="gcReset">
                                        <i class="fa-solid fa-arrows-to-dot"></i> রিসেট
                                    </button>
                                </div>
                                <div class="small text-muted mt-2">
                                    <i class="fa-solid fa-hand"></i> কার্ডের উপর ছবি টেনে সরিয়ে ফ্রেমে নিখুঁতভাবে বসান। মাউস হুইল বা দুই আঙুল দিয়েও জুম করা যায়।
                                </div>
                            </div>

                            <hr>

                            {{-- 2. Name --}}
                            <div class="mb-3">
                                <label class="gc-label" for="gcName"><span><span class="gc-step">২</span>আপনার নাম</span></label>
                                <input type="text" class="form-control form-control-lg" id="gcName" maxlength="60"
                                    placeholder="আপনার নাম লিখুন" autocomplete="name">
                            </div>
                            <div class="mb-3">
                                <div class="gc-label">
                                    <span><i class="fa-solid fa-text-height me-1"></i> নামের ফন্ট সাইজ</span>
                                    <span class="gc-val" id="gcFontVal">{{ $defaultSize }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-font-step="-2" aria-label="ছোট করুন">A-</button>
                                    <input type="range" class="form-range flex-grow-1" id="gcFont"
                                        min="{{ max(10, (int) round($defaultSize * 0.4)) }}"
                                        max="{{ (int) round($defaultSize * 2) }}" step="1" value="{{ $defaultSize }}">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-font-step="2" aria-label="বড় করুন">A+</button>
                                </div>
                                <div class="small text-muted mt-1 d-none" id="gcFitNote">
                                    নামটি বড় হওয়ায় ফ্রেমে আঁটাতে ছোট করা হয়েছে।
                                </div>
                            </div>

                            <hr>

                            <button type="button" class="btn gc-btn-red btn-lg w-100" id="gcDownload" disabled>
                                <i class="fa-solid fa-download"></i> ডাউনলোড করুন
                            </button>
                            <div class="small text-danger mt-2 d-none" id="gcError"></div>

                            {{-- Facebook/Messenger in-app browsers block downloads: show the image to long-press instead --}}
                            <div id="gcResult" class="d-none mt-3 text-center">
                                <div class="alert alert-warning small py-2 mb-2">
                                    <i class="fa-solid fa-hand-pointer"></i> ছবিটির উপর চেপে ধরে রাখুন, তারপর <b>Save / Download image</b> চাপুন।
                                </div>
                                <img id="gcResultImg" class="img-fluid rounded border" alt="আপনার শুভেচ্ছা কার্ড">
                            </div>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div class="col-lg-7 order-1 order-lg-2">
                        <div class="gc-panel">
                            <div class="gc-canvas-wrap">
                                <canvas id="gcCanvas" width="{{ $W }}" height="{{ $H }}"
                                    aria-label="শুভেচ্ছা কার্ডের প্রিভিউ"></canvas>
                            </div>
                            <div class="gc-hint">প্রিভিউ — ডাউনলোডের ছবি {{ $W }}×{{ $H }} পিক্সেল</div>
                        </div>
                    </div>
                </div>
        </div>
    </section>
@endsection

    @push('scripts')
        <script>
            (function () {
                const CFG = @json($jsConfig);
                const W = CFG.width, H = CFG.height;
                const FONT = `"${CFG.fontFamily}", "Noto Sans Bengali", serif`;

                const canvas = document.getElementById('gcCanvas');
                const ctx = canvas.getContext('2d');
                const $ = (id) => document.getElementById(id);
                const fileInput = $('gcFile'), drop = $('gcDrop'), zoomEl = $('gcZoom'), fontEl = $('gcFont'),
                    nameEl = $('gcName'), dlBtn = $('gcDownload');

                const state = { img: null, zoom: 1, x: 0, y: 0, rot: 0 };
                let frame = null, logo = null, bg = null, objectUrl = null;

                function loadImage(src) {
                    return new Promise((resolve) => {
                        const im = new Image();
                        im.onload = () => resolve(im);
                        im.onerror = () => resolve(null);
                        im.src = src;
                    });
                }

                // ---------- Drawing ----------
                function baseScale() {
                    const im = state.img;
                    const sideways = state.rot % 180 !== 0;
                    const iw = sideways ? im.naturalHeight : im.naturalWidth;
                    const ih = sideways ? im.naturalWidth : im.naturalHeight;
                    return Math.max(W / iw, H / ih); // "cover" fit at zoom = 1
                }

                // Background layer: "cover"-fit behind the photo. Visible where the photo is
                // transparent (cut-out PNG) or doesn't reach (zoomed out / moved).
                function drawBackground() {
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, W, H);
                    if (!bg) return false;
                    const s = Math.max(W / bg.naturalWidth, H / bg.naturalHeight);
                    const bw = bg.naturalWidth * s, bh = bg.naturalHeight * s;
                    ctx.drawImage(bg, (W - bw) / 2, (H - bh) / 2, bw, bh);
                    return true;
                }

                function drawPhoto() {
                    const hasBg = drawBackground();
                    if (!state.img) {
                        if (!hasBg) {
                            ctx.fillStyle = '#e9edf3';
                            ctx.fillRect(0, 0, W, H);
                        }
                        // Hint text, outlined in white so it stays readable over a background image
                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.font = `700 ${Math.round(W * 0.05)}px "Noto Sans Bengali", sans-serif`;
                        ctx.lineJoin = 'round';
                        ctx.lineWidth = Math.round(W * 0.012);
                        ctx.strokeStyle = 'rgba(255,255,255,.9)';
                        ctx.strokeText('এখানে ক্লিক করে ছবি দিন', W / 2, H * 0.3);
                        ctx.fillStyle = '#6b7a90';
                        ctx.fillText('এখানে ক্লিক করে ছবি দিন', W / 2, H * 0.3);
                        ctx.restore();
                        return;
                    }
                    const s = baseScale() * state.zoom;
                    ctx.save();
                    ctx.translate(W / 2 + state.x, H / 2 + state.y);
                    ctx.rotate(state.rot * Math.PI / 180);
                    ctx.scale(s, s);
                    ctx.drawImage(state.img, -state.img.naturalWidth / 2, -state.img.naturalHeight / 2);
                    ctx.restore();
                }

                function drawName() {
                    const name = nameEl.value.trim();
                    $('gcFitNote').classList.add('d-none');
                    if (!name) return;
                    let size = +fontEl.value;
                    const maxW = W * CFG.nameMaxWidth / 100;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = `${CFG.fontWeight} ${size}px ${FONT}`;
                    const w = ctx.measureText(name).width;
                    if (w > maxW) { // shrink long names so they stay inside the name box
                        size = Math.max(8, Math.floor(size * maxW / w));
                        ctx.font = `${CFG.fontWeight} ${size}px ${FONT}`;
                        $('gcFitNote').classList.remove('d-none');
                    }
                    ctx.fillStyle = CFG.color;
                    ctx.fillText(name, W * CFG.nameX / 100, H * CFG.nameY / 100);
                    ctx.restore();
                }

                // ---------- Built-in design (used when no template PNG is uploaded; 1080×1350) ----------
                const UI_FONT = '"Noto Sans Bengali", sans-serif';

                function roundRect(x, y, w, h, r) {
                    ctx.beginPath();
                    ctx.moveTo(x + r, y);
                    ctx.arcTo(x + w, y, x + w, y + h, r);
                    ctx.arcTo(x + w, y + h, x, y + h, r);
                    ctx.arcTo(x, y + h, x, y, r);
                    ctx.arcTo(x, y, x + w, y, r);
                    ctx.closePath();
                }

                function wave(top, color) {
                    ctx.beginPath();
                    ctx.moveTo(0, top + 75);
                    ctx.bezierCurveTo(300, top - 10, 700, top + 160, W, top);
                    ctx.lineTo(W, H);
                    ctx.lineTo(0, H);
                    ctx.closePath();
                    ctx.fillStyle = color;
                    ctx.fill();
                }

                function drawBuiltInFrame() {
                    wave(830, '#d71920');
                    const g = ctx.createLinearGradient(0, 860, 0, H);
                    g.addColorStop(0, '#1b4a8c');
                    g.addColorStop(1, '#0d2647');
                    wave(860, g);

                    ctx.strokeStyle = 'rgba(255,255,255,.85)';
                    ctx.lineWidth = 6;
                    ctx.strokeRect(18, 18, W - 36, H - 36);

                    if (logo) { // logo badge, top-right
                        const bw = 200, bh = 170, bx = W - bw - 40, by = 40;
                        ctx.save();
                        ctx.shadowColor = 'rgba(0,0,0,.25)';
                        ctx.shadowBlur = 16;
                        roundRect(bx, by, bw, bh, 18);
                        ctx.fillStyle = '#fff';
                        ctx.fill();
                        ctx.restore();
                        const r = Math.min((bw - 24) / logo.naturalWidth, (bh - 24) / logo.naturalHeight);
                        const lw = logo.naturalWidth * r, lh = logo.naturalHeight * r;
                        ctx.drawImage(logo, bx + (bw - lw) / 2, by + (bh - lh) / 2, lw, lh);
                    }

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'alphabetic';
                    ctx.fillStyle = '#fff';
                    ctx.font = `700 76px ${UI_FONT}`;
                    ctx.fillText(CFG.siteTitle, W / 2, 1010);
                    ctx.fillStyle = '#ffd54a';
                    ctx.font = `600 40px ${UI_FONT}`;
                    ctx.fillText('আন্তরিক শুভেচ্ছা ও শুভকামনা', W / 2, 1068);

                    // Name box (the name itself is drawn by drawName at 50% / 85.9%)
                    const ng = ctx.createLinearGradient(220, 0, 860, 0);
                    ng.addColorStop(0, 'rgba(13,38,71,.95)');
                    ng.addColorStop(1, 'rgba(120,14,20,.95)');
                    roundRect(220, 1105, 640, 110, 16);
                    ctx.fillStyle = ng;
                    ctx.fill();

                    ctx.fillStyle = 'rgba(255,255,255,.75)';
                    ctx.font = `500 26px ${UI_FONT}`;
                    ctx.fillText(CFG.siteHost, W / 2, 1300);
                    ctx.restore();
                }

                function drawFrame() {
                    if (CFG.templateUrl) { if (frame) ctx.drawImage(frame, 0, 0, W, H); }
                    else drawBuiltInFrame();
                }

                let raf = 0;
                function render() {
                    if (raf) return;
                    raf = requestAnimationFrame(() => {
                        raf = 0;
                        ctx.clearRect(0, 0, W, H);
                        drawPhoto();
                        drawFrame();
                        drawName();
                        $('gcZoomVal').textContent = Math.round(state.zoom * 100) + '%';
                        $('gcFontVal').textContent = fontEl.value;
                    });
                }

                // ---------- Photo loading ----------
                function showError(msg) {
                    const err = $('gcError');
                    err.textContent = msg;
                    err.classList.toggle('d-none', !msg);
                }

                async function setFile(file) {
                    showError('');
                    if (!file || !file.type.startsWith('image/')) {
                        showError('অনুগ্রহ করে একটি ছবি নির্বাচন করুন।');
                        return;
                    }
                    const url = URL.createObjectURL(file);
                    const im = await loadImage(url);
                    if (!im) {
                        URL.revokeObjectURL(url);
                        showError('ছবিটি খোলা যায়নি, অন্য একটি ছবি চেষ্টা করুন।');
                        return;
                    }
                    if (objectUrl) URL.revokeObjectURL(objectUrl);
                    objectUrl = url;
                    state.img = im;
                    resetPhoto();
                    $('gcDropText').textContent = 'অন্য ছবি নির্বাচন করুন';
                    $('gcPhotoControls').setAttribute('aria-disabled', 'false');
                    dlBtn.disabled = false;
                    canvas.classList.add('has-photo');
                }

                function resetPhoto() {
                    state.zoom = 1; state.x = 0; state.y = 0; state.rot = 0;
                    zoomEl.value = 1;
                    render();
                }

                // Zoom, keeping canvas point (cx, cy) fixed (defaults to the centre).
                function setZoom(z, cx = W / 2, cy = H / 2) {
                    z = Math.min(+zoomEl.max, Math.max(+zoomEl.min, z));
                    const k = z / state.zoom;
                    state.x = (cx - W / 2) - ((cx - W / 2) - state.x) * k;
                    state.y = (cy - H / 2) - ((cy - H / 2) - state.y) * k;
                    state.zoom = z;
                    zoomEl.value = z;
                    render();
                }

                fileInput.addEventListener('change', (e) => { setFile(e.target.files[0]); fileInput.value = ''; });
                ['dragenter', 'dragover'].forEach((t) => drop.addEventListener(t, (e) => { e.preventDefault(); drop.classList.add('is-over'); }));
                ['dragleave', 'drop'].forEach((t) => drop.addEventListener(t, (e) => { e.preventDefault(); drop.classList.remove('is-over'); }));
                drop.addEventListener('drop', (e) => setFile(e.dataTransfer.files[0]));

                zoomEl.addEventListener('input', () => setZoom(+zoomEl.value));
                $('gcRotate').addEventListener('click', () => { state.rot = (state.rot + 90) % 360; render(); });
                $('gcReset').addEventListener('click', resetPhoto);

                // Google Fonts splits Bengali/Latin into unicode-range subsets, so ask for the
                // glyphs actually used — otherwise the canvas may draw with a fallback font.
                function loadNameFont() {
                    if (!document.fonts) return Promise.resolve();
                    const text = (nameEl.value.trim() || '') + 'অআকখ Abc';
                    return document.fonts.load(`${CFG.fontWeight} 40px ${FONT}`, text).catch(() => {});
                }

                // ---------- Name controls ----------
                nameEl.addEventListener('input', () => { render(); loadNameFont().then(render); });
                fontEl.addEventListener('input', render);
                document.querySelectorAll('[data-font-step]').forEach((b) => b.addEventListener('click', () => {
                    fontEl.value = Math.min(+fontEl.max, Math.max(+fontEl.min, +fontEl.value + +b.dataset.fontStep));
                    render();
                }));

                // ---------- Drag / pinch / wheel on the canvas ----------
                const pointers = new Map();
                let pinchStart = null;

                function toCanvas(e) {
                    const r = canvas.getBoundingClientRect();
                    return { x: (e.clientX - r.left) * (W / r.width), y: (e.clientY - r.top) * (H / r.height) };
                }

                // Tap the empty card to pick a photo. Uses "click" (not pointerdown) so that
                // swiping over the card on a phone scrolls the page instead of opening the picker.
                canvas.addEventListener('click', () => { if (!state.img) fileInput.click(); });

                canvas.addEventListener('pointerdown', (e) => {
                    if (!state.img) return;
                    canvas.setPointerCapture(e.pointerId);
                    pointers.set(e.pointerId, toCanvas(e));
                    canvas.classList.add('is-dragging');
                    if (pointers.size === 2) {
                        const [a, b] = [...pointers.values()];
                        pinchStart = { dist: Math.hypot(a.x - b.x, a.y - b.y), zoom: state.zoom };
                    }
                });

                canvas.addEventListener('pointermove', (e) => {
                    if (!pointers.has(e.pointerId)) return;
                    const prev = pointers.get(e.pointerId);
                    const cur = toCanvas(e);
                    pointers.set(e.pointerId, cur);
                    if (pointers.size === 1) {
                        state.x += cur.x - prev.x;
                        state.y += cur.y - prev.y;
                        render();
                    } else if (pointers.size === 2 && pinchStart) {
                        const [a, b] = [...pointers.values()];
                        const dist = Math.hypot(a.x - b.x, a.y - b.y);
                        setZoom(pinchStart.zoom * (dist / pinchStart.dist), (a.x + b.x) / 2, (a.y + b.y) / 2);
                    }
                });

                function endPointer(e) {
                    pointers.delete(e.pointerId);
                    if (pointers.size < 2) pinchStart = null;
                    if (pointers.size === 0) canvas.classList.remove('is-dragging');
                }
                canvas.addEventListener('pointerup', endPointer);
                canvas.addEventListener('pointercancel', endPointer);

                canvas.addEventListener('wheel', (e) => {
                    if (!state.img) return;
                    e.preventDefault();
                    const p = toCanvas(e);
                    setZoom(state.zoom * (e.deltaY < 0 ? 1.06 : 1 / 1.06), p.x, p.y);
                }, { passive: false });

                // ---------- Download ----------
                // Facebook / Messenger / Instagram in-app browsers ignore <a download>.
                const IN_APP_BROWSER = /FBAN|FBAV|FB_IAB|FBIOS|Messenger|Instagram|Line\//i.test(navigator.userAgent);
                let resultUrl = null;

                // Count the download for admin stats. Fire-and-forget: never blocks or breaks the download.
                function trackDownload() {
                    try {
                        fetch(CFG.trackUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': CFG.csrf, 'Accept': 'application/json' },
                            credentials: 'same-origin',
                            keepalive: true,
                        }).catch(() => {});
                    } catch (e) { /* ignore */ }
                }

                dlBtn.addEventListener('click', async () => {
                    showError('');
                    await loadNameFont();
                    // Draw synchronously so the export never misses a pending frame.
                    if (raf) { cancelAnimationFrame(raf); raf = 0; }
                    ctx.clearRect(0, 0, W, H);
                    drawPhoto();
                    drawFrame();
                    drawName();

                    const safe = (nameEl.value.trim() || 'card').replace(/[\\/:*?"<>|\s]+/g, '-').slice(0, 40);
                    const fail = () => showError('ডাউনলোড করা যায়নি, পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।');
                    try {
                        canvas.toBlob((blob) => {
                            if (!blob) return fail();
                            trackDownload();
                            if (IN_APP_BROWSER) {
                                const img = $('gcResultImg');
                                if (resultUrl) URL.revokeObjectURL(resultUrl);
                                resultUrl = URL.createObjectURL(blob);
                                img.src = resultUrl;
                                $('gcResult').classList.remove('d-none');
                                $('gcResult').scrollIntoView({ behavior: 'smooth', block: 'start' });
                                return;
                            }
                            const a = document.createElement('a');
                            a.href = URL.createObjectURL(blob);
                            a.download = `greeting-card-${safe}.png`;
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            setTimeout(() => URL.revokeObjectURL(a.href), 2000);
                        }, 'image/png');
                    } catch (e) {
                        fail(); // e.g. template served from another domain without CORS
                    }
                });

                // ---------- Init ----------
                render();
                Promise.all([
                    CFG.templateUrl
                        ? loadImage(CFG.templateUrl).then((im) => { frame = im; })
                        : loadImage(CFG.logoUrl).then((im) => { logo = im; }),
                    CFG.backgroundUrl ? loadImage(CFG.backgroundUrl).then((im) => { bg = im; }) : null,
                    CFG.templateUrl || !document.fonts ? null
                        : document.fonts.load(`700 76px ${UI_FONT}`, 'আন্তরিক শুভেচ্ছা ' + CFG.siteTitle).catch(() => {}),
                    loadNameFont(),
                ]).then(render);
                if (document.fonts) document.fonts.ready.then(render);
            })();
        </script>
    @endpush
