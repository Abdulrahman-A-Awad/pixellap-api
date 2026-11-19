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
        'services' => ['Services', 'خدماتنا'],
        'contact' => ['Get in touch', 'تواصل معنا'],
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
        'optional' => ['Optional', 'اختياري'],
        'invalid_phone' => [
            'Use digits, spaces and + ( ) - only.',
            'يُرجى استخدام الأرقام والمسافات والرموز + ( ) - فقط.',
        ],
        'message_too_short' => [
            'Please tell us a little more (at least 10 characters).',
            'يُرجى كتابة المزيد من التفاصيل (10 أحرف على الأقل).',
        ],
        'too_long' => ['This text is too long.', 'هذا النص أطول من المسموح به.'],
        'too_many_requests' => [
            'You have sent several messages in a short time. Please try again in a minute.',
            'لقد أرسلت عدة رسائل خلال وقت قصير. يُرجى المحاولة مرة أخرى بعد دقيقة.',
        ],
        'sending' => ['Sending...', 'جارٍ الإرسال...'],
        'success_title' => ['Message sent', 'تم إرسال رسالتك'],
        'send_another' => ['Send another message', 'إرسال رسالة أخرى'],
    ],

    'contact' => [
        'form_title' => ['Send us a message', 'أرسل لنا رسالة'],
        'info_title' => ['Contact details', 'بيانات التواصل'],
        'email' => ['Email', 'البريد الإلكتروني'],
        'phone' => ['Phone', 'الهاتف'],
        'whatsapp' => ['Chat on WhatsApp', 'تواصل عبر واتساب'],
        'address' => ['Address', 'العنوان'],
        'response_time' => ['Response time', 'مدة الرد'],
        'map_title' => ['Our location on the map', 'موقعنا على الخريطة'],
        // Prefilled message when arriving from "Request this service" ({item} = service title).
        'interested_in' => ["I'm interested in: {item}", 'أودّ الاستفسار عن: {item}'],
    ],

    // Services index + category pages.
    'services' => [
        // Plural forms by Intl.PluralRules category ({count} = number). English uses one/other.
        'count_zero' => ['{count} services', 'لا توجد خدمات'],
        'count_one' => ['{count} service', 'خدمة واحدة'],
        'count_two' => ['{count} services', 'خدمتان'],
        'count_few' => ['{count} services', '{count} خدمات'],
        'count_many' => ['{count} services', '{count} خدمة'],
        'count_other' => ['{count} services', '{count} خدمة'],
        'overview' => ['Overview', 'نظرة عامة'],
        'list_title' => ['What we offer', 'ما نقدّمه'],
        'index_label' => ['Services in this category', 'خدمات هذا القسم'],
        'jump_to' => ['Jump to a service', 'انتقل إلى خدمة'],
        'request' => ['Request this service', 'اطلب هذه الخدمة'],
        'faq_title' => ['Frequently asked questions', 'الأسئلة الشائعة'],
        'explore_other' => ['Explore other services', 'استكشف خدماتنا الأخرى'],
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
        'skip_to_content' => ['Skip to content', 'الانتقال إلى المحتوى'],
        'main_navigation' => ['Main navigation', 'القائمة الرئيسية'],
        'language' => ['Change language', 'تغيير اللغة'],
        'loading' => ['Loading...', 'جارٍ التحميل...'],
        'error_title' => ['Something went wrong', 'حدث خطأ ما'],
        'error_text' => [
            'We could not load this page. Please try again.',
            'تعذّر تحميل هذه الصفحة. يُرجى المحاولة مرة أخرى.',
        ],
        'try_again' => ['Try again', 'إعادة المحاولة'],
        'breadcrumb' => ['Breadcrumb', 'مسار التنقل'],
        'previous' => ['Previous', 'السابق'],
        'next' => ['Next', 'التالي'],
    ],
];
