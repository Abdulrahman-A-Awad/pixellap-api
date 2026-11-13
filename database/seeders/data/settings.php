<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Site settings
|--------------------------------------------------------------------------
| Values starting with "TODO" are placeholders — replace them in the admin
| panel before launch. Seeding never overwrites a setting that already exists.
|
| type: text | translatable | json | boolean
*/

return [
    // General
    ['key' => 'site_name', 'group' => 'general', 'type' => 'text', 'value' => 'Pixellap'],
    ['key' => 'tagline', 'group' => 'general', 'type' => 'translatable', 'value' => [
        'en' => 'Small Pixels. BIG IMPACT.',
        'ar' => 'بيكسلات صغيرة. أثر كبير.',
    ]],
    ['key' => 'showreel_url', 'group' => 'general', 'type' => 'text', 'value' => ''],

    // Contact
    ['key' => 'email', 'group' => 'contact', 'type' => 'text', 'value' => 'TODO: hello@pixellap.ae'],
    ['key' => 'phone', 'group' => 'contact', 'type' => 'text', 'value' => 'TODO: +971 4 000 0000'],
    ['key' => 'whatsapp', 'group' => 'contact', 'type' => 'text', 'value' => 'TODO: +971500000000'],
    ['key' => 'address', 'group' => 'contact', 'type' => 'translatable', 'value' => [
        'en' => 'TODO: Office address, Dubai, United Arab Emirates',
        'ar' => 'TODO: عنوان المكتب، دبي، الإمارات العربية المتحدة',
    ]],
    ['key' => 'map_embed_url', 'group' => 'contact', 'type' => 'text', 'value' => ''],
    ['key' => 'working_hours', 'group' => 'contact', 'type' => 'translatable', 'value' => [
        'en' => 'Monday – Friday, 9:00 am – 6:00 pm',
        'ar' => 'من الاثنين إلى الجمعة، 9:00 صباحاً – 6:00 مساءً',
    ]],
    ['key' => 'vat_number', 'group' => 'contact', 'type' => 'text', 'value' => 'TODO: 100000000000000'],
    ['key' => 'response_time', 'group' => 'contact', 'type' => 'translatable', 'value' => [
        'en' => 'We reply within 24 hours.',
        'ar' => 'نرد على جميع الاستفسارات خلال 24 ساعة.',
    ]],

    // Social
    ['key' => 'socials', 'group' => 'social', 'type' => 'json', 'value' => [
        'instagram' => '',
        'linkedin' => '',
        'tiktok' => '',
        'youtube' => '',
        'x' => '',
        'behance' => '',
    ]],

    // SEO
    ['key' => 'seo_default_title', 'group' => 'seo', 'type' => 'translatable', 'value' => [
        'en' => 'Pixellap — Creative, Media & Digital Agency in the UAE',
        'ar' => 'Pixellap — وكالة إبداعية للإعلام والتسويق الرقمي في الإمارات',
    ]],
    ['key' => 'seo_default_description', 'group' => 'seo', 'type' => 'translatable', 'value' => [
        'en' => 'Pixellap brings brand identity, media production, digital marketing, web development and AI-powered content together under one roof for businesses across the UAE and GCC.',
        'ar' => 'تجمع Pixellap بناء الهوية البصرية والإنتاج الإعلامي والتسويق الرقمي وتطوير المواقع والمحتوى المدعوم بالذكاء الاصطناعي تحت سقف واحد، لخدمة الشركات في الإمارات ودول مجلس التعاون الخليجي.',
    ]],
];
