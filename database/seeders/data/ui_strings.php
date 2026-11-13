<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Interface strings for the frontend
|--------------------------------------------------------------------------
| group => [ key (without group prefix) => [en, ar] ]
| Stored as "group.key", e.g. "nav.services".
*/

return [
    'nav' => [
        'home' => ['Home', 'الرئيسية'],
        'about' => ['About', 'من نحن'],
        'services' => ['Services', 'خدماتنا'],
        'contact' => ['Contact', 'تواصل معنا'],
    ],

    'actions' => [
        'start_project' => ['Start a project', 'ابدأ مشروعك'],
        'watch_reel' => ['Watch the reel', 'شاهد العرض التعريفي'],
        'explore_services' => ['Explore our services', 'استكشف خدماتنا'],
        'learn_more' => ['Learn more', 'اعرف المزيد'],
        'send_message' => ['Send message', 'إرسال الرسالة'],
        'back_home' => ['Back to home', 'العودة إلى الرئيسية'],
        'view_all' => ['View all', 'عرض الكل'],
    ],

    'footer' => [
        'rights' => ['All rights reserved.', 'جميع الحقوق محفوظة.'],
        'quick_links' => ['Quick links', 'روابط سريعة'],
        'follow_us' => ['Follow us', 'تابعنا'],
        'working_hours' => ['Working hours', 'ساعات العمل'],
        'vat' => ['VAT No.', 'الرقم الضريبي'],
    ],

    'form' => [
        'name' => ['Full name', 'الاسم الكامل'],
        'email' => ['Email address', 'البريد الإلكتروني'],
        'phone' => ['Phone number', 'رقم الهاتف'],
        'company' => ['Company', 'الشركة'],
        'service' => ['Service of interest', 'الخدمة المطلوبة'],
        'message' => ['Tell us about your project', 'أخبرنا عن مشروعك'],
        'success' => [
            'Thank you. Your message has been sent and we will be in touch shortly.',
            'شكراً لتواصلك معنا. تم إرسال رسالتك وسنتواصل معك قريباً.',
        ],
        'error' => ['Something went wrong. Please try again.', 'حدث خطأ ما. يُرجى المحاولة مرة أخرى.'],
        'required' => ['This field is required.', 'هذا الحقل مطلوب.'],
        'invalid_email' => ['Please enter a valid email address.', 'يُرجى إدخال بريد إلكتروني صحيح.'],
        'select_service' => ['Select a service', 'اختر الخدمة'],
    ],

    'misc' => [
        // Label of the switcher shows the OTHER language.
        'lang_switch_label' => ['العربية', 'English'],
        'theme_light' => ['Light mode', 'الوضع الفاتح'],
        'theme_dark' => ['Dark mode', 'الوضع الداكن'],
        'scroll' => ['Scroll', 'مرّر للأسفل'],
        'not_found_title' => ['Page not found', 'الصفحة غير موجودة'],
        'not_found_text' => [
            'The page you are looking for has moved or no longer exists.',
            'الصفحة التي تبحث عنها نُقلت أو لم تعد متوفرة.',
        ],
        'menu' => ['Menu', 'القائمة'],
        'close' => ['Close', 'إغلاق'],
    ],
];
