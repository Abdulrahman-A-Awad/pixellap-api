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
        'view_all_services' => ['View all services', 'عرض جميع الخدمات'],
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
        'service' => ['Service needed', 'الخدمة المطلوبة'],
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
        // Service chips: "Other" sends no category.
        'other' => ['Other', 'أخرى'],
        'char_count' => ['{count} of {max} characters', 'عدد الأحرف: {count} من {max}'],
        'error_summary' => ['Please check the highlighted fields:', 'يُرجى مراجعة الحقول التالية:'],
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
        // Quick contact cards (contact page).
        'quick_title' => ['Quick contact', 'تواصل سريع'],
        'chat_title' => ['Chat with us', 'تحدّث معنا'],
        'email_title' => ['Write to us', 'راسلنا'],
        'call_title' => ['Call us', 'اتصل بنا'],
        // "What happens next" (contact page).
        'next_title' => ['What happens next', 'ماذا يحدث بعد ذلك؟'],
        'next_1_title' => ['We review your message', 'نراجع رسالتك'],
        'next_1_text' => [
            'A member of our team reads every request carefully.',
            'يطّلع أحد أعضاء فريقنا على كل طلب بعناية.',
        ],
        'next_2_title' => ['We contact you within 24 hours', 'نتواصل معك خلال 24 ساعة'],
        'next_2_text' => [
            'We reply by email or phone to understand your goals.',
            'نردّ عليك عبر البريد الإلكتروني أو الهاتف لفهم أهدافك.',
        ],
        'next_3_title' => ['You receive a tailored proposal', 'تتلقّى عرضاً مصمّماً لك'],
        'next_3_text' => [
            'A clear scope, timeline and price, built around your project.',
            'نطاق عمل واضح وجدول زمني وتكلفة، مبنية حول مشروعك.',
        ],
        // Short FAQ on the contact page.
        'faq_title' => ['Quick answers', 'إجابات سريعة'],
        'faq_more' => ['More questions', 'المزيد من الأسئلة'],
        // Live opening status in the contact info card ({time} / {day} are localized by the website).
        'open_now' => ['Open now', 'مفتوح الآن'],
        'closed_now' => ['Closed', 'مغلق الآن'],
        'closes_at' => ['Closes at {time}', 'يُغلق الساعة {time}'],
        'opens_today' => ['Opens today at {time}', 'يفتح اليوم الساعة {time}'],
        'opens_tomorrow' => ['Opens tomorrow at {time}', 'يفتح غداً الساعة {time}'],
        'opens_at' => ['Opens {day} at {time}', 'يفتح يوم {day} الساعة {time}'],
        'uae_time' => ['UAE time', 'بتوقيت الإمارات'],
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

    // FAQ help card (home + category pages).
    'faq' => [
        'help_title' => ["Didn't find your answer?", 'لم تجد إجابتك؟'],
        'help_text' => [
            'Our team is happy to help. Send us your question and we will get back to you shortly.',
            'يسعد فريقنا مساعدتك. أرسل لنا سؤالك وسنعود إليك في أقرب وقت.',
        ],
        'help_cta' => ['Ask us directly', 'اسألنا مباشرة'],
    ],

    // Floating WhatsApp chat widget. {page} = title of the page the visitor is on.
    'whatsapp' => [
        'open' => ['Chat with us on WhatsApp', 'تحدّث معنا عبر واتساب'],
        'tooltip' => ['Need help? Chat with us', 'تحتاج إلى مساعدة؟ تحدّث معنا'],
        'replies' => ['Typically replies within an hour', 'نردّ عادةً خلال ساعة'],
        'greeting' => [
            'Hi there! How can we help with your project today?',
            'مرحباً! كيف يمكننا مساعدتك في مشروعك اليوم؟',
        ],
        'start' => ['Start chat', 'ابدأ المحادثة'],
        'prefill' => [
            'Hello, I would like to ask about a project. (Page: {page})',
            'مرحباً، أودّ الاستفسار عن مشروع. (الصفحة: {page})',
        ],
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
