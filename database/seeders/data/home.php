<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Home page content: sections, stats, process steps, home FAQs
|--------------------------------------------------------------------------
*/

return [

    // Keyed by App\Enums\HomeSectionKey value; sort follows the enum order.
    'sections' => [
        'hero' => [
            'eyebrow' => ['en' => 'Creative | Media | Digital — UAE', 'ar' => 'إبداع | إعلام | رقمي — الإمارات'],
            'title' => ['en' => 'Small Pixels. BIG IMPACT.', 'ar' => 'بيكسلات صغيرة. أثر كبير.'],
            'subtitle' => [
                'en' => 'Integrated branding, media production, digital marketing and web — all under one roof.',
                'ar' => 'هوية بصرية وإنتاج إعلامي وتسويق رقمي وتطوير مواقع، متكاملة تحت سقف واحد.',
            ],
            'cta_label' => ['en' => 'Start a project', 'ar' => 'ابدأ مشروعك'],
            'cta_url' => '/contact',
            // The headline is the tagline setting (no hero title); the reel button is automatic
            // (Settings → showreel link), so button 2 is a normal link.
            'cta2_label' => ['en' => 'Explore our services', 'ar' => 'استكشف خدماتنا'],
            'cta2_url' => '/services',
        ],
        'stats' => [
            'eyebrow' => ['en' => 'By the numbers', 'ar' => 'أرقامنا'],
            'title' => ['en' => 'Results that speak for themselves.', 'ar' => 'نتائج تتحدث عن نفسها.'],
        ],
        'services' => [
            'eyebrow' => ['en' => 'What we do', 'ar' => 'ماذا نقدّم'],
            'title' => ['en' => 'Six disciplines. One creative partner.', 'ar' => 'ستة تخصصات. شريك إبداعي واحد.'],
            'subtitle' => [
                'en' => 'From brand strategy to AI-powered content, we cover every stage of your brand’s growth.',
                'ar' => 'من استراتيجية العلامة التجارية إلى المحتوى المدعوم بالذكاء الاصطناعي، نغطي كل مرحلة من مراحل نمو علامتك.',
            ],
            'cta_label' => ['en' => 'Explore our services', 'ar' => 'استكشف خدماتنا'],
            'cta_url' => '/services',
        ],
        'process' => [
            'eyebrow' => ['en' => 'How we work', 'ar' => 'منهجية عملنا'],
            'title' => ['en' => 'A clear process, from brief to launch.', 'ar' => 'منهجية واضحة من الفكرة إلى الإطلاق.'],
        ],
        'clients' => [
            'eyebrow' => ['en' => 'Trusted by', 'ar' => 'شركاء النجاح'],
            'title' => ['en' => 'Brands we have worked with.', 'ar' => 'علامات تجارية عملنا معها.'],
        ],
        'about' => [
            'eyebrow' => ['en' => 'About Pixellap', 'ar' => 'عن بكسل لاب'],
            'title' => ['en' => 'A UAE agency built on craft and clarity.', 'ar' => 'وكالة إماراتية تقوم على الإتقان والوضوح.'],
            'subtitle' => [
                'en' => 'We are strategists, designers, filmmakers and marketers helping brands across the GCC look sharper, speak clearer and grow faster.',
                'ar' => 'فريق من الاستراتيجيين والمصممين وصنّاع الأفلام والمسوّقين، نساعد العلامات التجارية في دول الخليج على الظهور بصورة أكثر تميزاً، والتواصل برسائل أوضح، والنمو بوتيرة أسرع.',
            ],
            'cta_label' => ['en' => 'Learn more', 'ar' => 'اعرف المزيد'],
            'cta_url' => '/about',
        ],
        'faq' => [
            'eyebrow' => ['en' => 'FAQ', 'ar' => 'الأسئلة الشائعة'],
            'title' => ['en' => 'Questions, answered.', 'ar' => 'إجابات لأسئلتكم.'],
        ],
        'contact_cta' => [
            'eyebrow' => ['en' => 'Let’s talk', 'ar' => 'لنتحدث'],
            'title' => ['en' => 'Have a project in mind?', 'ar' => 'هل لديك مشروع تفكر فيه؟'],
            'subtitle' => [
                'en' => 'Tell us what you are building. We reply within 24 hours.',
                'ar' => 'أخبرنا عن مشروعك، وسنرد عليك خلال 24 ساعة.',
            ],
            'cta_label' => ['en' => 'Start a project', 'ar' => 'ابدأ مشروعك'],
            'cta_url' => '/contact',
        ],
    ],

    // PLACEHOLDER values — the client will replace them. Only "services" (43) is real.
    'stats' => [
        ['value' => 10, 'prefix' => null, 'suffix' => '+', 'label' => ['en' => 'Years of experience (placeholder)', 'ar' => 'سنوات من الخبرة (قيمة مؤقتة)']],
        ['value' => 250, 'prefix' => null, 'suffix' => '+', 'label' => ['en' => 'Projects delivered (placeholder)', 'ar' => 'مشروع منجز (قيمة مؤقتة)']],
        ['value' => 120, 'prefix' => null, 'suffix' => '+', 'label' => ['en' => 'Clients served (placeholder)', 'ar' => 'عميل وثق بنا (قيمة مؤقتة)']],
        ['value' => 43, 'prefix' => null, 'suffix' => null, 'label' => ['en' => 'Specialist services', 'ar' => 'خدمة متخصصة']],
    ],

    'process_steps' => [
        [
            'icon' => 'search',
            'title' => ['en' => 'Discover & Brief', 'ar' => 'الاكتشاف وتحديد المتطلبات'],
            'description' => [
                'en' => 'We learn about your business, audience and goals, then agree a clear brief, scope and timeline.',
                'ar' => 'نتعرّف على نشاطك التجاري وجمهورك وأهدافك، ثم نتفق على موجز واضح ونطاق عمل وجدول زمني محدد.',
            ],
        ],
        [
            'icon' => 'lightbulb',
            'title' => ['en' => 'Concept & Strategy', 'ar' => 'الفكرة والاستراتيجية'],
            'description' => [
                'en' => 'We shape the strategy and creative direction, and share concepts for your feedback before production begins.',
                'ar' => 'نضع الاستراتيجية والتوجه الإبداعي، ونعرض عليك المفاهيم الأولية لإبداء ملاحظاتك قبل بدء التنفيذ.',
            ],
        ],
        [
            'icon' => 'clapperboard',
            'title' => ['en' => 'Production', 'ar' => 'الإنتاج'],
            'description' => [
                'en' => 'Our designers, filmmakers and developers bring the work to life, with regular check-ins along the way.',
                'ar' => 'يتولى فريقنا من المصممين وصنّاع المحتوى والمطورين تنفيذ العمل، مع متابعة دورية معك في كل مرحلة.',
            ],
        ],
        [
            'icon' => 'rocket',
            'title' => ['en' => 'Launch & Grow', 'ar' => 'الإطلاق والنمو'],
            'description' => [
                'en' => 'We launch, measure performance and refine, so your brand keeps growing after go-live.',
                'ar' => 'نطلق العمل ونقيس الأداء ونواصل التحسين، لتستمر علامتك في النمو بعد الإطلاق.',
            ],
        ],
    ],

    // Home page FAQs (service_category_id = null)
    'faqs' => [
        [
            'question' => ['en' => 'What services does Pixellap offer?', 'ar' => 'ما الخدمات التي تقدمها بكسل لاب؟'],
            'answer' => [
                'en' => 'We offer six integrated disciplines: brand identity and design, media and content production, digital marketing, web design and development, creative and AI solutions, and training and workshops. You can work with us on a single project or as a full-service partner.',
                'ar' => 'نقدم ستة تخصصات متكاملة: بناء الهوية البصرية والتصميم، وإنتاج المحتوى المرئي والمسموع، والتسويق الرقمي، وتصميم المواقع الإلكترونية وتطويرها، والحلول الإبداعية المدعومة بالذكاء الاصطناعي، والتدريب وورش العمل. ويمكنك التعاون معنا في مشروع واحد أو اعتمادنا شريكاً متكاملاً.',
            ],
        ],
        [
            'question' => ['en' => 'How long does a typical project take?', 'ar' => 'كم يستغرق تنفيذ المشروع عادةً؟'],
            'answer' => [
                'en' => 'It depends on scope. A logo or landing page can take two to three weeks; a full brand identity or corporate website usually takes six to ten weeks. We agree a clear timeline before we start.',
                'ar' => 'يعتمد ذلك على نطاق العمل؛ فتصميم شعار أو صفحة هبوط قد يستغرق من أسبوعين إلى ثلاثة أسابيع، بينما تستغرق الهوية البصرية المتكاملة أو الموقع المؤسسي عادةً من ستة إلى عشرة أسابيع. ونتفق معك على جدول زمني واضح قبل البدء.',
            ],
        ],
        [
            'question' => ['en' => 'How do you price your work?', 'ar' => 'كيف تحددون تكلفة المشاريع؟'],
            'answer' => [
                'en' => 'Every project is quoted individually based on scope, deliverables and timeline. For ongoing work such as social media management, we offer monthly retainers. You will always receive a detailed proposal before any commitment.',
                'ar' => 'نُعدّ عرض سعر خاصاً لكل مشروع وفق نطاق العمل والمخرجات والجدول الزمني، ونوفر باقات شهرية للخدمات المستمرة مثل إدارة منصات التواصل الاجتماعي. وستتلقى دائماً عرضاً تفصيلياً قبل أي التزام.',
            ],
        ],
        [
            'question' => ['en' => 'Do you work in both Arabic and English?', 'ar' => 'هل تعملون باللغتين العربية والإنجليزية؟'],
            'answer' => [
                'en' => 'Yes. Our team creates content, copy and campaigns natively in Arabic and English, so your brand speaks naturally to every audience in the region.',
                'ar' => 'نعم، يُعدّ فريقنا المحتوى والنصوص والحملات باللغتين العربية والإنجليزية بإتقان، لتخاطب علامتك كل جمهور في المنطقة بأسلوب طبيعي ومؤثر.',
            ],
        ],
        [
            'question' => ['en' => 'Who do you work with?', 'ar' => 'مع من تعملون؟'],
            'answer' => [
                'en' => 'We partner with start-ups, SMEs, government entities and established brands across the UAE and the wider GCC, in sectors from real estate and hospitality to retail and technology.',
                'ar' => 'نتعاون مع الشركات الناشئة والصغيرة والمتوسطة والجهات الحكومية والعلامات التجارية الراسخة في دولة الإمارات ودول مجلس التعاون الخليجي، في قطاعات تشمل العقارات والضيافة والتجزئة والتكنولوجيا.',
            ],
        ],
        [
            'question' => ['en' => 'How do we get started?', 'ar' => 'كيف نبدأ العمل معاً؟'],
            'answer' => [
                'en' => 'Send us a message through the contact form or WhatsApp. We will arrange a short call to understand your goals, then share a proposal within a few working days.',
                'ar' => 'أرسل لنا رسالة عبر نموذج التواصل أو واتساب، وسنحدد معك مكالمة قصيرة لفهم أهدافك، ثم نرسل لك عرضاً مفصلاً خلال أيام عمل قليلة.',
            ],
        ],
    ],
];
