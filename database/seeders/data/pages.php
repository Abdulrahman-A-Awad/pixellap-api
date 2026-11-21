<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pages and their blocks
|--------------------------------------------------------------------------
| Keyed by App\Enums\PageKey value. Block order here = sort order.
| `items` is a list of {title, text} per locale.
*/

return [

    'about' => [
        'title' => ['en' => 'About Pixellap', 'ar' => 'عن بكسل لاب'],
        'subtitle' => [
            'en' => 'A creative, media and digital agency built in the UAE.',
            'ar' => 'وكالة إبداعية للإعلام والتسويق الرقمي، انطلقت من دولة الإمارات.',
        ],
        'seo_title' => ['en' => 'About Us | Pixellap', 'ar' => 'من نحن | بكسل لاب'],
        'seo_description' => [
            'en' => 'Meet Pixellap, a UAE creative agency bringing branding, media production, digital marketing and web together under one roof.',
            'ar' => 'تعرّف على بكسل لاب، الوكالة الإبداعية الإماراتية التي تجمع الهوية البصرية والإنتاج الإعلامي والتسويق الرقمي وتطوير المواقع تحت سقف واحد.',
        ],
        'blocks' => [
            [
                // Photo added by pixellap:import-media (docs/brand-identity/import-manifest.json).
                'type' => 'image_text',
                'title' => ['en' => 'Who we are', 'ar' => 'من نحن'],
                'body' => [
                    'en' => "Pixellap is a creative, media and digital agency based in the UAE. We bring strategists, designers, filmmakers, marketers and developers together in one team, so our clients get joined-up thinking and consistent quality at every step.\n\nWe believe the smallest details make the biggest difference. Every pixel, frame and word is crafted with purpose — because that is how brands earn attention and trust.",
                    'ar' => "بكسل لاب وكالة إبداعية متخصصة في الإعلام والتسويق الرقمي، مقرها دولة الإمارات العربية المتحدة. نجمع الاستراتيجيين والمصممين وصنّاع الأفلام والمسوّقين والمطورين في فريق واحد، ليحظى عملاؤنا برؤية متكاملة وجودة ثابتة في كل مرحلة.\n\nنؤمن بأن أدق التفاصيل تصنع الفارق الأكبر؛ لذلك نصنع كل بيكسل وكل لقطة وكل كلمة بهدف واضح، فهكذا تكسب العلامات التجارية الاهتمام والثقة.",
                ],
            ],
            [
                'type' => 'mission_vision',
                'title' => ['en' => 'Mission & vision', 'ar' => 'رسالتنا ورؤيتنا'],
                'items' => [
                    'en' => [
                        ['title' => 'Our mission', 'text' => 'To help brands in the UAE and the region grow through clear strategy, outstanding creative work and measurable results.'],
                        ['title' => 'Our vision', 'text' => 'To be the region’s most trusted creative partner, known for small details that create big impact.'],
                    ],
                    'ar' => [
                        ['title' => 'رسالتنا', 'text' => 'مساعدة العلامات التجارية في الإمارات والمنطقة على النمو من خلال استراتيجية واضحة وأعمال إبداعية متميزة ونتائج قابلة للقياس.'],
                        ['title' => 'رؤيتنا', 'text' => 'أن نكون الشريك الإبداعي الأكثر ثقة في المنطقة، المعروف بتفاصيل صغيرة تصنع أثراً كبيراً.'],
                    ],
                ],
            ],
            [
                'type' => 'values',
                'title' => ['en' => 'Our values', 'ar' => 'قيمنا'],
                'items' => [
                    'en' => [
                        ['title' => 'Craft', 'text' => 'We care about every detail, because quality is never an accident.'],
                        ['title' => 'Clarity', 'text' => 'Clear thinking, clear communication, clear results.'],
                        ['title' => 'Partnership', 'text' => 'We work as an extension of your team, not as a supplier.'],
                        ['title' => 'Curiosity', 'text' => 'We keep learning, testing and embracing new technology.'],
                    ],
                    'ar' => [
                        ['title' => 'الإتقان', 'text' => 'نهتم بأدق التفاصيل، فالجودة لا تأتي مصادفة.'],
                        ['title' => 'الوضوح', 'text' => 'تفكير واضح، وتواصل واضح، ونتائج واضحة.'],
                        ['title' => 'الشراكة', 'text' => 'نعمل امتداداً لفريقك، لا مجرد مورّد للخدمات.'],
                        ['title' => 'الشغف بالتعلّم', 'text' => 'نواصل التعلّم والتجربة وتبنّي التقنيات الحديثة.'],
                    ],
                ],
            ],
            [
                'type' => 'cta',
                'title' => ['en' => 'Ready to make an impact?', 'ar' => 'هل أنت مستعد لصناعة الأثر؟'],
                'body' => [
                    'en' => 'Tell us about your project and we will reply within 24 hours.',
                    'ar' => 'أخبرنا عن مشروعك، وسنرد عليك خلال 24 ساعة.',
                ],
            ],
        ],
    ],

    'services' => [
        'title' => ['en' => 'Our services', 'ar' => 'خدماتنا'],
        'subtitle' => [
            'en' => 'Six integrated disciplines, 43 specialist services, one creative partner.',
            'ar' => 'ستة تخصصات متكاملة، و43 خدمة متخصصة، وشريك إبداعي واحد.',
        ],
        'seo_title' => ['en' => 'Our Services | Pixellap', 'ar' => 'خدماتنا | بكسل لاب'],
        'seo_description' => [
            'en' => 'Brand identity, media production, digital marketing, web development, AI content and training — explore every Pixellap service.',
            'ar' => 'الهوية البصرية والإنتاج الإعلامي والتسويق الرقمي وتطوير المواقع ومحتوى الذكاء الاصطناعي والتدريب — استكشف جميع خدمات بكسل لاب.',
        ],
        'blocks' => [],
    ],

    'contact' => [
        'title' => ['en' => 'Contact us', 'ar' => 'تواصل معنا'],
        'subtitle' => [
            'en' => 'Tell us about your project. We reply within 24 hours.',
            'ar' => 'أخبرنا عن مشروعك، وسنرد عليك خلال 24 ساعة.',
        ],
        'seo_title' => ['en' => 'Contact Us | Pixellap', 'ar' => 'تواصل معنا | بكسل لاب'],
        'seo_description' => [
            'en' => 'Start a project with Pixellap. Get in touch for branding, production, digital marketing and web enquiries across the UAE.',
            'ar' => 'ابدأ مشروعك مع بكسل لاب. تواصل معنا لاستفسارات الهوية البصرية والإنتاج والتسويق الرقمي وتطوير المواقع في الإمارات.',
        ],
        'blocks' => [],
    ],
];
