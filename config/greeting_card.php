<?php

return [
    // Fonts selectable for the name on the greeting card. They are self-hosted in
    // public/fonts/greeting-card/{slug}.css — after adding or changing a font here, run:
    //   php artisan greeting-card:fonts
    // key = CSS font-family name, value = Google Fonts "family=" query part (used only for downloading).
    'fonts' => [
        'Noto Serif Bengali' => 'Noto+Serif+Bengali:wght@400;700',
        'Noto Sans Bengali' => 'Noto+Sans+Bengali:wght@400;700',
        'Hind Siliguri' => 'Hind+Siliguri:wght@400;700',
        'Tiro Bangla' => 'Tiro+Bangla',
        'Baloo Da 2' => 'Baloo+Da+2:wght@400;700',
    ],

    // Defaults used until the admin saves the greeting card settings.
    'defaults' => [
        'enabled' => true,
        'template' => null,      // front frame PNG (transparent where the photo shows)
        'background' => null,    // optional image drawn behind the user's photo
        'template_width' => 1080,
        'template_height' => 1350,
        'title' => 'শুভেচ্ছা কার্ড তৈরি করুন',
        'description' => 'আপনার ছবি ও নাম দিয়ে কার্ড বানিয়ে ডাউনলোড করুন, তারপর ফেসবুকে শেয়ার করুন।',
        'name_x' => 50,          // % of template width (centre of the name)
        'name_y' => 73,          // % of template height (centre of the name)
        'name_max_width' => 50,  // % of template width; longer names shrink to fit
        'font_size' => 54,       // px in template pixels
        'font_family' => 'Noto Serif Bengali',
        'font_weight' => '400',
        'name_color' => '#ffffff',
    ],
];
