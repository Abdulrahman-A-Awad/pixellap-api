<?php

declare(strict_types=1);

use App\Models\Faq;
use App\Services\RevalidationService;
use Illuminate\Database\Migrations\Migration;

/**
 * FAQ readability: the seeded answers (home + category FAQs, en + ar) become a short opening
 * sentence + bullet points (rich-text HTML). Only a translation that still equals the ORIGINAL seed
 * text is replaced, matched by text (not by position), so answers editors changed are never touched.
 * Saved quietly per row, then the API cache + website are revalidated once.
 */
return new class extends Migration
{
    /** @return array<string, list<array{0: string, 1: string}>> locale => [original seed, new] */
    private function answers(): array
    {
        return [
            'en' => [
                [
                    'We offer six integrated disciplines: brand identity and design, media and content production, digital marketing, web design and development, creative and AI solutions, and training and workshops. You can work with us on a single project or as a full-service partner.',
                    '<p>We offer six integrated disciplines under one roof:</p><ul><li>Brand identity and design</li><li>Media and content production</li><li>Digital marketing</li><li>Web design and development</li><li>Creative and AI solutions</li><li>Training and workshops</li></ul><p>You can work with us on a single project or as a full-service partner.</p>',
                ],
                [
                    'It depends on scope. A logo or landing page can take two to three weeks; a full brand identity or corporate website usually takes six to ten weeks. We agree a clear timeline before we start.',
                    '<p>It depends on the scope. Typical timelines:</p><ul><li><strong>Logo or landing page:</strong> two to three weeks</li><li><strong>Full brand identity or corporate website:</strong> six to ten weeks</li></ul><p>We agree a clear timeline with you before we start.</p>',
                ],
                [
                    'Every project is quoted individually based on scope, deliverables and timeline. For ongoing work such as social media management, we offer monthly retainers. You will always receive a detailed proposal before any commitment.',
                    '<p>Every project is quoted individually.</p><ul><li><strong>Projects:</strong> priced on scope, deliverables and timeline</li><li><strong>Ongoing work</strong> such as social media management: a monthly retainer</li><li><strong>No surprises:</strong> you receive a detailed proposal before any commitment</li></ul>',
                ],
                [
                    'Yes. Our team creates content, copy and campaigns natively in Arabic and English, so your brand speaks naturally to every audience in the region.',
                    '<p>Yes. Our team creates content, copy and campaigns natively in Arabic and English, so your brand speaks naturally to every audience in the region.</p>',
                ],
                [
                    'We partner with start-ups, SMEs, government entities and established brands across the UAE and the wider GCC, in sectors from real estate and hospitality to retail and technology.',
                    '<p>We partner with organisations of every size across the UAE and the wider GCC:</p><ul><li>Start-ups and SMEs</li><li>Government entities</li><li>Established brands</li></ul><p>Our sectors range from real estate and hospitality to retail and technology.</p>',
                ],
                [
                    'Send us a message through the contact form or WhatsApp. We will arrange a short call to understand your goals, then share a proposal within a few working days.',
                    '<p>Getting started takes three simple steps:</p><ul><li>Send us a message through the contact form or WhatsApp</li><li>We arrange a short call to understand your goals</li><li>You receive a proposal within a few working days</li></ul>',
                ],
                [
                    'A full identity typically takes six to eight weeks, including strategy, concepts, refinements and final guidelines. A logo-only project usually takes two to three weeks.',
                    '<p>It depends on what you need:</p><ul><li><strong>Full identity:</strong> six to eight weeks, covering strategy, concepts, refinements and final guidelines</li><li><strong>Logo only:</strong> two to three weeks</li></ul>',
                ],
                [
                    'Yes. We often evolve existing brands — modernising the logo, tightening the visual system and updating guidelines — while protecting the recognition you have already built.',
                    '<p>Yes. We often evolve existing brands while protecting the recognition you have already built:</p><ul><li>Modernising the logo</li><li>Tightening the visual system</li><li>Updating the brand guidelines</li></ul>',
                ],
                [
                    'Yes. We supply directors, camera operators, lighting, sound and editors, along with professional cinema equipment, so you deal with one team from start to finish.',
                    '<p>Yes. You deal with one team from start to finish:</p><ul><li>Directors, camera operators and editors</li><li>Lighting and sound crew</li><li>Professional cinema equipment</li></ul>',
                ],
                [
                    'Yes. We film across all seven emirates and travel throughout the GCC for productions and events.',
                    '<p>Yes. We film across all seven emirates and travel throughout the GCC for productions and events.</p>',
                ],
                [
                    'Budgets vary by goal and market. We recommend a short test phase to find the best-performing channels, then scale what works. Our management fee is quoted separately from your ad spend.',
                    '<p>Budgets vary by goal and market, so we start small and scale what works:</p><ul><li>A short test phase finds the best-performing channels</li><li>We then scale the channels that deliver</li><li>Our management fee is quoted separately from your ad spend</li></ul>',
                ],
                [
                    'Paid campaigns can deliver results within days. Organic growth and brand awareness build over three to six months of consistent activity. We report monthly, so you can see progress clearly.',
                    '<p>It depends on the channel:</p><ul><li><strong>Paid campaigns:</strong> results within days</li><li><strong>Organic growth and brand awareness:</strong> three to six months of consistent activity</li><li><strong>Monthly reports</strong> so you can see progress clearly</li></ul>',
                ],
                [
                    'Yes. Every website we build is fully bilingual, with proper right-to-left layouts for Arabic — not simply a mirrored or machine-translated version.',
                    '<p>Yes. Every website we build is fully bilingual, with proper right-to-left layouts for Arabic — not simply a mirrored or machine-translated version.</p>',
                ],
                [
                    'Yes. We include an easy-to-use content management system and train your team, so you can update text, images and pages without technical help.',
                    '<p>Yes. You stay in control of your content:</p><ul><li>An easy-to-use content management system</li><li>Training for your team</li><li>Update text, images and pages without technical help</li></ul>',
                ],
                [
                    'We use licensed, commercially cleared tools and review every output for brand fit, accuracy and rights. We will always tell you when traditional production is the better choice.',
                    '<p>Yes, when it is done properly. We:</p><ul><li>Use licensed, commercially cleared tools</li><li>Review every output for brand fit, accuracy and rights</li><li>Tell you when traditional production is the better choice</li></ul>',
                ],
                [
                    'No. AI is a tool our team uses to move faster. Strategy, ideas, art direction and quality control stay in the hands of experienced people.',
                    '<p>No. AI is a tool our team uses to move faster. These stay in the hands of experienced people:</p><ul><li>Strategy and ideas</li><li>Art direction</li><li>Quality control</li></ul>',
                ],
                [
                    'Yes. We deliver training at your offices anywhere in the UAE, or online, depending on what suits your team.',
                    '<p>Yes. We deliver training at your offices anywhere in the UAE, or online, depending on what suits your team.</p>',
                ],
                [
                    'Yes. All courses can be delivered in Arabic or English, with materials provided in the language of your choice.',
                    '<p>Yes. All courses can be delivered in Arabic or English, with materials provided in the language of your choice.</p>',
                ],
            ],
            'ar' => [
                [
                    'نقدم ستة تخصصات متكاملة: بناء الهوية البصرية والتصميم، وإنتاج المحتوى المرئي والمسموع، والتسويق الرقمي، وتصميم المواقع الإلكترونية وتطويرها، والحلول الإبداعية المدعومة بالذكاء الاصطناعي، والتدريب وورش العمل. ويمكنك التعاون معنا في مشروع واحد أو اعتمادنا شريكاً متكاملاً.',
                    '<p>نقدم ستة تخصصات متكاملة تحت سقف واحد:</p><ul><li>بناء الهوية البصرية والتصميم</li><li>إنتاج المحتوى المرئي والمسموع</li><li>التسويق الرقمي</li><li>تصميم المواقع الإلكترونية وتطويرها</li><li>الحلول الإبداعية المدعومة بالذكاء الاصطناعي</li><li>التدريب وورش العمل</li></ul><p>ويمكنك التعاون معنا في مشروع واحد أو اعتمادنا شريكاً متكاملاً.</p>',
                ],
                [
                    'يعتمد ذلك على نطاق العمل؛ فتصميم شعار أو صفحة هبوط قد يستغرق من أسبوعين إلى ثلاثة أسابيع، بينما تستغرق الهوية البصرية المتكاملة أو الموقع المؤسسي عادةً من ستة إلى عشرة أسابيع. ونتفق معك على جدول زمني واضح قبل البدء.',
                    '<p>يعتمد ذلك على نطاق العمل، وهذه هي المدد المعتادة:</p><ul><li><strong>شعار أو صفحة هبوط:</strong> من أسبوعين إلى ثلاثة أسابيع</li><li><strong>هوية بصرية متكاملة أو موقع مؤسسي:</strong> من ستة إلى عشرة أسابيع</li></ul><p>ونتفق معك على جدول زمني واضح قبل البدء.</p>',
                ],
                [
                    'نُعدّ عرض سعر خاصاً لكل مشروع وفق نطاق العمل والمخرجات والجدول الزمني، ونوفر باقات شهرية للخدمات المستمرة مثل إدارة منصات التواصل الاجتماعي. وستتلقى دائماً عرضاً تفصيلياً قبل أي التزام.',
                    '<p>نُعدّ عرض سعر خاصاً لكل مشروع.</p><ul><li><strong>المشاريع:</strong> تُسعَّر وفق نطاق العمل والمخرجات والجدول الزمني</li><li><strong>الخدمات المستمرة</strong> مثل إدارة منصات التواصل الاجتماعي: باقة شهرية</li><li><strong>دون مفاجآت:</strong> تتلقى عرضاً تفصيلياً قبل أي التزام</li></ul>',
                ],
                [
                    'نعم، يُعدّ فريقنا المحتوى والنصوص والحملات باللغتين العربية والإنجليزية بإتقان، لتخاطب علامتك كل جمهور في المنطقة بأسلوب طبيعي ومؤثر.',
                    '<p>نعم، يُعدّ فريقنا المحتوى والنصوص والحملات باللغتين العربية والإنجليزية بإتقان، لتخاطب علامتك كل جمهور في المنطقة بأسلوب طبيعي ومؤثر.</p>',
                ],
                [
                    'نتعاون مع الشركات الناشئة والصغيرة والمتوسطة والجهات الحكومية والعلامات التجارية الراسخة في دولة الإمارات ودول مجلس التعاون الخليجي، في قطاعات تشمل العقارات والضيافة والتجزئة والتكنولوجيا.',
                    '<p>نتعاون مع جهات من مختلف الأحجام في دولة الإمارات ودول مجلس التعاون الخليجي:</p><ul><li>الشركات الناشئة والصغيرة والمتوسطة</li><li>الجهات الحكومية</li><li>العلامات التجارية الراسخة</li></ul><p>وتشمل القطاعات التي نخدمها العقارات والضيافة والتجزئة والتكنولوجيا.</p>',
                ],
                [
                    'أرسل لنا رسالة عبر نموذج التواصل أو واتساب، وسنحدد معك مكالمة قصيرة لفهم أهدافك، ثم نرسل لك عرضاً مفصلاً خلال أيام عمل قليلة.',
                    '<p>نبدأ العمل معاً في ثلاث خطوات بسيطة:</p><ul><li>أرسل لنا رسالة عبر نموذج التواصل أو واتساب</li><li>نحدد معك مكالمة قصيرة لفهم أهدافك</li><li>تتلقى عرضاً مفصلاً خلال أيام عمل قليلة</li></ul>',
                ],
                [
                    'يستغرق مشروع الهوية المتكاملة عادةً من ستة إلى ثمانية أسابيع، تشمل الاستراتيجية والمفاهيم الأولية والتعديلات ودليل الهوية النهائي، بينما يستغرق تصميم الشعار وحده من أسبوعين إلى ثلاثة أسابيع.',
                    '<p>يعتمد ذلك على ما تحتاجه:</p><ul><li><strong>الهوية المتكاملة:</strong> من ستة إلى ثمانية أسابيع، تشمل الاستراتيجية والمفاهيم الأولية والتعديلات ودليل الهوية النهائي</li><li><strong>الشعار وحده:</strong> من أسبوعين إلى ثلاثة أسابيع</li></ul>',
                ],
                [
                    'نعم، كثيراً ما نعمل على تطوير الهويات القائمة من خلال تحديث الشعار وتنظيم المنظومة البصرية وتحديث دليل الهوية، مع الحفاظ على ما بنته علامتك من حضور وتميّز.',
                    '<p>نعم، كثيراً ما نطوّر الهويات القائمة مع الحفاظ على ما بنته علامتك من حضور وتميّز، وذلك من خلال:</p><ul><li>تحديث الشعار</li><li>تنظيم المنظومة البصرية</li><li>تحديث دليل الهوية</li></ul>',
                ],
                [
                    'نعم، نوفر المخرجين والمصورين وفنيي الإضاءة والصوت والمونتاج، إضافة إلى معدات تصوير سينمائية احترافية، لتتعامل مع فريق واحد من البداية حتى النهاية.',
                    '<p>نعم، تتعامل مع فريق واحد من البداية حتى النهاية:</p><ul><li>مخرجون ومصورون وفنيو مونتاج</li><li>فنيو إضاءة وصوت</li><li>معدات تصوير سينمائية احترافية</li></ul>',
                ],
                [
                    'نعم، نصوّر في جميع الإمارات السبع، ونتنقل إلى مختلف دول مجلس التعاون الخليجي لتنفيذ مشاريع الإنتاج وتغطية الفعاليات.',
                    '<p>نعم، نصوّر في جميع الإمارات السبع، ونتنقل إلى مختلف دول مجلس التعاون الخليجي لتنفيذ مشاريع الإنتاج وتغطية الفعاليات.</p>',
                ],
                [
                    'تختلف الميزانية بحسب الهدف والسوق المستهدف. ونوصي بمرحلة تجريبية قصيرة لتحديد القنوات الأعلى أداءً، ثم التوسع فيما يحقق النتائج. وتُحتسب رسوم الإدارة بشكل منفصل عن ميزانية الإعلانات.',
                    '<p>تختلف الميزانية بحسب الهدف والسوق، لذلك نبدأ بخطوات مدروسة ثم نتوسع فيما ينجح:</p><ul><li>مرحلة تجريبية قصيرة لتحديد القنوات الأعلى أداءً</li><li>التوسع في القنوات التي تحقق النتائج</li><li>تُحتسب رسوم الإدارة بشكل منفصل عن ميزانية الإعلانات</li></ul>',
                ],
                [
                    'قد تحقق الحملات المدفوعة نتائج خلال أيام، بينما يحتاج النمو العضوي وبناء الوعي بالعلامة إلى فترة تتراوح بين ثلاثة وستة أشهر من العمل المستمر. ونقدم تقارير شهرية لتتابع التقدم بوضوح.',
                    '<p>يعتمد ذلك على القناة:</p><ul><li><strong>الحملات المدفوعة:</strong> نتائج خلال أيام</li><li><strong>النمو العضوي والوعي بالعلامة:</strong> من ثلاثة إلى ستة أشهر من العمل المستمر</li><li><strong>تقارير شهرية</strong> لتتابع التقدم بوضوح</li></ul>',
                ],
                [
                    'نعم، كل موقع نطوّره ثنائي اللغة بالكامل، مع تصميم عربي سليم من اليمين إلى اليسار، وليس مجرد نسخة معكوسة أو مترجمة آلياً.',
                    '<p>نعم، كل موقع نطوّره ثنائي اللغة بالكامل، مع تصميم عربي سليم من اليمين إلى اليسار، وليس مجرد نسخة معكوسة أو مترجمة آلياً.</p>',
                ],
                [
                    'نعم، نوفر نظام إدارة محتوى سهل الاستخدام وندرّب فريقك عليه، لتتمكن من تحديث النصوص والصور والصفحات دون الحاجة إلى دعم تقني.',
                    '<p>نعم، يبقى محتوى موقعك بين يديك:</p><ul><li>نظام إدارة محتوى سهل الاستخدام</li><li>تدريب فريقك على استخدامه</li><li>تحديث النصوص والصور والصفحات دون الحاجة إلى دعم تقني</li></ul>',
                ],
                [
                    'نستخدم أدوات مرخّصة ومعتمدة للاستخدام التجاري، ونراجع كل مخرج للتأكد من توافقه مع هوية العلامة ودقته وسلامة حقوقه. كما نوضح لك دائماً متى يكون الإنتاج التقليدي هو الخيار الأفضل.',
                    '<p>نعم، عند استخدامه بالطريقة الصحيحة؛ إذ إننا:</p><ul><li>نستخدم أدوات مرخّصة ومعتمدة للاستخدام التجاري</li><li>نراجع كل مخرج للتأكد من توافقه مع هوية العلامة ودقته وسلامة حقوقه</li><li>نوضح لك متى يكون الإنتاج التقليدي هو الخيار الأفضل</li></ul>',
                ],
                [
                    'لا، فالذكاء الاصطناعي أداة يستخدمها فريقنا للعمل بسرعة أكبر، بينما تبقى الاستراتيجية والأفكار والتوجيه الفني ومراقبة الجودة بأيدي خبراء متمرسين.',
                    '<p>لا، فالذكاء الاصطناعي أداة يستخدمها فريقنا للعمل بسرعة أكبر، بينما تبقى المهام التالية بأيدي خبراء متمرسين:</p><ul><li>الاستراتيجية والأفكار</li><li>التوجيه الفني</li><li>مراقبة الجودة</li></ul>',
                ],
                [
                    'نعم، نقدّم التدريب في مقر مؤسستك في أي مكان داخل الإمارات، أو عن بُعد، بحسب ما يناسب فريقك.',
                    '<p>نعم، نقدّم التدريب في مقر مؤسستك في أي مكان داخل الإمارات، أو عن بُعد، بحسب ما يناسب فريقك.</p>',
                ],
                [
                    'نعم، يمكن تقديم جميع الدورات باللغة العربية أو الإنجليزية، مع توفير المواد التدريبية باللغة التي تختارها.',
                    '<p>نعم، يمكن تقديم جميع الدورات باللغة العربية أو الإنجليزية، مع توفير المواد التدريبية باللغة التي تختارها.</p>',
                ],
            ],
        ];
    }

    public function up(): void
    {
        $this->swap(0, 1);
    }

    public function down(): void
    {
        $this->swap(1, 0);
    }

    /** Replace answers equal to pair[$from] with pair[$to]; returns how many translations changed. */
    private function swap(int $from, int $to): int
    {
        $map = [];
        foreach ($this->answers() as $locale => $pairs) {
            foreach ($pairs as $pair) {
                $map[$locale][$pair[$from]] = $pair[$to];
            }
        }

        $revalidation = app(RevalidationService::class);
        $changed = 0;
        foreach (Faq::query()->get() as $faq) {
            $dirty = false;
            foreach ($map as $locale => $replacements) {
                $current = $faq->getTranslation('answer', $locale, false);
                if (is_string($current) && isset($replacements[$current])) {
                    $faq->setTranslation('answer', $locale, $replacements[$current]);
                    $dirty = true;
                    $changed++;
                }
            }
            if ($dirty) {
                $faq->saveQuietly();
                $revalidation->touchModel($faq);
            }
        }

        $revalidation->flush(); // no-op when nothing changed

        echo "  FAQ answers updated: {$changed}\n";

        return $changed;
    }
};
