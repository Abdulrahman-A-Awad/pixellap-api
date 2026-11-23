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
                'en' => '<p>We offer six integrated disciplines under one roof:</p><ul><li>Brand identity and design</li><li>Media and content production</li><li>Digital marketing</li><li>Web design and development</li><li>Creative and AI solutions</li><li>Training and workshops</li></ul><p>You can work with us on a single project or as a full-service partner.</p>',
                'ar' => '<p>نقدم ستة تخصصات متكاملة تحت سقف واحد:</p><ul><li>بناء الهوية البصرية والتصميم</li><li>إنتاج المحتوى المرئي والمسموع</li><li>التسويق الرقمي</li><li>تصميم المواقع الإلكترونية وتطويرها</li><li>الحلول الإبداعية المدعومة بالذكاء الاصطناعي</li><li>التدريب وورش العمل</li></ul><p>ويمكنك التعاون معنا في مشروع واحد أو اعتمادنا شريكاً متكاملاً.</p>',
            ],
        ],
        [
            'question' => ['en' => 'How long does a typical project take?', 'ar' => 'كم يستغرق تنفيذ المشروع عادةً؟'],
            'answer' => [
                'en' => '<p>It depends on the scope. Typical timelines:</p><ul><li><strong>Logo or landing page:</strong> two to three weeks</li><li><strong>Full brand identity or corporate website:</strong> six to ten weeks</li></ul><p>We agree a clear timeline with you before we start.</p>',
                'ar' => '<p>يعتمد ذلك على نطاق العمل، وهذه هي المدد المعتادة:</p><ul><li><strong>شعار أو صفحة هبوط:</strong> من أسبوعين إلى ثلاثة أسابيع</li><li><strong>هوية بصرية متكاملة أو موقع مؤسسي:</strong> من ستة إلى عشرة أسابيع</li></ul><p>ونتفق معك على جدول زمني واضح قبل البدء.</p>',
            ],
        ],
        [
            'question' => ['en' => 'How do you price your work?', 'ar' => 'كيف تحددون تكلفة المشاريع؟'],
            'answer' => [
                'en' => '<p>Every project is quoted individually.</p><ul><li><strong>Projects:</strong> priced on scope, deliverables and timeline</li><li><strong>Ongoing work</strong> such as social media management: a monthly retainer</li><li><strong>No surprises:</strong> you receive a detailed proposal before any commitment</li></ul>',
                'ar' => '<p>نُعدّ عرض سعر خاصاً لكل مشروع.</p><ul><li><strong>المشاريع:</strong> تُسعَّر وفق نطاق العمل والمخرجات والجدول الزمني</li><li><strong>الخدمات المستمرة</strong> مثل إدارة منصات التواصل الاجتماعي: باقة شهرية</li><li><strong>دون مفاجآت:</strong> تتلقى عرضاً تفصيلياً قبل أي التزام</li></ul>',
            ],
        ],
        [
            'question' => ['en' => 'Do you work in both Arabic and English?', 'ar' => 'هل تعملون باللغتين العربية والإنجليزية؟'],
            'answer' => [
                'en' => '<p>Yes. Our team creates content, copy and campaigns natively in Arabic and English, so your brand speaks naturally to every audience in the region.</p>',
                'ar' => '<p>نعم، يُعدّ فريقنا المحتوى والنصوص والحملات باللغتين العربية والإنجليزية بإتقان، لتخاطب علامتك كل جمهور في المنطقة بأسلوب طبيعي ومؤثر.</p>',
            ],
        ],
        [
            'question' => ['en' => 'Who do you work with?', 'ar' => 'مع من تعملون؟'],
            'answer' => [
                'en' => '<p>We partner with organisations of every size across the UAE and the wider GCC:</p><ul><li>Start-ups and SMEs</li><li>Government entities</li><li>Established brands</li></ul><p>Our sectors range from real estate and hospitality to retail and technology.</p>',
                'ar' => '<p>نتعاون مع جهات من مختلف الأحجام في دولة الإمارات ودول مجلس التعاون الخليجي:</p><ul><li>الشركات الناشئة والصغيرة والمتوسطة</li><li>الجهات الحكومية</li><li>العلامات التجارية الراسخة</li></ul><p>وتشمل القطاعات التي نخدمها العقارات والضيافة والتجزئة والتكنولوجيا.</p>',
            ],
        ],
        [
            'question' => ['en' => 'How do we get started?', 'ar' => 'كيف نبدأ العمل معاً؟'],
            'answer' => [
                'en' => '<p>Getting started takes three simple steps:</p><ul><li>Send us a message through the contact form or WhatsApp</li><li>We arrange a short call to understand your goals</li><li>You receive a proposal within a few working days</li></ul>',
                'ar' => '<p>نبدأ العمل معاً في ثلاث خطوات بسيطة:</p><ul><li>أرسل لنا رسالة عبر نموذج التواصل أو واتساب</li><li>نحدد معك مكالمة قصيرة لفهم أهدافك</li><li>تتلقى عرضاً مفصلاً خلال أيام عمل قليلة</li></ul>',
            ],
        ],
    ],
];
