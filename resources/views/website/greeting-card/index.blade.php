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
        // Temporary card upload for social app browsers
        'fileUrl' => route('greeting.card.file.store'),
        // Download/share file name, without extension
        'fileName' => config('greeting_card.download_name'),
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

            {{-- Shown by JS inside Facebook/Messenger/other social app browsers --}}
            <div id="gcInAppNotice" class="alert alert-info d-none d-flex flex-wrap align-items-center gap-2 small">
                <span class="flex-grow-1">
                    <i class="fa-solid fa-circle-info"></i>
                    আপনি ফেসবুক/মেসেঞ্জার অ্যাপের ভেতর থেকে পেজটি খুলেছেন। সহজে ডাউনলোডের জন্য পেজটি
                    <b id="gcInAppBrowserName">ব্রাউজারে</b> খুলুন।
                    <span id="gcIosTip" class="d-none">উপরের <b>•••</b> মেনু থেকে <b>Open in browser / Safari</b> চাপুন।</span>
                </span>
                <a id="gcOpenChrome" href="#" class="btn btn-sm btn-primary d-none">
                    <i class="fa-brands fa-chrome"></i> Chrome-এ খুলুন
                </a>
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
                                    <small class="text-muted" id="gcPrivacyNote">আপনার ছবি কোথাও আপলোড হয় না</small>
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

                            <div class="d-grid gap-2">
                                <button type="button" class="btn gc-btn-red btn-lg" id="gcDownload" disabled>
                                    <i class="fa-solid fa-download"></i> ডাউনলোড করুন
                                </button>
                                {{-- Shown by JS only where the browser can share image files --}}
                                <button type="button" class="btn btn-outline-primary btn-lg d-none" id="gcShare" disabled>
                                    <i class="fa-solid fa-share-nodes"></i> শেয়ার করুন
                                </button>
                            </div>
                            <div class="small text-danger mt-2 d-none" id="gcError"></div>

                            {{-- Social app browsers (Facebook/Messenger…): the download is handed to the phone's
                                 browser; these are the fallbacks if the app blocks that --}}
                            <div id="gcResult" class="d-none mt-3 text-center">
                                <div class="alert alert-warning small py-2 mb-2" id="gcResultMsg"></div>
                                <div class="d-grid gap-2 mb-2">
                                    <a id="gcResultOpen" href="#" class="btn gc-btn-red d-none">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> ব্রাউজারে খুলে ডাউনলোড করুন
                                    </a>
                                    <a id="gcResultDownload" href="#" class="btn btn-outline-secondary d-none" download="{{ config('greeting_card.download_name') }}.jpg">
                                        <i class="fa-solid fa-file-arrow-down"></i> এখানেই ডাউনলোডের চেষ্টা করুন
                                    </a>
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
                let renderVersion = 0; // bumps on every change; the share cache uses it to know the card is stale
                function render() {
                    renderVersion++;
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
                    $('gcShare').disabled = false;
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

                // ---------- Download & share ----------
                // Social app browsers (Facebook, Messenger, Instagram, TikTok…) ignore <a download>
                // and can't save blob: images. Keep in sync with GreetingCardEvent::isInAppBrowser().
                const UA = navigator.userAgent;
                const IS_ANDROID = /Android/i.test(UA);
                const IS_IOS = /iPhone|iPad|iPod/i.test(UA);
                // Big social apps whose browsers are known to hand intent:// / x-safari links to the OS.
                const KNOWN_SOCIAL_APP = /FBAN|FBAV|FB_IAB|FBIOS|Messenger|Instagram|Line\/|Twitter|LinkedInApp|musical_ly|BytedanceWebview|Snapchat|Pinterest/i.test(UA);
                const IN_APP_BROWSER = KNOWN_SOCIAL_APP
                    || (IS_ANDROID && UA.includes('; wv)'))
                    || (IS_IOS && !UA.includes('Safari/'));
                const SHARE_SUPPORTED = (() => {
                    try {
                        return !!(navigator.canShare && navigator.canShare({ files: [new File(['x'], 'x.png', { type: 'image/png' })] }));
                    } catch (e) { return false; }
                })();
                const shareBtn = $('gcShare');
                let resultUrl = null;

                /**
                 * URL that asks a social app's browser to open `url` in the phone's real browser.
                 * Android: intent:// to Chrome, falling back to the URL itself (works in most app browsers).
                 * iOS: x-safari-https:// (some Facebook/Instagram versions block it, so callers keep a fallback).
                 */
                function externalBrowserUrl(url) {
                    const u = new URL(url, location.href);
                    if (IS_ANDROID) {
                        return `intent://${u.host}${u.pathname}${u.search}#Intent;scheme=${u.protocol.replace(':', '')};`
                            + `package=com.android.chrome;S.browser_fallback_url=${encodeURIComponent(u.href)};end`;
                    }
                    return IS_IOS ? 'x-safari-' + u.href : u.href;
                }

                // Tell in-app users up front that a real browser works best (before they make the card).
                if (IN_APP_BROWSER) {
                    $('gcInAppNotice').classList.remove('d-none');
                    $('gcPrivacyNote').textContent = 'অ্যাপে ডাউনলোডের জন্য তৈরি কার্ডটি ১ ঘণ্টা সার্ভারে থাকে, তারপর মুছে যায়';
                    const open = $('gcOpenChrome');
                    if (IS_ANDROID) {
                        open.href = externalBrowserUrl(location.href);
                        open.classList.remove('d-none');
                        $('gcInAppBrowserName').textContent = 'Chrome-এ';
                    } else if (IS_IOS) {
                        open.href = externalBrowserUrl(location.href);
                        open.innerHTML = '<i class="fa-brands fa-safari"></i> Safari-তে খুলুন';
                        open.classList.remove('d-none');
                        $('gcIosTip').classList.remove('d-none');
                        $('gcInAppBrowserName').textContent = 'Safari-তে';
                    }
                }
                if (SHARE_SUPPORTED) shareBtn.classList.remove('d-none');

                // Admin stats: 'download' or 'share'. Fire-and-forget: never blocks or breaks the user's action.
                function trackEvent(type) {
                    try {
                        fetch(CFG.trackUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': CFG.csrf, 'Accept': 'application/json' },
                            credentials: 'same-origin',
                            keepalive: true,
                            body: new URLSearchParams({ type }),
                        }).catch(() => {});
                    } catch (e) { /* ignore */ }
                }

                // Draw synchronously (with the name font loaded) so an export never misses a pending frame.
                async function renderNow() {
                    await loadNameFont();
                    if (raf) { cancelAnimationFrame(raf); raf = 0; }
                    ctx.clearRect(0, 0, W, H);
                    drawPhoto();
                    drawFrame();
                    drawName();
                }

                // null if the canvas can't be exported (e.g. template served from another domain without CORS)
                function cardBlob(type, quality) {
                    return new Promise((resolve) => {
                        try { canvas.toBlob(resolve, type, quality); } catch (e) { resolve(null); }
                    });
                }

                const fail = () => showError('কার্ড তৈরি করা যায়নি, পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।');

                // ---- Normal browser: save the PNG directly ----
                async function directDownload() {
                    await renderNow();
                    const blob = await cardBlob('image/png');
                    if (!blob) return fail();
                    trackEvent('download');
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = `${CFG.fileName}.png`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    setTimeout(() => URL.revokeObjectURL(a.href), 2000);
                }

                // ---- Social app browser: upload, then hand the download to the phone's real browser ----
                async function uploadCard(blob) {
                    const body = new FormData();
                    body.append('card', blob, 'card.jpg');
                    const res = await fetch(CFG.fileUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CFG.csrf, 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        body,
                    });
                    if (!res.ok) throw new Error('upload ' + res.status);
                    return res.json(); // { page_url, view_url, download_url }
                }

                async function inAppDownload() {
                    const box = $('gcResult'), img = $('gcResultImg'), msg = $('gcResultMsg'),
                        dl = $('gcResultDownload'), openExt = $('gcResultOpen');
                    const setMsg = (html) => { msg.innerHTML = html; };

                    dl.classList.add('d-none');
                    openExt.classList.add('d-none');
                    setMsg('<i class="fa-solid fa-spinner fa-spin"></i> কার্ড প্রস্তুত হচ্ছে…');
                    box.classList.remove('d-none');

                    await renderNow();
                    // JPEG keeps the upload small (≈0.5 MB vs 2–4 MB PNG) for server upload limits.
                    const blob = await cardBlob('image/jpeg', 0.92);
                    if (!blob) { box.classList.add('d-none'); return fail(); }

                    if (resultUrl) URL.revokeObjectURL(resultUrl);
                    resultUrl = URL.createObjectURL(blob);
                    img.src = resultUrl; // instant preview while uploading
                    box.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    let data;
                    try {
                        data = await uploadCard(blob);
                    } catch (e) {
                        setMsg('কার্ডটি প্রস্তুত করা যায়নি। ইন্টারনেট সংযোগ দেখে আবার "ডাউনলোড করুন" চাপুন, '
                            + 'অথবা উপরের নির্দেশনা অনুযায়ী পেজটি ব্রাউজারে খুলুন।');
                        return;
                    }

                    trackEvent('download'); // counted only once the card is really available
                    img.src = data.view_url; // real URL: long-press "Save image" works
                    dl.href = data.download_url;
                    dl.classList.remove('d-none');
                    const ext = externalBrowserUrl(data.page_url);
                    openExt.href = ext;
                    openExt.classList.remove('d-none');

                    const manualMsg = 'নিচের <b>"ব্রাউজারে খুলে ডাউনলোড করুন"</b> চাপুন, '
                        + 'অথবা ছবির উপর <b>চেপে ধরে</b> <b>Download / Save image</b> চাপুন।'
                        + (IS_IOS ? ' iPhone-এ উপরের <b>•••</b> মেনু → <b>Open in external browser</b> চাপলেও হবে।' : '');

                    // Only auto-redirect in the big social apps: an unknown app's WebView may not understand
                    // intent:// and would replace this page with an error page (losing the card).
                    if (!KNOWN_SOCIAL_APP) {
                        setMsg('<i class="fa-solid fa-circle-check"></i> কার্ড প্রস্তুত! ' + manualMsg);
                        return;
                    }

                    // Try to open the card page in the external browser automatically. If the app blocks it,
                    // this page stays visible, so after a moment show the manual options instead.
                    setMsg('<i class="fa-solid fa-spinner fa-spin"></i> ডাউনলোডের জন্য ব্রাউজার খোলা হচ্ছে…');
                    let left = false;
                    const onVisibility = () => { if (document.hidden) left = true; };
                    document.addEventListener('visibilitychange', onVisibility);
                    window.location.href = ext;
                    await new Promise((resolve) => setTimeout(resolve, 1500));
                    document.removeEventListener('visibilitychange', onVisibility);
                    setMsg(left
                        ? '<i class="fa-solid fa-circle-check"></i> কার্ডটি ব্রাউজারে খোলা হয়েছে, সেখান থেকে ডাউনলোড বা শেয়ার করুন। '
                            + 'কোনো সমস্যা হলে নিচের অপশনগুলো ব্যবহার করুন।'
                        : 'স্বয়ংক্রিয়ভাবে ব্রাউজার খোলা যায়নি। ' + manualMsg);
                }

                // ---- Share sheet (WhatsApp, Facebook, Save to Photos…) where supported ----
                // Preparing the file can outlast the tap's "user activation"; then the share is refused
                // with NotAllowedError, so the file is cached and a second tap shares instantly.
                let shareCache = null;
                async function shareCard() {
                    if (!shareCache || shareCache.version !== renderVersion) {
                        await renderNow();
                        const blob = await cardBlob('image/png');
                        if (!blob) return fail();
                        shareCache = {
                            version: renderVersion,
                            file: new File([blob], `${CFG.fileName}.png`, { type: 'image/png' }),
                        };
                    }
                    try {
                        await navigator.share({ files: [shareCache.file], title: document.title });
                        trackEvent('share');
                    } catch (e) {
                        if (e && e.name === 'NotAllowedError') {
                            showError('কার্ড প্রস্তুত হয়েছে, শেয়ার করতে আবার "শেয়ার করুন" চাপুন।');
                        } // AbortError = the user closed the share sheet
                    }
                }

                // One action at a time: repeated taps during an upload/redirect would start duplicates.
                let busy = false;
                async function runExclusive(action) {
                    if (busy) return;
                    busy = true;
                    dlBtn.disabled = shareBtn.disabled = true;
                    showError('');
                    try {
                        await action();
                    } catch (e) {
                        fail();
                    } finally {
                        busy = false;
                        dlBtn.disabled = shareBtn.disabled = !state.img;
                    }
                }
                dlBtn.addEventListener('click', () => runExclusive(IN_APP_BROWSER ? inAppDownload : directDownload));
                shareBtn.addEventListener('click', () => runExclusive(shareCard));

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
