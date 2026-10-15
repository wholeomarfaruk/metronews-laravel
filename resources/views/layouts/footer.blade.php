<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    .site-footer {
        --mf-text: #000;
        --mf-line: #b4b4b4;
        --mf-dot: #b4b4b4;
        --mf-link: #5b21d6;
        --mf-bg: #fff;

        width: 100%;
        background: var(--mf-bg);
        color: var(--mf-text);
        font-family: 'Hind Siliguri', sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    .site-footer *,
    .site-footer *::before,
    .site-footer *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    /* ---------- top section ---------- */
    .site-footer .footer-top {
        max-width: 1400px;
        margin: 0 auto;
        padding: 48px 50px 66px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        column-gap: 60px;
        row-gap: 40px;
    }

    .site-footer .footer-brand h2 {
        font-family: 'Hind Siliguri', sans-serif;
        font-size: 25px;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 26px;
        color: var(--mf-text);
    }

    .site-footer .label {
        font-size: 18px;
        font-weight: 700;
        line-height: 1.4;
    }

    .site-footer .editor-name {
        font-size: 16px;
        font-weight: 400;
        line-height: 1.4;
        margin-bottom: 26px;
    }

    .site-footer .about {
        font-size: 16px;
        font-weight: 400;
        line-height: 1.5;
        max-width: 480px;
        margin-bottom: 22px;
    }

    /* social icons */
    .site-footer .social {
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    .site-footer .social a {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 24px;
        text-decoration: none;
        transition: transform .2s ease, background-color .2s ease;
    }

    .site-footer .social a:hover {
        transform: translateY(-3px);
        background: #f2f2f2;
    }

    .site-footer .social a:focus-visible {
        outline: 2px solid var(--mf-link);
        outline-offset: 2px;
    }

    .site-footer .social .fb { color: #1877F2; }
    .site-footer .social .li { color: #0A66C2; }
    .site-footer .social .yt { color: #FF0000; }
    .site-footer .social .xx { color: #000000; }
    .site-footer .social .tt { color: #000000; }

    .site-footer .social .ig i {
        background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: #E4405F;
    }

    /* right column */
    .site-footer .footer-info .block { margin-bottom: 30px; }
    .site-footer .footer-info .block:last-child { margin-bottom: 0; }
    .site-footer .footer-info .label { margin-bottom: 2px; }

    .site-footer .footer-info p {
        font-size: 16px;
        font-weight: 400;
        line-height: 1.5;
        word-break: break-word;
    }

    .site-footer .footer-info a {
        color: inherit;
        text-decoration: none;
    }

    .site-footer .footer-info a:hover { text-decoration: underline; }

    /* ---------- nav bar (borders) ---------- */
    .site-footer .footer-nav {
        border-top: 1px solid var(--mf-line);
        border-bottom: 1px solid var(--mf-line);
    }

    .site-footer .footer-nav ul {
        list-style: none;
        max-width: 1400px;
        margin: 0 auto;
        padding: 12px 20px;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        gap: 6px 0;
    }

    .site-footer .footer-nav li {
        display: flex;
        align-items: center;
    }

    .site-footer .footer-nav li + li::before {
        content: "";
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--mf-dot);
        margin: 0 18px;
        flex: none;
    }

    .site-footer .footer-nav a {
        color: var(--mf-text);
        text-decoration: none;
        font-size: 16px;
        font-weight: 400;
        padding: 4px 0;
    }

    .site-footer .footer-nav a:hover { text-decoration: underline; }

    /* ---------- bottom bar ---------- */
    .site-footer .footer-bottom {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px 50px 22px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 6px 30px;
        font-size: 16px;
        line-height: 1.5;
    }

    .site-footer .footer-bottom a {
        color: var(--mf-link);
        text-decoration: underline;
    }

    .site-footer .footer-bottom a:hover { opacity: .8; }

    /* ---------- tablets ---------- */
    @media (max-width: 991px) {
        .site-footer .footer-top {
            padding: 40px 30px 48px;
            column-gap: 30px;
        }

        .site-footer .footer-bottom { padding: 18px 30px 22px; }
    }

    /* ---------- mobiles ---------- */
    @media (max-width: 767px) {
        .site-footer .footer-top {
            grid-template-columns: 1fr;
            padding: 32px 20px 36px;
            row-gap: 32px;
        }

        .site-footer .footer-brand h2 { margin-bottom: 20px; }
        .site-footer .about { max-width: 100%; }
        .site-footer .footer-nav ul { padding: 10px 16px; }

        .site-footer .footer-nav li + li::before {
            margin: 0 12px;
            width: 8px;
            height: 8px;
        }

        .site-footer .footer-bottom {
            flex-direction: column;
            justify-content: center;
            text-align: center;
            padding: 18px 20px 24px;
        }
    }
</style>

<footer class="site-footer">

    <div class="footer-top">
        {{-- left column --}}
        <div class="footer-brand">
            <h2>মেট্রো নিউজ</h2>

            <div class="label">সম্পাদক ও প্রকাশক</div>
            <div class="editor-name">মোঃ মিজানুর রাহমান</div>

            <p class="about">
                @if (setting('general.footer_text'))
                    {!! nl2br(e(setting('general.footer_text'))) !!}
                @else
                    মেট্রো নিউজ বাংলাদেশের একটি ডিজিটাল নিউজ মিডিয়া আউটলেট, যা অনলাইন ও মাল্টিমিডিয়া সেক্টরজুড়ে কাজ করে, আমরা সত্য ও নির্ভুল সংবাদ প্রকাশে প্রতিশ্রুতিবদ্ধ।
                @endif
            </p>

            <ul class="social" aria-label="Social media">
                @if (setting('social.facebook'))
                    <li><a class="fb" href="{{ setting('social.facebook') }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a></li>
                @endif
                @if (setting('social.instagram'))
                    <li><a class="ig" href="{{ setting('social.instagram') }}" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a></li>
                @endif
                @if (setting('social.linkedin'))
                    <li><a class="li" href="{{ setting('social.linkedin') }}" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fa-brands fa-linkedin"></i></a></li>
                @endif
                @if (setting('social.youtube'))
                    <li><a class="yt" href="{{ setting('social.youtube') }}" target="_blank" rel="noopener" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a></li>
                @endif
                @if (setting('social.twitter'))
                    <li><a class="xx" href="{{ setting('social.twitter') }}" target="_blank" rel="noopener" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a></li>
                @endif
                @if (setting('social.tiktok'))
                    <li><a class="tt" href="{{ setting('social.tiktok') }}" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a></li>
                @endif
            </ul>
        </div>

        {{-- right column --}}
        <div class="footer-info">
            @if (setting('general.phone') || setting('general.secondary_phone'))
                <div class="block">
                    <div class="label">জরুরী প্রয়োজনে</div>
                    <p>
                        @if (setting('general.phone'))
                            <a href="tel:{{ setting('general.phone') }}">{{ setting('general.phone') }}</a>
                        @endif
                        @if (setting('general.phone') && setting('general.secondary_phone')), @endif
                        @if (setting('general.secondary_phone'))
                            <a href="tel:{{ setting('general.secondary_phone') }}">{{ setting('general.secondary_phone') }}</a>
                        @endif
                    </p>
                </div>
            @endif
            @if (setting('general.email'))
                <div class="block">
                    <div class="label">ইমেইল ঠিকানা</div>
                    <p><a href="mailto:{{ setting('general.email') }}">{{ setting('general.email') }}</a></p>
                </div>
            @endif
            @if (setting('general.address'))
                <div class="block">
                    <div class="label">কর্পোরেট অফিস</div>
                    <p>{{ setting('general.address') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- nav with borders --}}
    <nav class="footer-nav" aria-label="Footer">
        <ul>
            <li><a href="#">আমাদের সম্পর্কে</a></li>
            <li><a href="#">বিজ্ঞাপন</a></li>
            <li><a href="#">সার্কুলেশন</a></li>
            <li><a href="#">নীতি ও শর্ত</a></li>
            <li><a href="#">যোগাযোগ</a></li>
            <li><a href="#">নিউজলেটার</a></li>
        </ul>
    </nav>

    {{-- bottom bar --}}
    <div class="footer-bottom">
        <div>© {{ date('Y') }}. All right Reserved Developed By <a href="https://www.facebook.com/alimuzahid.dev" target="_blank" rel="noopener">Ali Muzahid</a></div>
        <div>ওয়েবসাইটের কোনো লেখা, ছবি, ভিডিও অনুমতি ছাড়া ব্যবহার বেআইনি।</div>
    </div>

</footer>
