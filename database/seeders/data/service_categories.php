<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Service categories, their services and category FAQs
|--------------------------------------------------------------------------
| Order here = display order (sort). Slugs are English-only and must not change
| once the site is live (they are URLs).
|
| Each service: title, description, features (3 per locale).
| Each category: 2 FAQs (seeded by FaqSeeder).
*/

return [

    /* ───────────────────────── 1. Brand Identity & Design ───────────────────────── */
    [
        'slug' => 'brand-identity-design',
        'title' => ['en' => 'Brand Identity & Design', 'ar' => 'بناء الهوية البصرية والتصميم الإبداعي'],
        'excerpt' => [
            'en' => 'Distinctive brands, built on strategy and crafted with care.',
            'ar' => 'علامات تجارية مميزة، تقوم على الاستراتيجية وتُصمَّم بعناية.',
        ],
        'description' => [
            'en' => 'We build brands that are clear in purpose and consistent in every detail. From positioning to logos, guidelines and packaging, we give your business an identity that earns recognition and trust.',
            'ar' => 'نبني علامات تجارية واضحة الهدف ومتسقة في كل تفاصيلها. ومن تحديد الموقع التنافسي إلى الشعار ودليل الهوية والتغليف، نمنح نشاطك التجاري هوية تحظى بالتميّز وتكسب الثقة.',
        ],
        'seo_title' => [
            'en' => 'Brand Identity & Design Agency in the UAE | Pixellap',
            'ar' => 'تصميم الهوية البصرية في الإمارات | بكسل لاب',
        ],
        'seo_description' => [
            'en' => 'Brand strategy, logo design, visual identity and packaging for businesses across the UAE and GCC.',
            'ar' => 'استراتيجية العلامة التجارية وتصميم الشعارات والهوية البصرية والتغليف للشركات في الإمارات ودول الخليج.',
        ],
        'services' => [
            [
                'title' => ['en' => 'Brand Strategy & Positioning', 'ar' => 'استراتيجية العلامة التجارية وتحديد موقعها'],
                'description' => [
                    'en' => 'We define what your brand stands for, who it serves and how it differs from competitors.',
                    'ar' => 'نحدد ما تمثله علامتك التجارية، والجمهور الذي تخدمه، وما يميزها عن منافسيها.',
                ],
                'features' => [
                    'en' => ['Market and competitor research', 'Brand purpose, values and personality', 'Positioning and messaging framework'],
                    'ar' => ['دراسة السوق والمنافسين', 'رسالة العلامة وقيمها وشخصيتها', 'إطار التموضع والرسائل الرئيسية'],
                ],
            ],
            [
                'title' => ['en' => 'Visual Identity Design', 'ar' => 'تصميم الهوية البصرية'],
                'description' => [
                    'en' => 'A complete visual system — colour, typography, imagery and graphic elements — that makes your brand instantly recognisable.',
                    'ar' => 'منظومة بصرية متكاملة تشمل الألوان والخطوط والصور والعناصر الجرافيكية، تجعل علامتك مميزة من النظرة الأولى.',
                ],
                'features' => [
                    'en' => ['Colour palette and typography', 'Graphic elements and patterns', 'Bilingual Arabic–English application'],
                    'ar' => ['لوحة الألوان والخطوط', 'العناصر والأنماط الجرافيكية', 'تطبيقات ثنائية اللغة بالعربية والإنجليزية'],
                ],
            ],
            [
                'title' => ['en' => 'Logo Design', 'ar' => 'تصميم الشعارات'],
                'description' => [
                    'en' => 'Original, timeless logos designed to work across every size, screen and language.',
                    'ar' => 'شعارات أصيلة لا تتقادم، مصممة لتعمل بكفاءة على مختلف الأحجام والشاشات واللغات.',
                ],
                'features' => [
                    'en' => ['Multiple initial concepts', 'Arabic and English lockups', 'Full file package for print and digital'],
                    'ar' => ['عدة مفاهيم أولية', 'نسخ عربية وإنجليزية متناسقة', 'ملفات متكاملة للطباعة والمنصات الرقمية'],
                ],
            ],
            [
                'title' => ['en' => 'Brand Guidelines', 'ar' => 'دليل الهوية البصرية'],
                'description' => [
                    'en' => 'A clear rulebook that keeps your brand consistent across teams, agencies and channels.',
                    'ar' => 'دليل واضح يضمن اتساق علامتك التجارية لدى فرق العمل والوكالات وعبر جميع القنوات.',
                ],
                'features' => [
                    'en' => ['Logo usage and clear-space rules', 'Colour, type and imagery standards', 'Templates and application examples'],
                    'ar' => ['قواعد استخدام الشعار والمساحات المحيطة به', 'معايير الألوان والخطوط والصور', 'قوالب وأمثلة تطبيقية'],
                ],
            ],
            [
                'title' => ['en' => 'Graphic Design', 'ar' => 'التصميم الجرافيكي'],
                'description' => [
                    'en' => 'Polished design for every touchpoint, from presentations and reports to social media and signage.',
                    'ar' => 'تصاميم احترافية لكل نقاط التواصل، من العروض التقديمية والتقارير إلى منصات التواصل الاجتماعي واللافتات.',
                ],
                'features' => [
                    'en' => ['Presentations and company profiles', 'Annual reports and infographics', 'Social media and digital assets'],
                    'ar' => ['العروض التقديمية والملفات التعريفية', 'التقارير السنوية والإنفوجرافيك', 'تصاميم منصات التواصل والأصول الرقمية'],
                ],
            ],
            [
                'title' => ['en' => 'Marketing & Advertising Materials', 'ar' => 'المواد التسويقية والإعلانية'],
                'description' => [
                    'en' => 'Print and digital collateral that turns attention into enquiries.',
                    'ar' => 'مواد تسويقية مطبوعة ورقمية تحوّل الاهتمام إلى استفسارات فعلية.',
                ],
                'features' => [
                    'en' => ['Brochures, flyers and catalogues', 'Outdoor and print advertising', 'Exhibition and event materials'],
                    'ar' => ['الكتيبات والمطويات والكتالوجات', 'الإعلانات الخارجية والمطبوعة', 'مواد المعارض والفعاليات'],
                ],
            ],
            [
                'title' => ['en' => 'Packaging Design', 'ar' => 'تصميم التغليف'],
                'description' => [
                    'en' => 'Packaging that stands out on the shelf and tells your product’s story at a glance.',
                    'ar' => 'تغليف يلفت الأنظار على الرفوف ويروي قصة منتجك من النظرة الأولى.',
                ],
                'features' => [
                    'en' => ['Structural and graphic packaging design', 'Label and product range design', 'Print-ready artwork and supplier liaison'],
                    'ar' => ['تصميم هيكلي وجرافيكي للعبوات', 'تصميم الملصقات وخطوط المنتجات', 'ملفات جاهزة للطباعة والتنسيق مع الموردين'],
                ],
            ],
        ],
        'faqs' => [
            [
                'question' => ['en' => 'How long does a brand identity project take?', 'ar' => 'كم يستغرق مشروع بناء الهوية البصرية؟'],
                'answer' => [
                    'en' => '<p>It depends on what you need:</p><ul><li><strong>Full identity:</strong> six to eight weeks, covering strategy, concepts, refinements and final guidelines</li><li><strong>Logo only:</strong> two to three weeks</li></ul>',
                    'ar' => '<p>يعتمد ذلك على ما تحتاجه:</p><ul><li><strong>الهوية المتكاملة:</strong> من ستة إلى ثمانية أسابيع، تشمل الاستراتيجية والمفاهيم الأولية والتعديلات ودليل الهوية النهائي</li><li><strong>الشعار وحده:</strong> من أسبوعين إلى ثلاثة أسابيع</li></ul>',
                ],
            ],
            [
                'question' => ['en' => 'Can you refresh our existing brand rather than start from scratch?', 'ar' => 'هل يمكنكم تطوير هويتنا الحالية بدلاً من البدء من الصفر؟'],
                'answer' => [
                    'en' => '<p>Yes. We often evolve existing brands while protecting the recognition you have already built:</p><ul><li>Modernising the logo</li><li>Tightening the visual system</li><li>Updating the brand guidelines</li></ul>',
                    'ar' => '<p>نعم، كثيراً ما نطوّر الهويات القائمة مع الحفاظ على ما بنته علامتك من حضور وتميّز، وذلك من خلال:</p><ul><li>تحديث الشعار</li><li>تنظيم المنظومة البصرية</li><li>تحديث دليل الهوية</li></ul>',
                ],
            ],
        ],
    ],

    /* ───────────────────────── 2. Media & Content Production ───────────────────────── */
    [
        'slug' => 'media-content-production',
        'title' => ['en' => 'Media & Content Production', 'ar' => 'إنتاج المحتوى المرئي والمسموع'],
        'excerpt' => [
            'en' => 'Video, photography and audio that capture attention.',
            'ar' => 'فيديو وتصوير فوتوغرافي ومحتوى صوتي يأسر الانتباه.',
        ],
        'description' => [
            'en' => 'Our production team handles every stage, from concept and scripting to filming, editing and final delivery. We create commercials, corporate films, photography and podcasts that look world-class and feel true to your brand.',
            'ar' => 'يتولى فريق الإنتاج لدينا جميع المراحل، من الفكرة وكتابة السيناريو إلى التصوير والمونتاج والتسليم النهائي. ننتج الإعلانات التجارية والأفلام المؤسسية والصور الاحترافية والبودكاست بجودة عالمية تعكس هوية علامتك بصدق.',
        ],
        'seo_title' => [
            'en' => 'Video & Content Production Company in the UAE | Pixellap',
            'ar' => 'شركة إنتاج فيديو ومحتوى في الإمارات | بكسل لاب',
        ],
        'seo_description' => [
            'en' => 'Commercial and corporate video, photography, podcasts and motion graphics produced in the UAE.',
            'ar' => 'إنتاج الإعلانات والأفلام المؤسسية والتصوير الفوتوغرافي والبودكاست والموشن جرافيك في الإمارات.',
        ],
        'services' => [
            [
                'title' => ['en' => 'Commercial Video Production', 'ar' => 'إنتاج الإعلانات التجارية'],
                'description' => [
                    'en' => 'High-impact commercials for TV, cinema and digital platforms, from concept to final cut.',
                    'ar' => 'إعلانات تجارية عالية التأثير للتلفزيون والسينما والمنصات الرقمية، من الفكرة حتى النسخة النهائية.',
                ],
                'features' => [
                    'en' => ['Creative concept and scriptwriting', 'Casting, locations and full crew', 'Cut-downs for every platform'],
                    'ar' => ['الفكرة الإبداعية وكتابة السيناريو', 'اختيار الممثلين ومواقع التصوير وطاقم متكامل', 'نسخ مختصرة تناسب كل منصة'],
                ],
            ],
            [
                'title' => ['en' => 'Corporate Videos', 'ar' => 'الأفلام المؤسسية'],
                'description' => [
                    'en' => 'Company profiles, interviews and internal films that tell your story with clarity.',
                    'ar' => 'أفلام تعريفية ومقابلات ومواد داخلية تنقل قصة مؤسستك بوضوح واحترافية.',
                ],
                'features' => [
                    'en' => ['Company profile films', 'Leadership and staff interviews', 'Training and internal communications'],
                    'ar' => ['الأفلام التعريفية بالشركات', 'مقابلات القيادات والموظفين', 'مواد التدريب والتواصل الداخلي'],
                ],
            ],
            [
                'title' => ['en' => 'Event Coverage', 'ar' => 'تغطية الفعاليات'],
                'description' => [
                    'en' => 'Professional photo and video coverage of conferences, launches and exhibitions, with same-day highlights available.',
                    'ar' => 'تغطية احترافية بالصور والفيديو للمؤتمرات وحفلات الإطلاق والمعارض، مع إمكانية تسليم مقاطع مختارة في اليوم نفسه.',
                ],
                'features' => [
                    'en' => ['Multi-camera filming', 'Same-day social media edits', 'Highlight and full-length films'],
                    'ar' => ['تصوير بكاميرات متعددة', 'مقاطع سريعة لمنصات التواصل في اليوم نفسه', 'أفلام مختصرة وأخرى كاملة'],
                ],
            ],
            [
                'title' => ['en' => 'Photography', 'ar' => 'التصوير الفوتوغرافي'],
                'description' => [
                    'en' => 'Corporate, editorial and lifestyle photography that gives your brand a consistent, premium look.',
                    'ar' => 'تصوير مؤسسي وتحريري وتصوير لأسلوب الحياة يمنح علامتك مظهراً راقياً ومتسقاً.',
                ],
                'features' => [
                    'en' => ['Corporate portraits and headshots', 'Lifestyle and campaign shoots', 'Professional retouching'],
                    'ar' => ['صور شخصية احترافية للقيادات والموظفين', 'جلسات تصوير للحملات وأسلوب الحياة', 'معالجة وتحسين احترافي للصور'],
                ],
            ],
            [
                'title' => ['en' => 'Product Photography & Videography', 'ar' => 'تصوير المنتجات فوتوغرافياً وبالفيديو'],
                'description' => [
                    'en' => 'Clean, detailed product imagery and video for e-commerce, catalogues and advertising.',
                    'ar' => 'صور ومقاطع فيديو واضحة ودقيقة للمنتجات، مخصصة للمتاجر الإلكترونية والكتالوجات والإعلانات.',
                ],
                'features' => [
                    'en' => ['Studio and white-background shots', 'Styled, in-context scenes', 'Short product videos and 360° spins'],
                    'ar' => ['تصوير استوديو بخلفية بيضاء', 'مشاهد منسّقة ضمن سياق الاستخدام', 'فيديوهات قصيرة وعروض بزاوية 360°'],
                ],
            ],
            [
                'title' => ['en' => 'Social Media Content Production', 'ar' => 'إنتاج محتوى منصات التواصل الاجتماعي'],
                'description' => [
                    'en' => 'Scroll-stopping reels, stories and posts, produced in batches to keep your channels active.',
                    'ar' => 'مقاطع ريلز وقصص ومنشورات تلفت الانتباه، تُنتَج على دفعات لضمان نشاط حساباتك باستمرار.',
                ],
                'features' => [
                    'en' => ['Reels, TikToks and stories', 'Monthly content shoot days', 'Platform-native formats and captions'],
                    'ar' => ['مقاطع ريلز وتيك توك وقصص', 'أيام تصوير شهرية للمحتوى', 'صيغ ونصوص مصممة لكل منصة'],
                ],
            ],
            [
                'title' => ['en' => 'Podcast Production', 'ar' => 'إنتاج البودكاست'],
                'description' => [
                    'en' => 'End-to-end podcast production, from format development to recording, editing and publishing.',
                    'ar' => 'إنتاج متكامل للبودكاست، من تطوير الفكرة والصيغة إلى التسجيل والمونتاج والنشر.',
                ],
                'features' => [
                    'en' => ['Format and episode planning', 'Video and audio recording', 'Editing, mixing and distribution'],
                    'ar' => ['تخطيط الصيغة والحلقات', 'التسجيل بالصوت والصورة', 'المونتاج والمكساج والنشر على المنصات'],
                ],
            ],
            [
                'title' => ['en' => 'Video Editing & Post-production', 'ar' => 'المونتاج وما بعد الإنتاج'],
                'description' => [
                    'en' => 'Expert editing, colour grading and sound design that turn raw footage into a finished film.',
                    'ar' => 'مونتاج احترافي وتصحيح ألوان وتصميم صوتي يحوّل المادة الخام إلى عمل متكامل.',
                ],
                'features' => [
                    'en' => ['Editing and storytelling', 'Colour grading', 'Sound design, subtitles and Arabic–English versions'],
                    'ar' => ['المونتاج وبناء القصة', 'تصحيح الألوان ومعالجتها', 'التصميم الصوتي والترجمة ونسخ بالعربية والإنجليزية'],
                ],
            ],
            [
                'title' => ['en' => 'Motion Graphics & Animation', 'ar' => 'الموشن جرافيك والرسوم المتحركة'],
                'description' => [
                    'en' => 'Animated explainers, logo animations and graphics that make complex ideas simple.',
                    'ar' => 'فيديوهات توضيحية متحركة وتحريك للشعارات ورسوم تبسّط الأفكار المعقدة.',
                ],
                'features' => [
                    'en' => ['2D explainer videos', 'Logo and title animation', 'Infographic and data animation'],
                    'ar' => ['فيديوهات توضيحية ثنائية الأبعاد', 'تحريك الشعارات والعناوين', 'تحريك الإنفوجرافيك والبيانات'],
                ],
            ],
        ],
        'faqs' => [
            [
                'question' => ['en' => 'Do you provide the full crew and equipment?', 'ar' => 'هل توفرون طاقم العمل والمعدات كاملة؟'],
                'answer' => [
                    'en' => '<p>Yes. You deal with one team from start to finish:</p><ul><li>Directors, camera operators and editors</li><li>Lighting and sound crew</li><li>Professional cinema equipment</li></ul>',
                    'ar' => '<p>نعم، تتعامل مع فريق واحد من البداية حتى النهاية:</p><ul><li>مخرجون ومصورون وفنيو مونتاج</li><li>فنيو إضاءة وصوت</li><li>معدات تصوير سينمائية احترافية</li></ul>',
                ],
            ],
            [
                'question' => ['en' => 'Do you film across the UAE and the GCC?', 'ar' => 'هل تصوّرون في جميع أنحاء الإمارات ودول الخليج؟'],
                'answer' => [
                    'en' => '<p>Yes. We film across all seven emirates and travel throughout the GCC for productions and events.</p>',
                    'ar' => '<p>نعم، نصوّر في جميع الإمارات السبع، ونتنقل إلى مختلف دول مجلس التعاون الخليجي لتنفيذ مشاريع الإنتاج وتغطية الفعاليات.</p>',
                ],
            ],
        ],
    ],

    /* ───────────────────────── 3. Digital Marketing ───────────────────────── */
    [
        'slug' => 'digital-marketing',
        'title' => ['en' => 'Digital Marketing', 'ar' => 'التسويق الرقمي وإدارة الحضور الإلكتروني للعلامات التجارية'],
        'excerpt' => [
            'en' => 'Data-led campaigns that grow audiences and revenue.',
            'ar' => 'حملات مبنية على البيانات تنمّي جمهورك وتزيد إيراداتك.',
        ],
        'description' => [
            'en' => 'We plan, run and optimise digital marketing that delivers measurable results. From social media management to paid campaigns and influencer partnerships, every activity is tied to clear goals and transparent reporting.',
            'ar' => 'نخطط لأنشطة التسويق الرقمي وننفذها ونحسّنها باستمرار لتحقيق نتائج قابلة للقياس. ومن إدارة منصات التواصل الاجتماعي إلى الحملات الإعلانية المدفوعة والتعاون مع المؤثرين، يرتبط كل نشاط بأهداف واضحة وتقارير شفافة.',
        ],
        'seo_title' => [
            'en' => 'Digital Marketing Agency in the UAE | Pixellap',
            'ar' => 'وكالة تسويق رقمي في الإمارات | بكسل لاب',
        ],
        'seo_description' => [
            'en' => 'Social media management, paid advertising, influencer marketing and performance reporting across the UAE and GCC.',
            'ar' => 'إدارة منصات التواصل الاجتماعي والإعلانات المدفوعة والتسويق عبر المؤثرين وتقارير الأداء في الإمارات ودول الخليج.',
        ],
        'services' => [
            [
                'title' => ['en' => 'Social Media Management', 'ar' => 'إدارة منصات التواصل الاجتماعي'],
                'description' => [
                    'en' => 'Day-to-day management of your social channels, from content calendars to community engagement.',
                    'ar' => 'إدارة يومية لحساباتك على منصات التواصل، من إعداد خطط النشر إلى التفاعل مع الجمهور.',
                ],
                'features' => [
                    'en' => ['Monthly content calendars', 'Publishing and scheduling', 'Community management in Arabic and English'],
                    'ar' => ['خطط محتوى شهرية', 'النشر والجدولة', 'إدارة التفاعل مع الجمهور بالعربية والإنجليزية'],
                ],
            ],
            [
                'title' => ['en' => 'Digital Marketing Strategy', 'ar' => 'استراتيجية التسويق الرقمي'],
                'description' => [
                    'en' => 'A clear roadmap that aligns channels, budget and messaging with your business goals.',
                    'ar' => 'خارطة طريق واضحة توائم بين القنوات والميزانية والرسائل وأهداف نشاطك التجاري.',
                ],
                'features' => [
                    'en' => ['Audience and channel analysis', 'Goals, KPIs and budget planning', '12-month marketing roadmap'],
                    'ar' => ['تحليل الجمهور والقنوات', 'تحديد الأهداف ومؤشرات الأداء والميزانية', 'خطة تسويقية لمدة 12 شهراً'],
                ],
            ],
            [
                'title' => ['en' => 'Social Media Marketing', 'ar' => 'التسويق عبر منصات التواصل الاجتماعي'],
                'description' => [
                    'en' => 'Campaigns that grow reach, engagement and conversions on Instagram, TikTok, LinkedIn, Snapchat and X.',
                    'ar' => 'حملات تعزز الوصول والتفاعل والتحويل على إنستغرام وتيك توك ولينكدإن وسناب شات وإكس.',
                ],
                'features' => [
                    'en' => ['Platform-specific campaign planning', 'Creative testing and optimisation', 'Audience growth strategies'],
                    'ar' => ['تخطيط حملات مخصصة لكل منصة', 'اختبار المحتوى الإبداعي وتحسينه', 'استراتيجيات لتنمية قاعدة المتابعين'],
                ],
            ],
            [
                'title' => ['en' => 'Paid Advertising Campaigns', 'ar' => 'الحملات الإعلانية المدفوعة'],
                'description' => [
                    'en' => 'Targeted ads on Google, Meta, TikTok and Snapchat, managed to maximise return on spend.',
                    'ar' => 'إعلانات موجّهة على Google وMeta وتيك توك وسناب شات، تُدار لتحقيق أعلى عائد على الإنفاق.',
                ],
                'features' => [
                    'en' => ['Search, social and display ads', 'Audience targeting and retargeting', 'Ongoing bid and budget optimisation'],
                    'ar' => ['إعلانات البحث والتواصل الاجتماعي والإعلانات المصوّرة', 'استهداف الجمهور وإعادة الاستهداف', 'تحسين مستمر للمزايدات والميزانية'],
                ],
            ],
            [
                'title' => ['en' => 'Content Strategy & Planning', 'ar' => 'استراتيجية المحتوى والتخطيط له'],
                'description' => [
                    'en' => 'Content pillars, formats and calendars that give every post a purpose.',
                    'ar' => 'محاور وصيغ وخطط محتوى تمنح كل منشور هدفاً واضحاً.',
                ],
                'features' => [
                    'en' => ['Content pillars and tone of voice', 'Editorial calendars', 'Bilingual copywriting'],
                    'ar' => ['محاور المحتوى ونبرة الخطاب', 'خطط النشر التحريرية', 'كتابة المحتوى باللغتين'],
                ],
            ],
            [
                'title' => ['en' => 'Influencer Marketing', 'ar' => 'التسويق عبر المؤثرين'],
                'description' => [
                    'en' => 'Partnerships with the right creators across the region, managed from shortlist to results.',
                    'ar' => 'شراكات مع صنّاع المحتوى المناسبين في المنطقة، تُدار من اختيار المؤثرين حتى قياس النتائج.',
                ],
                'features' => [
                    'en' => ['Influencer research and licence checks', 'Briefing, contracts and approvals', 'Performance tracking'],
                    'ar' => ['البحث عن المؤثرين والتحقق من تراخيصهم', 'إعداد الموجز والعقود والموافقات', 'متابعة الأداء وقياسه'],
                ],
            ],
            [
                'title' => ['en' => 'Campaign Management', 'ar' => 'إدارة الحملات'],
                'description' => [
                    'en' => 'Integrated campaigns coordinated across channels, from launch plan to final report.',
                    'ar' => 'حملات متكاملة منسّقة عبر مختلف القنوات، من خطة الإطلاق حتى التقرير الختامي.',
                ],
                'features' => [
                    'en' => ['Campaign planning and timelines', 'Cross-channel coordination', 'Real-time monitoring and adjustments'],
                    'ar' => ['تخطيط الحملات وجداولها الزمنية', 'التنسيق بين القنوات المختلفة', 'متابعة لحظية وتعديلات فورية'],
                ],
            ],
            [
                'title' => ['en' => 'Performance Analysis & Reporting', 'ar' => 'تحليل الأداء وإعداد التقارير'],
                'description' => [
                    'en' => 'Clear, regular reporting that shows what is working and where to invest next.',
                    'ar' => 'تقارير دورية واضحة تبيّن ما يحقق النتائج وأين ينبغي توجيه الاستثمار لاحقاً.',
                ],
                'features' => [
                    'en' => ['Monthly performance reports', 'Dashboards and KPI tracking', 'Actionable recommendations'],
                    'ar' => ['تقارير أداء شهرية', 'لوحات متابعة ومؤشرات أداء رئيسية', 'توصيات عملية قابلة للتنفيذ'],
                ],
            ],
        ],
        'faqs' => [
            [
                'question' => ['en' => 'What budget do I need for paid advertising?', 'ar' => 'ما الميزانية المطلوبة للإعلانات المدفوعة؟'],
                'answer' => [
                    'en' => '<p>Budgets vary by goal and market, so we start small and scale what works:</p><ul><li>A short test phase finds the best-performing channels</li><li>We then scale the channels that deliver</li><li>Our management fee is quoted separately from your ad spend</li></ul>',
                    'ar' => '<p>تختلف الميزانية بحسب الهدف والسوق، لذلك نبدأ بخطوات مدروسة ثم نتوسع فيما ينجح:</p><ul><li>مرحلة تجريبية قصيرة لتحديد القنوات الأعلى أداءً</li><li>التوسع في القنوات التي تحقق النتائج</li><li>تُحتسب رسوم الإدارة بشكل منفصل عن ميزانية الإعلانات</li></ul>',
                ],
            ],
            [
                'question' => ['en' => 'How soon will we see results?', 'ar' => 'متى نبدأ في رؤية النتائج؟'],
                'answer' => [
                    'en' => '<p>It depends on the channel:</p><ul><li><strong>Paid campaigns:</strong> results within days</li><li><strong>Organic growth and brand awareness:</strong> three to six months of consistent activity</li><li><strong>Monthly reports</strong> so you can see progress clearly</li></ul>',
                    'ar' => '<p>يعتمد ذلك على القناة:</p><ul><li><strong>الحملات المدفوعة:</strong> نتائج خلال أيام</li><li><strong>النمو العضوي والوعي بالعلامة:</strong> من ثلاثة إلى ستة أشهر من العمل المستمر</li><li><strong>تقارير شهرية</strong> لتتابع التقدم بوضوح</li></ul>',
                ],
            ],
        ],
    ],

    /* ───────────────────────── 4. Web Design & Development ───────────────────────── */
    [
        'slug' => 'web-design-development',
        'title' => ['en' => 'Web Design & Development', 'ar' => 'تصميم وتطوير المواقع الإلكترونية'],
        'excerpt' => [
            'en' => 'Fast, bilingual websites designed to convert.',
            'ar' => 'مواقع إلكترونية سريعة وثنائية اللغة مصممة لتحقيق النتائج.',
        ],
        'description' => [
            'en' => 'We design and build websites that look exceptional, load quickly and work flawlessly in Arabic and English. Every site is responsive, search-friendly and easy for your team to manage.',
            'ar' => 'نصمم ونطوّر مواقع إلكترونية بمظهر استثنائي وسرعة تحميل عالية وأداء مثالي باللغتين العربية والإنجليزية. وكل موقع نطوّره متجاوب مع مختلف الأجهزة، ومتوافق مع محركات البحث، وسهل الإدارة لفريقك.',
        ],
        'seo_title' => [
            'en' => 'Web Design & Development Company in the UAE | Pixellap',
            'ar' => 'تصميم وتطوير المواقع الإلكترونية في الإمارات | بكسل لاب',
        ],
        'seo_description' => [
            'en' => 'Bilingual corporate websites, e-commerce stores and landing pages, designed and developed in the UAE.',
            'ar' => 'مواقع مؤسسية ومتاجر إلكترونية وصفحات هبوط ثنائية اللغة، نصممها ونطوّرها في الإمارات.',
        ],
        'services' => [
            [
                'title' => ['en' => 'Website Design (UI/UX)', 'ar' => 'تصميم واجهات المواقع وتجربة المستخدم (UI/UX)'],
                'description' => [
                    'en' => 'Intuitive, beautiful interfaces designed around how your customers think and browse.',
                    'ar' => 'واجهات جذابة وسهلة الاستخدام، مصممة وفق طريقة تفكير عملائك وتصفّحهم.',
                ],
                'features' => [
                    'en' => ['User research and site mapping', 'Wireframes and interactive prototypes', 'Right-to-left Arabic layouts'],
                    'ar' => ['دراسة المستخدمين وهيكلة الموقع', 'مخططات أولية ونماذج تفاعلية', 'تصاميم عربية متوافقة مع الاتجاه من اليمين إلى اليسار'],
                ],
            ],
            [
                'title' => ['en' => 'Website Development', 'ar' => 'تطوير المواقع الإلكترونية'],
                'description' => [
                    'en' => 'Robust, secure websites built with modern technology and a content management system your team can use.',
                    'ar' => 'مواقع إلكترونية قوية وآمنة، مبنية بأحدث التقنيات مع نظام إدارة محتوى سهل الاستخدام لفريقك.',
                ],
                'features' => [
                    'en' => ['Modern, high-performance frameworks', 'Custom CMS integration', 'Security and speed optimisation'],
                    'ar' => ['أُطر عمل حديثة عالية الأداء', 'ربط بنظام إدارة محتوى مخصص', 'تحسين الأمان وسرعة التحميل'],
                ],
            ],
            [
                'title' => ['en' => 'Corporate Websites', 'ar' => 'المواقع المؤسسية'],
                'description' => [
                    'en' => 'Professional bilingual websites that present your company with credibility and clarity.',
                    'ar' => 'مواقع مؤسسية احترافية ثنائية اللغة تقدّم شركتك بمصداقية ووضوح.',
                ],
                'features' => [
                    'en' => ['Arabic and English versions', 'Lead capture and contact forms', 'Search engine optimisation foundations'],
                    'ar' => ['نسختان عربية وإنجليزية', 'نماذج تواصل لاستقطاب العملاء المحتملين', 'أساسيات تحسين الظهور في محركات البحث'],
                ],
            ],
            [
                'title' => ['en' => 'Landing Page Design', 'ar' => 'تصميم صفحات الهبوط'],
                'description' => [
                    'en' => 'Focused, high-converting landing pages for campaigns, launches and lead generation.',
                    'ar' => 'صفحات هبوط مركّزة عالية التحويل للحملات وإطلاق المنتجات واستقطاب العملاء.',
                ],
                'features' => [
                    'en' => ['Conversion-focused layouts', 'Ready for A/B testing', 'Analytics and tracking pixel integration'],
                    'ar' => ['تصاميم تركّز على التحويل', 'جاهزية لاختبارات A/B', 'ربط أدوات التحليل وأكواد التتبع'],
                ],
            ],
            [
                'title' => ['en' => 'E-commerce Websites', 'ar' => 'المتاجر الإلكترونية'],
                'description' => [
                    'en' => 'Online stores with smooth checkout, local payment gateways and easy product management.',
                    'ar' => 'متاجر إلكترونية بتجربة شراء سلسة، وبوابات دفع محلية، وإدارة سهلة للمنتجات.',
                ],
                'features' => [
                    'en' => ['Shopify, WooCommerce or custom builds', 'Local payment and delivery integrations', 'Inventory and order management'],
                    'ar' => ['متاجر على Shopify أو WooCommerce أو حلول مخصصة', 'ربط بوابات الدفع وخدمات التوصيل المحلية', 'إدارة المخزون والطلبات'],
                ],
            ],
            [
                'title' => ['en' => 'Website Maintenance & Support', 'ar' => 'صيانة المواقع والدعم الفني'],
                'description' => [
                    'en' => 'Ongoing care that keeps your website secure, up to date and performing at its best.',
                    'ar' => 'رعاية مستمرة تحافظ على أمان موقعك وتحديثه وأدائه بأفضل صورة.',
                ],
                'features' => [
                    'en' => ['Security updates and backups', 'Content updates on request', 'Uptime and performance monitoring'],
                    'ar' => ['التحديثات الأمنية والنسخ الاحتياطي', 'تحديث المحتوى عند الطلب', 'مراقبة جاهزية الموقع وأدائه'],
                ],
            ],
            [
                'title' => ['en' => 'Responsive Web Design', 'ar' => 'تصميم المواقع المتجاوبة'],
                'description' => [
                    'en' => 'Websites that adapt seamlessly to every screen, from mobile phones to large desktops.',
                    'ar' => 'مواقع تتكيف بسلاسة مع جميع الشاشات، من الهواتف المحمولة إلى شاشات الحاسوب الكبيرة.',
                ],
                'features' => [
                    'en' => ['Mobile-first design', 'Cross-browser and device testing', 'Optimised images and performance'],
                    'ar' => ['تصميم يبدأ من الهاتف المحمول', 'اختبار على مختلف المتصفحات والأجهزة', 'تحسين الصور والأداء'],
                ],
            ],
        ],
        'faqs' => [
            [
                'question' => ['en' => 'Will our website work in both Arabic and English?', 'ar' => 'هل سيعمل موقعنا باللغتين العربية والإنجليزية؟'],
                'answer' => [
                    'en' => '<p>Yes. Every website we build is fully bilingual, with proper right-to-left layouts for Arabic — not simply a mirrored or machine-translated version.</p>',
                    'ar' => '<p>نعم، كل موقع نطوّره ثنائي اللغة بالكامل، مع تصميم عربي سليم من اليمين إلى اليسار، وليس مجرد نسخة معكوسة أو مترجمة آلياً.</p>',
                ],
            ],
            [
                'question' => ['en' => 'Can we update the website ourselves?', 'ar' => 'هل يمكننا تحديث الموقع بأنفسنا؟'],
                'answer' => [
                    'en' => '<p>Yes. You stay in control of your content:</p><ul><li>An easy-to-use content management system</li><li>Training for your team</li><li>Update text, images and pages without technical help</li></ul>',
                    'ar' => '<p>نعم، يبقى محتوى موقعك بين يديك:</p><ul><li>نظام إدارة محتوى سهل الاستخدام</li><li>تدريب فريقك على استخدامه</li><li>تحديث النصوص والصور والصفحات دون الحاجة إلى دعم تقني</li></ul>',
                ],
            ],
        ],
    ],

    /* ───────────────────────── 5. Creative & AI Solutions ───────────────────────── */
    [
        'slug' => 'creative-ai-solutions',
        'title' => ['en' => 'Creative & AI Solutions', 'ar' => 'الحلول الإبداعية والتقنيات الحديثة في صناعة المحتوى'],
        'excerpt' => [
            'en' => 'Creative thinking, amplified by artificial intelligence.',
            'ar' => 'تفكير إبداعي تعززه تقنيات الذكاء الاصطناعي.',
        ],
        'description' => [
            'en' => 'We combine creative direction with the latest AI tools to produce striking visuals and video faster and at scale. Every output is guided by our team, so it stays on-brand, original and responsibly produced.',
            'ar' => 'نجمع بين التوجيه الإبداعي وأحدث أدوات الذكاء الاصطناعي لإنتاج صور ومقاطع فيديو لافتة بسرعة أكبر وعلى نطاق أوسع. ويشرف فريقنا على كل مخرج لضمان توافقه مع هوية علامتك وأصالته وإنتاجه بمسؤولية.',
        ],
        'seo_title' => [
            'en' => 'AI Content & Creative Solutions in the UAE | Pixellap',
            'ar' => 'حلول المحتوى الإبداعي بالذكاء الاصطناعي في الإمارات | بكسل لاب',
        ],
        'seo_description' => [
            'en' => 'AI image generation, AI video, visual effects and creative campaign concepts for brands in the UAE and GCC.',
            'ar' => 'توليد الصور والفيديو بالذكاء الاصطناعي والمؤثرات البصرية والأفكار الإبداعية للحملات للعلامات التجارية في الإمارات ودول الخليج.',
        ],
        'services' => [
            [
                'title' => ['en' => 'AI-Powered Image Generation', 'ar' => 'توليد الصور بالذكاء الاصطناعي'],
                'description' => [
                    'en' => 'Bespoke, on-brand imagery created with AI and refined by our designers.',
                    'ar' => 'صور مخصصة متوافقة مع هوية علامتك، تُنشأ بالذكاء الاصطناعي ويصقلها مصممونا.',
                ],
                'features' => [
                    'en' => ['Campaign key visuals', 'Product and lifestyle scenes', 'Professional retouching and upscaling'],
                    'ar' => ['الصور الرئيسية للحملات', 'مشاهد للمنتجات وأسلوب الحياة', 'معالجة احترافية ورفع لدقة الصور'],
                ],
            ],
            [
                'title' => ['en' => 'AI Video Production', 'ar' => 'إنتاج الفيديو بالذكاء الاصطناعي'],
                'description' => [
                    'en' => 'Short-form and campaign videos produced with AI tools, from concept to final edit.',
                    'ar' => 'مقاطع فيديو قصيرة ومواد للحملات تُنتَج بأدوات الذكاء الاصطناعي، من الفكرة حتى المونتاج النهائي.',
                ],
                'features' => [
                    'en' => ['Text-to-video and image-to-video', 'AI voiceovers in Arabic and English', 'Editing, sound and finishing'],
                    'ar' => ['تحويل النصوص والصور إلى فيديو', 'تعليق صوتي بالذكاء الاصطناعي بالعربية والإنجليزية', 'المونتاج والصوت واللمسات النهائية'],
                ],
            ],
            [
                'title' => ['en' => 'AI Commercial Content', 'ar' => 'المحتوى الإعلاني بالذكاء الاصطناعي'],
                'description' => [
                    'en' => 'Advertising content produced faster and more cost-effectively, without compromising on quality.',
                    'ar' => 'محتوى إعلاني يُنتَج بسرعة أكبر وتكلفة أقل، دون المساس بالجودة.',
                ],
                'features' => [
                    'en' => ['Ad variations for testing', 'Localised versions for each market', 'Rapid turnaround for campaigns'],
                    'ar' => ['نسخ إعلانية متعددة للاختبار', 'نسخ مخصصة لكل سوق', 'تسليم سريع للحملات'],
                ],
            ],
            [
                'title' => ['en' => 'Creative Campaign Concepts', 'ar' => 'الأفكار الإبداعية للحملات'],
                'description' => [
                    'en' => 'Big ideas and campaign platforms that give your brand something worth talking about.',
                    'ar' => 'أفكار كبرى ومنصات حملات تمنح علامتك ما يستحق أن يُتحدَّث عنه.',
                ],
                'features' => [
                    'en' => ['Campaign ideas and storytelling', 'Moodboards and visual direction', 'AI-assisted concept visualisation'],
                    'ar' => ['أفكار الحملات وبناء القصة', 'لوحات الإلهام والتوجه البصري', 'تصوّر المفاهيم بمساعدة الذكاء الاصطناعي'],
                ],
            ],
            [
                'title' => ['en' => 'AI Visual Effects', 'ar' => 'المؤثرات البصرية بالذكاء الاصطناعي'],
                'description' => [
                    'en' => 'Cinematic visual effects created with AI, at a fraction of the usual cost and time.',
                    'ar' => 'مؤثرات بصرية سينمائية تُنشأ بالذكاء الاصطناعي، بجزء بسيط من التكلفة والوقت المعتادين.',
                ],
                'features' => [
                    'en' => ['Background and environment replacement', 'Object removal and clean-up', 'Stylised and surreal effects'],
                    'ar' => ['استبدال الخلفيات والبيئات', 'إزالة العناصر وتنقية المشاهد', 'مؤثرات فنية وخيالية'],
                ],
            ],
            [
                'title' => ['en' => 'AI Content Workflows', 'ar' => 'منظومات إنتاج المحتوى بالذكاء الاصطناعي'],
                'description' => [
                    'en' => 'Custom AI workflows that help your team produce more content, consistently and on-brand.',
                    'ar' => 'منظومات عمل مخصصة بالذكاء الاصطناعي تساعد فريقك على إنتاج محتوى أكثر باتساق وتوافق مع هوية العلامة.',
                ],
                'features' => [
                    'en' => ['Workflow audit and tool selection', 'Brand-trained prompts and templates', 'Team onboarding and usage guidelines'],
                    'ar' => ['تقييم سير العمل واختيار الأدوات', 'أوامر وقوالب مخصصة وفق هوية العلامة', 'تهيئة الفريق وإعداد إرشادات الاستخدام'],
                ],
            ],
        ],
        'faqs' => [
            [
                'question' => ['en' => 'Is AI-generated content safe to use commercially?', 'ar' => 'هل يمكن استخدام المحتوى المولّد بالذكاء الاصطناعي تجارياً بأمان؟'],
                'answer' => [
                    'en' => '<p>Yes, when it is done properly. We:</p><ul><li>Use licensed, commercially cleared tools</li><li>Review every output for brand fit, accuracy and rights</li><li>Tell you when traditional production is the better choice</li></ul>',
                    'ar' => '<p>نعم، عند استخدامه بالطريقة الصحيحة؛ إذ إننا:</p><ul><li>نستخدم أدوات مرخّصة ومعتمدة للاستخدام التجاري</li><li>نراجع كل مخرج للتأكد من توافقه مع هوية العلامة ودقته وسلامة حقوقه</li><li>نوضح لك متى يكون الإنتاج التقليدي هو الخيار الأفضل</li></ul>',
                ],
            ],
            [
                'question' => ['en' => 'Does AI replace your creative team?', 'ar' => 'هل يحل الذكاء الاصطناعي محل فريقكم الإبداعي؟'],
                'answer' => [
                    'en' => '<p>No. AI is a tool our team uses to move faster. These stay in the hands of experienced people:</p><ul><li>Strategy and ideas</li><li>Art direction</li><li>Quality control</li></ul>',
                    'ar' => '<p>لا، فالذكاء الاصطناعي أداة يستخدمها فريقنا للعمل بسرعة أكبر، بينما تبقى المهام التالية بأيدي خبراء متمرسين:</p><ul><li>الاستراتيجية والأفكار</li><li>التوجيه الفني</li><li>مراقبة الجودة</li></ul>',
                ],
            ],
        ],
    ],

    /* ───────────────────────── 6. Training & Workshops ───────────────────────── */
    [
        'slug' => 'training-workshops',
        'title' => ['en' => 'Training & Workshops', 'ar' => 'التدريب والتطوير في مجال صناعة المحتوى والإنتاج الإبداعي'],
        'excerpt' => [
            'en' => 'Practical training that builds in-house creative skills.',
            'ar' => 'تدريب عملي يبني المهارات الإبداعية داخل مؤسستك.',
        ],
        'description' => [
            'en' => 'We share what we do every day through hands-on courses and workshops for teams and individuals. Sessions are practical, delivered in Arabic or English, and tailored to your organisation’s goals.',
            'ar' => 'ننقل خبراتنا اليومية من خلال دورات وورش عمل تطبيقية للفرق والأفراد. وتتميز جلساتنا بطابعها العملي، وتُقدَّم باللغة العربية أو الإنجليزية، وتُصمَّم وفق أهداف مؤسستك.',
        ],
        'seo_title' => [
            'en' => 'Content Creation & Media Training in the UAE | Pixellap',
            'ar' => 'التدريب على صناعة المحتوى والإعلام في الإمارات | بكسل لاب',
        ],
        'seo_description' => [
            'en' => 'Hands-on workshops in content creation, photography, video editing, AI tools and corporate media for teams in the UAE.',
            'ar' => 'ورش عمل تطبيقية في صناعة المحتوى والتصوير والمونتاج وأدوات الذكاء الاصطناعي والإعلام المؤسسي للفرق في الإمارات.',
        ],
        'services' => [
            [
                'title' => ['en' => 'Content Creation Training', 'ar' => 'التدريب على صناعة المحتوى'],
                'description' => [
                    'en' => 'Learn to plan, shoot and publish engaging content for social media and digital channels.',
                    'ar' => 'تعلّم تخطيط المحتوى وتصويره ونشره بأسلوب جذاب على منصات التواصل والقنوات الرقمية.',
                ],
                'features' => [
                    'en' => ['Content planning and storytelling', 'Mobile filming techniques', 'Platform best practice'],
                    'ar' => ['تخطيط المحتوى وفن السرد', 'تقنيات التصوير بالهاتف المحمول', 'أفضل الممارسات لكل منصة'],
                ],
            ],
            [
                'title' => ['en' => 'Photography & Videography Workshops', 'ar' => 'ورش عمل التصوير الفوتوغرافي والفيديو'],
                'description' => [
                    'en' => 'Hands-on workshops covering camera skills, lighting and composition.',
                    'ar' => 'ورش عمل تطبيقية تغطي مهارات استخدام الكاميرا والإضاءة وتكوين الكادر.',
                ],
                'features' => [
                    'en' => ['Camera settings and lenses', 'Lighting for photo and video', 'Composition and shot planning'],
                    'ar' => ['إعدادات الكاميرا والعدسات', 'الإضاءة للتصوير الفوتوغرافي والفيديو', 'تكوين الكادر وتخطيط اللقطات'],
                ],
            ],
            [
                'title' => ['en' => 'Video Editing Training', 'ar' => 'التدريب على المونتاج'],
                'description' => [
                    'en' => 'Practical editing courses using industry-standard software.',
                    'ar' => 'دورات عملية في المونتاج باستخدام البرامج المعتمدة في القطاع.',
                ],
                'features' => [
                    'en' => ['Adobe Premiere Pro and CapCut', 'Editing for social media formats', 'Colour, sound and export basics'],
                    'ar' => ['برنامجا Adobe Premiere Pro وCapCut', 'المونتاج لصيغ منصات التواصل', 'أساسيات الألوان والصوت والتصدير'],
                ],
            ],
            [
                'title' => ['en' => 'AI Content Creation Training', 'ar' => 'التدريب على صناعة المحتوى بالذكاء الاصطناعي'],
                'description' => [
                    'en' => 'Learn to use AI tools responsibly to create images, video and copy.',
                    'ar' => 'تعلّم استخدام أدوات الذكاء الاصطناعي بمسؤولية لإنشاء الصور والفيديو والنصوص.',
                ],
                'features' => [
                    'en' => ['Leading AI tools for image and video', 'Prompting techniques', 'Responsible and ethical use'],
                    'ar' => ['أبرز أدوات الذكاء الاصطناعي للصور والفيديو', 'تقنيات كتابة الأوامر', 'الاستخدام المسؤول والأخلاقي'],
                ],
            ],
            [
                'title' => ['en' => 'Corporate Media Training', 'ar' => 'التدريب الإعلامي للمؤسسات'],
                'description' => [
                    'en' => 'Prepare leaders and spokespeople to communicate confidently on camera and with the press.',
                    'ar' => 'إعداد القيادات والمتحدثين الرسميين للتواصل بثقة أمام الكاميرا ومع وسائل الإعلام.',
                ],
                'features' => [
                    'en' => ['On-camera presence and delivery', 'Interview and press conference practice', 'Key messages and crisis communication'],
                    'ar' => ['الحضور والأداء أمام الكاميرا', 'التدريب على المقابلات والمؤتمرات الصحفية', 'الرسائل الرئيسية والتواصل في الأزمات'],
                ],
            ],
            [
                // Title as specified by the client (US spelling kept intentionally).
                'title' => ['en' => 'Customized Training Programs', 'ar' => 'برامج تدريبية مخصصة'],
                'description' => [
                    'en' => 'Programmes designed around your team’s skills, goals and schedule.',
                    'ar' => 'برامج مصممة وفق مهارات فريقك وأهدافه وجدوله الزمني.',
                ],
                'features' => [
                    'en' => ['Skills assessment and needs analysis', 'On-site or online delivery', 'Certificates of completion'],
                    'ar' => ['تقييم المهارات وتحليل الاحتياجات', 'تدريب حضوري في مقر المؤسسة أو عن بُعد', 'شهادات إتمام'],
                ],
            ],
        ],
        'faqs' => [
            [
                'question' => ['en' => 'Can you run training at our offices?', 'ar' => 'هل تقدّمون التدريب في مقر مؤسستنا؟'],
                'answer' => [
                    'en' => '<p>Yes. We deliver training at your offices anywhere in the UAE, or online, depending on what suits your team.</p>',
                    'ar' => '<p>نعم، نقدّم التدريب في مقر مؤسستك في أي مكان داخل الإمارات، أو عن بُعد، بحسب ما يناسب فريقك.</p>',
                ],
            ],
            [
                'question' => ['en' => 'Are the courses available in Arabic?', 'ar' => 'هل تتوفر الدورات باللغة العربية؟'],
                'answer' => [
                    'en' => '<p>Yes. All courses can be delivered in Arabic or English, with materials provided in the language of your choice.</p>',
                    'ar' => '<p>نعم، يمكن تقديم جميع الدورات باللغة العربية أو الإنجليزية، مع توفير المواد التدريبية باللغة التي تختارها.</p>',
                ],
            ],
        ],
    ],
];
