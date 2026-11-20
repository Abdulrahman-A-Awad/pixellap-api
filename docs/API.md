# Pixellap API v1 — frontend guide

The Laravel API that the Next.js site (`../pixellap-web`) reads from. It is **read-only**, except for
the contact form. TypeScript types for every response are in [`api-types.ts`](./api-types.ts). Copy
that file into the Next.js app unchanged.

- **Base URL:** `{APP_URL}/api/v1` (locally `http://localhost:8000/api/v1`)
- **Format:** JSON only, UTF-8. Errors are JSON too, whatever `Accept` header you send.
- **Examples:** every response below is real output from the seeded database. The seed has no
  images yet, so all media fields are `null`. See [MediaAsset](#mediaasset) for the shape they
  will have.

## Contents

1. [Conventions](#conventions)
2. [Endpoints](#endpoints): [site](#get-site) · [home](#get-home) · [pages](#get-pageskey) ·
   [service categories](#get-service-categories) · [category](#get-service-categoriesslug) ·
   [sitemap](#get-sitemap) · [contact](#post-contact)
3. [Errors](#errors)
4. [Caching and revalidation](#caching-and-revalidation)

---

## Conventions

### Headers and query parameters

| | Applies to | Notes |
|---|---|---|
| `X-Site-Key: {API_SITE_KEY}` | all `GET` endpoints | Required. A wrong or missing key returns **401**. Exception: when the API runs with `APP_ENV=local`, a *missing* header is allowed so you can open endpoints in a browser. A header that *is* sent must still be correct. Keep the key server-side: never expose it to the browser. |
| `?locale=en\|ar` | all endpoints | Selects the language. If it is missing, the API reads `Accept-Language`, then falls back to `en`. The resolved locale is echoed in `meta.locale` and the `Content-Language` header. |
| `Accept: application/json` | optional | Errors are JSON regardless. |

### Envelope

Every successful response has this shape:

```json
{ "data": "…", "meta": { "locale": "ar", "generated_at": "2026-11-13T16:04:26+04:00" } }
```

`generated_at` is when the payload was built. GET responses are cached server-side, so this can be
up to one hour old, or older, until content changes (see [Caching](#caching-and-revalidation)).

### Content rules

- **One language per response.** Strings are already in the requested locale. You never get
  `{en, ar}` objects. Missing Arabic text falls back to English.
- **Text formats.** FAQ answers and category descriptions are **HTML** from the admin rich editor;
  render them with `dangerouslySetInnerHTML` (seeded rows contain plain text, which is valid HTML).
  Other multi-line fields (`subtitle`, `description`, block `body`, item `text`) are **plain text**;
  split paragraphs on blank lines (`\n\n`).
- **Visibility and order.** Hidden rows never appear. Every list is already in display order.
- **Dates** are ISO 8601 with offset. **Slugs** are English-only and shared by both locales.
- **Nullable fields** are always present, with value `null`, so you can check `=== null` safely.
- Settings that still contain `TODO:` placeholders are returned as-is until editors fill them in.

### MediaAsset

Every image or video field is either `null` or this object (illustrative sample; the seed has no media yet):

```json
{
  "url": "http://localhost:8000/storage/12/cover.jpg",
  "mime": "image/jpeg",
  "width": 2400,
  "height": 1600,
  "alt": "Brand Identity & Design",
  "conversions": {
    "thumb": "http://localhost:8000/storage/12/conversions/cover-thumb.webp",
    "md": "http://localhost:8000/storage/12/conversions/cover-md.webp",
    "lg": "http://localhost:8000/storage/12/conversions/cover-lg.webp"
  }
}
```

- URLs are absolute (built from the API's `APP_URL`). Add the host to `images.remotePatterns` in
  `next.config`.
- `conversions` are WebP renditions 400 / 1024 / 1920 px wide. They are **`null` for SVG and video**:
  use `url` directly. A rendition that has not been generated yet points to the original.
- `width`/`height` are `null` for SVG, video, or when they are unknown.
- `alt` is the owner's title (a client's name for logos) in the current locale. It may be `""`.

---

## Endpoints

Rate limit for all GET endpoints: **120 requests/minute per IP**. All of them sit behind the `site.key` check.

| Method | Path | Cache tags | Returns |
|---|---|---|---|
| GET | `/site` | `site` | `SiteResponse` |
| GET | `/home` | `home` | `HomeResponse` |
| GET | `/pages/{key}` | `page:{key}` | `PageResponse` |
| GET | `/service-categories` | `categories` | `ServiceCategoriesResponse` |
| GET | `/service-categories/{slug}` | `category:{slug}` | `ServiceCategoryResponse` |
| GET | `/sitemap` | `sitemap` | `SitemapResponse` |
| POST | `/contact` | — | `ContactResponse` |

Use the **cache tag** column as the Next.js `fetch(..., { next: { tags: [...] } })` tag for each call.
The API sends the same strings to the revalidate webhook.

### GET /site

Global layout data: public settings (contact details, socials, SEO defaults), every interface string
as a flat `{key: value}` map, the categories for the navigation, and the available locales. Fetch it
once per request in the root layout.

`settings.og_image` is the default social sharing image (1200×630) as an **absolute URL**, or `null`
until an editor uploads one (Admin → Site settings → SEO). Use it as the Open Graph image when a
page has no image of its own.

```ts
const site = await api<SiteResponse>('/site', { locale, tags: ['site'] });
site.data.ui_strings['nav.services']; // "Services" | "خدماتنا"
```

`GET /api/v1/site?locale=en`

```json
// 200
{
  "data": {
    "settings": {
      "site_name": "Pixellap",
      "tagline": "Small Pixels. BIG IMPACT.",
      "showreel_url": "",
      "email": "TODO: hello@ppixelmedia.com",
      "phone": "TODO: +971 4 000 0000",
      "whatsapp": "TODO: +971500000000",
      "address": "TODO: Office address, Dubai, United Arab Emirates",
      "map_embed_url": "",
      "working_hours": "Monday – Friday, 9:00 am – 6:00 pm",
      "vat_number": "TODO: 100000000000000",
      "response_time": "We reply within 24 hours.",
      "socials": {
        "instagram": "",
        "linkedin": "",
        "tiktok": "",
        "youtube": "",
        "x": "",
        "behance": ""
      },
      "seo_default_title": "Pixellap — Creative, Media & Digital Agency in the UAE",
      "seo_default_description": "Pixellap brings brand identity, media production, digital marketing, web development and AI-powered content together under one roof for businesses across the UAE and GCC.",
      "og_image": null
    },
    "ui_strings": {
      "actions.back_home": "Back to home",
      "actions.explore_services": "Explore our services",
      "actions.learn_more": "Learn more",
      "actions.send_message": "Send message",
      "actions.start_project": "Start a project",
      "actions.view_all": "View all",
      "actions.watch_reel": "Watch the reel",
      "footer.follow_us": "Follow us",
      "footer.quick_links": "Quick links",
      "footer.rights": "All rights reserved.",
      "footer.vat": "VAT No.",
      "footer.working_hours": "Working hours",
      "form.company": "Company",
      "form.email": "Email address",
      "form.error": "Something went wrong. Please try again.",
      "form.invalid_email": "Please enter a valid email address.",
      "form.message": "Tell us about your project",
      "form.name": "Full name",
      "form.phone": "Phone number",
      "form.required": "This field is required.",
      "form.select_service": "Select a service",
      "form.service": "Service of interest",
      "form.success": "Thank you. Your message has been sent and we will be in touch shortly.",
      "misc.close": "Close",
      "misc.lang_switch_label": "العربية",
      "misc.menu": "Menu",
      "misc.not_found_text": "The page you are looking for has moved or no longer exists.",
      "misc.not_found_title": "Page not found",
      "misc.scroll": "Scroll",
      "misc.theme_dark": "Dark mode",
      "misc.theme_light": "Light mode",
      "nav.about": "About",
      "nav.contact": "Contact",
      "nav.home": "Home",
      "nav.services": "Services"
    },
    "nav": {
      "categories": [
        {
          "slug": "brand-identity-design",
          "title": "Brand Identity & Design",
          "sort": 1
        },
        {
          "slug": "media-content-production",
          "title": "Media & Content Production",
          "sort": 2
        },
        {
          "slug": "digital-marketing",
          "title": "Digital Marketing",
          "sort": 3
        },
        {
          "slug": "web-design-development",
          "title": "Web Design & Development",
          "sort": 4
        },
        {
          "slug": "creative-ai-solutions",
          "title": "Creative & AI Solutions",
          "sort": 5
        },
        {
          "slug": "training-workshops",
          "title": "Training & Workshops",
          "sort": 6
        }
      ]
    },
    "locales": [
      "en",
      "ar"
    ]
  },
  "meta": {
    "locale": "en",
    "generated_at": "2026-11-13T16:16:06+04:00"
  }
}
```

### GET /home

The home page's visible sections, in order. The result is a **union discriminated by `key`**: render
with a `switch (section.key)`. Every section has `eyebrow, title, subtitle, cta, cta2, image`.
Fields a section type does not use are always `null`. Extra data per key:

| key | Extra field(s) |
|---|---|
| `hero` | `media: HeroMedia` (loop video variants; uses `cta`, `cta2`; see the hero media notes below) |
| `stats` | `items: HomeStat[]` (`value` is a number; animate it, then wrap with `prefix`/`suffix`) |
| `services` | `categories: HomeCategoryCard[]` (each with `services: string[]` titles for tags) |
| `process` | `steps: ProcessStep[]` (`title`, `description`) |
| `clients` | `items: ClientLogo[]` (empty until logos are added; hide the section when it is empty) |
| `about` | — (uses `cta`, `image`) |
| `faq` | `faqs: Faq[]` (home FAQs only) |
| `contact_cta` | `response_time`, `whatsapp` (uses `title`, `subtitle`, `cta`; `eyebrow` and `image` are always `null`) |

Sections can be hidden or reordered in the admin, so never assume a fixed position.

**Hero media.** The hero is a 3D scene in the browser; `media` holds its loop video fallbacks, shown
instead of the scene on phones and low-power devices. Two orientations, each with three absolute URLs
(any of them may be `null` until uploaded):

```json
"media": {
  "wide":     { "webm": "…/hero-wide.webm",     "mp4": "…/hero-wide.mp4",     "poster": "…/hero-wide-poster.webp" },
  "vertical": { "webm": "…/hero-vertical.webm", "mp4": "…/hero-vertical.mp4", "poster": "…/hero-vertical-poster.webp" }
}
```

- Pick `vertical` for `(max-width: 767px)` or portrait orientation, else `wide`.
- Videos are silent, 6–10 s seamless loops, ≤ 6 MB each (the admin enforces it). Play them
  `muted playsInline loop autoPlay` with `<source type="video/webm">` first, then `<source type="video/mp4">`.
- `poster` is the first frame (≤ 1 MB, served as uploaded, no conversions): use it as the video `poster`
  and as the static hero (reduced motion).
- `image` is always `null` for the hero. The old `video` field was removed from every section.
- Upload from the admin (Home sections → Hero → Loop videos) or `php artisan pixellap:attach-hero-media {dir}`.

`GET /api/v1/home?locale=en`

```json
// 200
{
  "data": [
    {
      "key": "hero",
      "eyebrow": "Creative | Media | Digital — UAE",
      "title": "Small Pixels. BIG IMPACT.",
      "subtitle": "Integrated branding, media production, digital marketing and web — all under one roof.",
      "cta": {
        "label": "Start a project",
        "url": "/contact"
      },
      "cta2": {
        "label": "Watch the reel",
        "url": "#reel"
      },
      "image": null,
      "media": {
        "wide": { "webm": null, "mp4": null, "poster": null },
        "vertical": { "webm": null, "mp4": null, "poster": null }
      }
    },
    {
      "key": "stats",
      "eyebrow": "By the numbers",
      "title": "Results that speak for themselves.",
      "subtitle": null,
      "cta": null,
      "cta2": null,
      "image": null,
      "items": [
        {
          "value": 10,
          "prefix": null,
          "suffix": "+",
          "label": "Years of experience (placeholder)"
        },
        {
          "value": 250,
          "prefix": null,
          "suffix": "+",
          "label": "Projects delivered (placeholder)"
        },
        {
          "value": 120,
          "prefix": null,
          "suffix": "+",
          "label": "Clients served (placeholder)"
        },
        {
          "value": 43,
          "prefix": null,
          "suffix": null,
          "label": "Specialist services"
        }
      ]
    },
    {
      "key": "services",
      "eyebrow": "What we do",
      "title": "Six disciplines. One creative partner.",
      "subtitle": "From brand strategy to AI-powered content, we cover every stage of your brand’s growth.",
      "cta": {
        "label": "Explore our services",
        "url": "/services"
      },
      "cta2": null,
      "image": null,
      "categories": [
        {
          "slug": "brand-identity-design",
          "title": "Brand Identity & Design",
          "excerpt": "Distinctive brands, built on strategy and crafted with care.",
          "icon": null,
          "cover": null,
          "services": [
            "Brand Strategy & Positioning",
            "Visual Identity Design",
            "Logo Design",
            "Brand Guidelines",
            "Graphic Design",
            "Marketing & Advertising Materials",
            "Packaging Design"
          ]
        },
        {
          "slug": "media-content-production",
          "title": "Media & Content Production",
          "excerpt": "Video, photography and audio that capture attention.",
          "icon": null,
          "cover": null,
          "services": [
            "Commercial Video Production",
            "Corporate Videos",
            "Event Coverage",
            "Photography",
            "Product Photography & Videography",
            "Social Media Content Production",
            "Podcast Production",
            "Video Editing & Post-production",
            "Motion Graphics & Animation"
          ]
        },
        {
          "slug": "digital-marketing",
          "title": "Digital Marketing",
          "excerpt": "Data-led campaigns that grow audiences and revenue.",
          "icon": null,
          "cover": null,
          "services": [
            "Social Media Management",
            "Digital Marketing Strategy",
            "Social Media Marketing",
            "Paid Advertising Campaigns",
            "Content Strategy & Planning",
            "Influencer Marketing",
            "Campaign Management",
            "Performance Analysis & Reporting"
          ]
        },
        {
          "slug": "web-design-development",
          "title": "Web Design & Development",
          "excerpt": "Fast, bilingual websites designed to convert.",
          "icon": null,
          "cover": null,
          "services": [
            "Website Design (UI/UX)",
            "Website Development",
            "Corporate Websites",
            "Landing Page Design",
            "E-commerce Websites",
            "Website Maintenance & Support",
            "Responsive Web Design"
          ]
        },
        {
          "slug": "creative-ai-solutions",
          "title": "Creative & AI Solutions",
          "excerpt": "Creative thinking, amplified by artificial intelligence.",
          "icon": null,
          "cover": null,
          "services": [
            "AI-Powered Image Generation",
            "AI Video Production",
            "AI Commercial Content",
            "Creative Campaign Concepts",
            "AI Visual Effects",
            "AI Content Workflows"
          ]
        },
        {
          "slug": "training-workshops",
          "title": "Training & Workshops",
          "excerpt": "Practical training that builds in-house creative skills.",
          "icon": null,
          "cover": null,
          "services": [
            "Content Creation Training",
            "Photography & Videography Workshops",
            "Video Editing Training",
            "AI Content Creation Training",
            "Corporate Media Training",
            "Customized Training Programs"
          ]
        }
      ]
    },
    {
      "key": "process",
      "eyebrow": "How we work",
      "title": "A clear process, from brief to launch.",
      "subtitle": null,
      "cta": null,
      "cta2": null,
      "image": null,
      "steps": [
        {
          "title": "Discover & Brief",
          "description": "We learn about your business, audience and goals, then agree a clear brief, scope and timeline."
        },
        {
          "title": "Concept & Strategy",
          "description": "We shape the strategy and creative direction, and share concepts for your feedback before production begins."
        },
        {
          "title": "Production",
          "description": "Our designers, filmmakers and developers bring the work to life, with regular check-ins along the way."
        },
        {
          "title": "Launch & Grow",
          "description": "We launch, measure performance and refine, so your brand keeps growing after go-live."
        }
      ]
    },
    {
      "key": "clients",
      "eyebrow": "Trusted by",
      "title": "Brands we have worked with.",
      "subtitle": null,
      "cta": null,
      "cta2": null,
      "image": null,
      "items": []
    },
    {
      "key": "about",
      "eyebrow": "About Pixellap",
      "title": "A UAE agency built on craft and clarity.",
      "subtitle": "We are strategists, designers, filmmakers and marketers helping brands across the GCC look sharper, speak clearer and grow faster.",
      "cta": {
        "label": "Learn more",
        "url": "/about"
      },
      "cta2": null,
      "image": null
    },
    {
      "key": "faq",
      "eyebrow": "FAQ",
      "title": "Questions, answered.",
      "subtitle": null,
      "cta": null,
      "cta2": null,
      "image": null,
      "faqs": [
        {
          "id": 1,
          "question": "What services does Pixellap offer?",
          "answer": "We offer six integrated disciplines: brand identity and design, media and content production, digital marketing, web design and development, creative and AI solutions, and training and workshops. You can work with us on a single project or as a full-service partner."
        },
        {
          "id": 2,
          "question": "How long does a typical project take?",
          "answer": "It depends on scope. A logo or landing page can take two to three weeks; a full brand identity or corporate website usually takes six to ten weeks. We agree a clear timeline before we start."
        },
        {
          "id": 3,
          "question": "How do you price your work?",
          "answer": "Every project is quoted individually based on scope, deliverables and timeline. For ongoing work such as social media management, we offer monthly retainers. You will always receive a detailed proposal before any commitment."
        },
        {
          "id": 4,
          "question": "Do you work in both Arabic and English?",
          "answer": "Yes. Our team creates content, copy and campaigns natively in Arabic and English, so your brand speaks naturally to every audience in the region."
        },
        {
          "id": 5,
          "question": "Who do you work with?",
          "answer": "We partner with start-ups, SMEs, government entities and established brands across the UAE and the wider GCC, in sectors from real estate and hospitality to retail and technology."
        },
        {
          "id": 6,
          "question": "How do we get started?",
          "answer": "Send us a message through the contact form or WhatsApp. We will arrange a short call to understand your goals, then share a proposal within a few working days."
        }
      ]
    },
    {
      "key": "contact_cta",
      "eyebrow": null,
      "title": "Have a project in mind?",
      "subtitle": "Tell us what you are building. We reply within 24 hours.",
      "cta": {
        "label": "Start a project",
        "url": "/contact"
      },
      "cta2": null,
      "image": null,
      "response_time": "We reply within 24 hours.",
      "whatsapp": "TODO: +971500000000"
    }
  ],
  "meta": {
    "locale": "en",
    "generated_at": "2026-11-13T16:16:07+04:00"
  }
}
```

`GET /api/v1/home?locale=ar`

```json
// 200
{
  "data": [
    {
      "key": "hero",
      "eyebrow": "إبداع | إعلام | رقمي — الإمارات",
      "title": "بيكسلات صغيرة. أثر كبير.",
      "subtitle": "هوية بصرية وإنتاج إعلامي وتسويق رقمي وتطوير مواقع، متكاملة تحت سقف واحد.",
      "cta": {
        "label": "ابدأ مشروعك",
        "url": "/contact"
      },
      "cta2": {
        "label": "شاهد العرض التعريفي",
        "url": "#reel"
      },
      "image": null,
      "media": {
        "wide": { "webm": null, "mp4": null, "poster": null },
        "vertical": { "webm": null, "mp4": null, "poster": null }
      }
    },
    {
      "key": "stats",
      "eyebrow": "أرقامنا",
      "title": "نتائج تتحدث عن نفسها.",
      "subtitle": null,
      "cta": null,
      "cta2": null,
      "image": null,
      "items": [
        {
          "value": 10,
          "prefix": null,
          "suffix": "+",
          "label": "سنوات من الخبرة (قيمة مؤقتة)"
        },
        {
          "value": 250,
          "prefix": null,
          "suffix": "+",
          "label": "مشروع منجز (قيمة مؤقتة)"
        },
        {
          "value": 120,
          "prefix": null,
          "suffix": "+",
          "label": "عميل وثق بنا (قيمة مؤقتة)"
        },
        {
          "value": 43,
          "prefix": null,
          "suffix": null,
          "label": "خدمة متخصصة"
        }
      ]
    },
    {
      "key": "services",
      "eyebrow": "ماذا نقدّم",
      "title": "ستة تخصصات. شريك إبداعي واحد.",
      "subtitle": "من استراتيجية العلامة التجارية إلى المحتوى المدعوم بالذكاء الاصطناعي، نغطي كل مرحلة من مراحل نمو علامتك.",
      "cta": {
        "label": "استكشف خدماتنا",
        "url": "/services"
      },
      "cta2": null,
      "image": null,
      "categories": [
        {
          "slug": "brand-identity-design",
          "title": "بناء الهوية البصرية والتصميم الإبداعي",
          "excerpt": "علامات تجارية مميزة، تقوم على الاستراتيجية وتُصمَّم بعناية.",
          "icon": null,
          "cover": null,
          "services": [
            "استراتيجية العلامة التجارية وتحديد موقعها",
            "تصميم الهوية البصرية",
            "تصميم الشعارات",
            "دليل الهوية البصرية",
            "التصميم الجرافيكي",
            "المواد التسويقية والإعلانية",
            "تصميم التغليف"
          ]
        },
        {
          "slug": "media-content-production",
          "title": "إنتاج المحتوى المرئي والمسموع",
          "excerpt": "فيديو وتصوير فوتوغرافي ومحتوى صوتي يأسر الانتباه.",
          "icon": null,
          "cover": null,
          "services": [
            "إنتاج الإعلانات التجارية",
            "الأفلام المؤسسية",
            "تغطية الفعاليات",
            "التصوير الفوتوغرافي",
            "تصوير المنتجات فوتوغرافياً وبالفيديو",
            "إنتاج محتوى منصات التواصل الاجتماعي",
            "إنتاج البودكاست",
            "المونتاج وما بعد الإنتاج",
            "الموشن جرافيك والرسوم المتحركة"
          ]
        },
        {
          "slug": "digital-marketing",
          "title": "التسويق الرقمي وإدارة الحضور الإلكتروني للعلامات التجارية",
          "excerpt": "حملات مبنية على البيانات تنمّي جمهورك وتزيد إيراداتك.",
          "icon": null,
          "cover": null,
          "services": [
            "إدارة منصات التواصل الاجتماعي",
            "استراتيجية التسويق الرقمي",
            "التسويق عبر منصات التواصل الاجتماعي",
            "الحملات الإعلانية المدفوعة",
            "استراتيجية المحتوى والتخطيط له",
            "التسويق عبر المؤثرين",
            "إدارة الحملات",
            "تحليل الأداء وإعداد التقارير"
          ]
        },
        {
          "slug": "web-design-development",
          "title": "تصميم وتطوير المواقع الإلكترونية",
          "excerpt": "مواقع إلكترونية سريعة وثنائية اللغة مصممة لتحقيق النتائج.",
          "icon": null,
          "cover": null,
          "services": [
            "تصميم واجهات المواقع وتجربة المستخدم (UI/UX)",
            "تطوير المواقع الإلكترونية",
            "المواقع المؤسسية",
            "تصميم صفحات الهبوط",
            "المتاجر الإلكترونية",
            "صيانة المواقع والدعم الفني",
            "تصميم المواقع المتجاوبة"
          ]
        },
        {
          "slug": "creative-ai-solutions",
          "title": "الحلول الإبداعية والتقنيات الحديثة في صناعة المحتوى",
          "excerpt": "تفكير إبداعي تعززه تقنيات الذكاء الاصطناعي.",
          "icon": null,
          "cover": null,
          "services": [
            "توليد الصور بالذكاء الاصطناعي",
            "إنتاج الفيديو بالذكاء الاصطناعي",
            "المحتوى الإعلاني بالذكاء الاصطناعي",
            "الأفكار الإبداعية للحملات",
            "المؤثرات البصرية بالذكاء الاصطناعي",
            "منظومات إنتاج المحتوى بالذكاء الاصطناعي"
          ]
        },
        {
          "slug": "training-workshops",
          "title": "التدريب والتطوير في مجال صناعة المحتوى والإنتاج الإبداعي",
          "excerpt": "تدريب عملي يبني المهارات الإبداعية داخل مؤسستك.",
          "icon": null,
          "cover": null,
          "services": [
            "التدريب على صناعة المحتوى",
            "ورش عمل التصوير الفوتوغرافي والفيديو",
            "التدريب على المونتاج",
            "التدريب على صناعة المحتوى بالذكاء الاصطناعي",
            "التدريب الإعلامي للمؤسسات",
            "برامج تدريبية مخصصة"
          ]
        }
      ]
    },
    {
      "key": "process",
      "eyebrow": "منهجية عملنا",
      "title": "منهجية واضحة من الفكرة إلى الإطلاق.",
      "subtitle": null,
      "cta": null,
      "cta2": null,
      "image": null,
      "steps": [
        {
          "title": "الاكتشاف وتحديد المتطلبات",
          "description": "نتعرّف على نشاطك التجاري وجمهورك وأهدافك، ثم نتفق على موجز واضح ونطاق عمل وجدول زمني محدد."
        },
        {
          "title": "الفكرة والاستراتيجية",
          "description": "نضع الاستراتيجية والتوجه الإبداعي، ونعرض عليك المفاهيم الأولية لإبداء ملاحظاتك قبل بدء التنفيذ."
        },
        {
          "title": "الإنتاج",
          "description": "يتولى فريقنا من المصممين وصنّاع المحتوى والمطورين تنفيذ العمل، مع متابعة دورية معك في كل مرحلة."
        },
        {
          "title": "الإطلاق والنمو",
          "description": "نطلق العمل ونقيس الأداء ونواصل التحسين، لتستمر علامتك في النمو بعد الإطلاق."
        }
      ]
    },
    {
      "key": "clients",
      "eyebrow": "شركاء النجاح",
      "title": "علامات تجارية عملنا معها.",
      "subtitle": null,
      "cta": null,
      "cta2": null,
      "image": null,
      "items": []
    },
    {
      "key": "about",
      "eyebrow": "عن Pixellap",
      "title": "وكالة إماراتية تقوم على الإتقان والوضوح.",
      "subtitle": "فريق من الاستراتيجيين والمصممين وصنّاع الأفلام والمسوّقين، نساعد العلامات التجارية في دول الخليج على الظهور بصورة أكثر تميزاً، والتواصل برسائل أوضح، والنمو بوتيرة أسرع.",
      "cta": {
        "label": "اعرف المزيد",
        "url": "/about"
      },
      "cta2": null,
      "image": null
    },
    {
      "key": "faq",
      "eyebrow": "الأسئلة الشائعة",
      "title": "إجابات لأسئلتكم.",
      "subtitle": null,
      "cta": null,
      "cta2": null,
      "image": null,
      "faqs": [
        {
          "id": 1,
          "question": "ما الخدمات التي تقدمها Pixellap؟",
          "answer": "نقدم ستة تخصصات متكاملة: بناء الهوية البصرية والتصميم، وإنتاج المحتوى المرئي والمسموع، والتسويق الرقمي، وتصميم المواقع الإلكترونية وتطويرها، والحلول الإبداعية المدعومة بالذكاء الاصطناعي، والتدريب وورش العمل. ويمكنك التعاون معنا في مشروع واحد أو اعتمادنا شريكاً متكاملاً."
        },
        {
          "id": 2,
          "question": "كم يستغرق تنفيذ المشروع عادةً؟",
          "answer": "يعتمد ذلك على نطاق العمل؛ فتصميم شعار أو صفحة هبوط قد يستغرق من أسبوعين إلى ثلاثة أسابيع، بينما تستغرق الهوية البصرية المتكاملة أو الموقع المؤسسي عادةً من ستة إلى عشرة أسابيع. ونتفق معك على جدول زمني واضح قبل البدء."
        },
        {
          "id": 3,
          "question": "كيف تحددون تكلفة المشاريع؟",
          "answer": "نُعدّ عرض سعر خاصاً لكل مشروع وفق نطاق العمل والمخرجات والجدول الزمني، ونوفر باقات شهرية للخدمات المستمرة مثل إدارة منصات التواصل الاجتماعي. وستتلقى دائماً عرضاً تفصيلياً قبل أي التزام."
        },
        {
          "id": 4,
          "question": "هل تعملون باللغتين العربية والإنجليزية؟",
          "answer": "نعم، يُعدّ فريقنا المحتوى والنصوص والحملات باللغتين العربية والإنجليزية بإتقان، لتخاطب علامتك كل جمهور في المنطقة بأسلوب طبيعي ومؤثر."
        },
        {
          "id": 5,
          "question": "مع من تعملون؟",
          "answer": "نتعاون مع الشركات الناشئة والصغيرة والمتوسطة والجهات الحكومية والعلامات التجارية الراسخة في دولة الإمارات ودول مجلس التعاون الخليجي، في قطاعات تشمل العقارات والضيافة والتجزئة والتكنولوجيا."
        },
        {
          "id": 6,
          "question": "كيف نبدأ العمل معاً؟",
          "answer": "أرسل لنا رسالة عبر نموذج التواصل أو واتساب، وسنحدد معك مكالمة قصيرة لفهم أهدافك، ثم نرسل لك عرضاً مفصلاً خلال أيام عمل قليلة."
        }
      ]
    },
    {
      "key": "contact_cta",
      "eyebrow": null,
      "title": "هل لديك مشروع تفكر فيه؟",
      "subtitle": "أخبرنا عن مشروعك، وسنرد عليك خلال 24 ساعة.",
      "cta": {
        "label": "ابدأ مشروعك",
        "url": "/contact"
      },
      "cta2": null,
      "image": null,
      "response_time": "نرد على جميع الاستفسارات خلال 24 ساعة.",
      "whatsapp": "TODO: +971500000000"
    }
  ],
  "meta": {
    "locale": "ar",
    "generated_at": "2026-11-13T16:16:07+04:00"
  }
}
```

### GET /pages/{key}

`key` ∈ `about` · `services` · `contact`. Any other key returns **404**. The response contains the page header,
SEO, an optional hero image, and visible **blocks**, a union discriminated by `type`:

| type | Fields (besides `id`, `type`, `title`) |
|---|---|
| `text` | `body` (plain text) |
| `image_text` | `body`, `image` |
| `mission_vision` | `items` — exactly 2 × `{title, text}` |
| `values` | `items` — 1–6 × `{title, text}` |
| `cta` | `body` (render a button to `/contact` with `ui_strings['actions.start_project']`) |

`seo.title` / `seo.description` may be `null`: fall back to `site.settings.seo_default_*`.

`GET /api/v1/pages/about?locale=en`

```json
// 200
{
  "data": {
    "key": "about",
    "title": "About Pixellap",
    "subtitle": "A creative, media and digital agency built in the UAE.",
    "seo": {
      "title": "About Us | Pixellap",
      "description": "Meet Pixellap, a UAE creative agency bringing branding, media production, digital marketing and web together under one roof."
    },
    "hero": null,
    "blocks": [
      {
        "id": 1,
        "type": "text",
        "title": "Who we are",
        "body": "Pixellap is a creative, media and digital agency based in the UAE. We bring strategists, designers, filmmakers, marketers and developers together in one team, so our clients get joined-up thinking and consistent quality at every step.\n\nWe believe the smallest details make the biggest difference. Every pixel, frame and word is crafted with purpose — because that is how brands earn attention and trust."
      },
      {
        "id": 2,
        "type": "mission_vision",
        "title": "Mission & vision",
        "items": [
          {
            "title": "Our mission",
            "text": "To help brands in the UAE and the region grow through clear strategy, outstanding creative work and measurable results."
          },
          {
            "title": "Our vision",
            "text": "To be the region’s most trusted creative partner, known for small details that create big impact."
          }
        ]
      },
      {
        "id": 3,
        "type": "values",
        "title": "Our values",
        "items": [
          {
            "title": "Craft",
            "text": "We care about every detail, because quality is never an accident."
          },
          {
            "title": "Clarity",
            "text": "Clear thinking, clear communication, clear results."
          },
          {
            "title": "Partnership",
            "text": "We work as an extension of your team, not as a supplier."
          },
          {
            "title": "Curiosity",
            "text": "We keep learning, testing and embracing new technology."
          }
        ]
      },
      {
        "id": 4,
        "type": "cta",
        "title": "Ready to make an impact?",
        "body": "Tell us about your project and we will reply within 24 hours."
      }
    ],
    "updated_at": "2026-11-13T16:16:01+04:00"
  },
  "meta": {
    "locale": "en",
    "generated_at": "2026-11-13T16:16:07+04:00"
  }
}
```

### GET /service-categories

Visible categories with the number of visible services in each. Use it for the services index page
and for `generateStaticParams`.

`GET /api/v1/service-categories?locale=en`

```json
// 200
{
  "data": [
    {
      "slug": "brand-identity-design",
      "title": "Brand Identity & Design",
      "excerpt": "Distinctive brands, built on strategy and crafted with care.",
      "icon": null,
      "cover": null,
      "services_count": 7
    },
    {
      "slug": "media-content-production",
      "title": "Media & Content Production",
      "excerpt": "Video, photography and audio that capture attention.",
      "icon": null,
      "cover": null,
      "services_count": 9
    },
    {
      "slug": "digital-marketing",
      "title": "Digital Marketing",
      "excerpt": "Data-led campaigns that grow audiences and revenue.",
      "icon": null,
      "cover": null,
      "services_count": 8
    },
    {
      "slug": "web-design-development",
      "title": "Web Design & Development",
      "excerpt": "Fast, bilingual websites designed to convert.",
      "icon": null,
      "cover": null,
      "services_count": 7
    },
    {
      "slug": "creative-ai-solutions",
      "title": "Creative & AI Solutions",
      "excerpt": "Creative thinking, amplified by artificial intelligence.",
      "icon": null,
      "cover": null,
      "services_count": 6
    },
    {
      "slug": "training-workshops",
      "title": "Training & Workshops",
      "excerpt": "Practical training that builds in-house creative skills.",
      "icon": null,
      "cover": null,
      "services_count": 6
    }
  ],
  "meta": {
    "locale": "en",
    "generated_at": "2026-11-13T16:16:07+04:00"
  }
}
```

### GET /service-categories/{slug}

One category with its visible services (each with `features: string[]`) and its own visible FAQs.
A hidden or unknown slug returns **404**: call `notFound()`.
`description` is HTML.

`GET /api/v1/service-categories/brand-identity-design?locale=en`

```json
// 200
{
  "data": {
    "slug": "brand-identity-design",
    "title": "Brand Identity & Design",
    "excerpt": "Distinctive brands, built on strategy and crafted with care.",
    "description": "We build brands that are clear in purpose and consistent in every detail. From positioning to logos, guidelines and packaging, we give your business an identity that earns recognition and trust.",
    "seo": {
      "title": "Brand Identity & Design Agency in the UAE | Pixellap",
      "description": "Brand strategy, logo design, visual identity and packaging for businesses across the UAE and GCC."
    },
    "icon": null,
    "cover": null,
    "hero_video": null,
    "services": [
      {
        "id": 1,
        "title": "Brand Strategy & Positioning",
        "description": "We define what your brand stands for, who it serves and how it differs from competitors.",
        "features": [
          "Market and competitor research",
          "Brand purpose, values and personality",
          "Positioning and messaging framework"
        ],
        "icon": null,
        "image": null
      },
      {
        "id": 2,
        "title": "Visual Identity Design",
        "description": "A complete visual system — colour, typography, imagery and graphic elements — that makes your brand instantly recognisable.",
        "features": [
          "Colour palette and typography",
          "Graphic elements and patterns",
          "Bilingual Arabic–English application"
        ],
        "icon": null,
        "image": null
      },
      {
        "id": 3,
        "title": "Logo Design",
        "description": "Original, timeless logos designed to work across every size, screen and language.",
        "features": [
          "Multiple initial concepts",
          "Arabic and English lockups",
          "Full file package for print and digital"
        ],
        "icon": null,
        "image": null
      },
      {
        "id": 4,
        "title": "Brand Guidelines",
        "description": "A clear rulebook that keeps your brand consistent across teams, agencies and channels.",
        "features": [
          "Logo usage and clear-space rules",
          "Colour, type and imagery standards",
          "Templates and application examples"
        ],
        "icon": null,
        "image": null
      },
      {
        "id": 5,
        "title": "Graphic Design",
        "description": "Polished design for every touchpoint, from presentations and reports to social media and signage.",
        "features": [
          "Presentations and company profiles",
          "Annual reports and infographics",
          "Social media and digital assets"
        ],
        "icon": null,
        "image": null
      },
      {
        "id": 6,
        "title": "Marketing & Advertising Materials",
        "description": "Print and digital collateral that turns attention into enquiries.",
        "features": [
          "Brochures, flyers and catalogues",
          "Outdoor and print advertising",
          "Exhibition and event materials"
        ],
        "icon": null,
        "image": null
      },
      {
        "id": 7,
        "title": "Packaging Design",
        "description": "Packaging that stands out on the shelf and tells your product’s story at a glance.",
        "features": [
          "Structural and graphic packaging design",
          "Label and product range design",
          "Print-ready artwork and supplier liaison"
        ],
        "icon": null,
        "image": null
      }
    ],
    "faqs": [
      {
        "id": 7,
        "question": "How long does a brand identity project take?",
        "answer": "A full identity typically takes six to eight weeks, including strategy, concepts, refinements and final guidelines. A logo-only project usually takes two to three weeks."
      },
      {
        "id": 8,
        "question": "Can you refresh our existing brand rather than start from scratch?",
        "answer": "Yes. We often evolve existing brands — modernising the logo, tightening the visual system and updating guidelines — while protecting the recognition you have already built."
      }
    ],
    "updated_at": "2026-11-13T16:15:25+04:00"
  },
  "meta": {
    "locale": "en",
    "generated_at": "2026-11-13T16:16:07+04:00"
  }
}
```

### GET /sitemap

Every public URL, in both locales, with the date of its last content change. Locale is not needed.
Path scheme: `/{locale}`, `/{locale}/about`, `/{locale}/services`, `/{locale}/contact`,
`/{locale}/services/{slug}`. Prefix with the site origin in `app/sitemap.ts`.

`GET /api/v1/sitemap`

```json
// 200
{
  "data": [
    {
      "path": "/en",
      "locale": "en",
      "updated_at": "2026-11-13T16:15:21+04:00"
    },
    {
      "path": "/en/about",
      "locale": "en",
      "updated_at": "2026-11-13T16:16:04+04:00"
    },
    {
      "path": "/en/services",
      "locale": "en",
      "updated_at": "2026-11-13T16:16:05+04:00"
    },
    {
      "path": "/en/contact",
      "locale": "en",
      "updated_at": "2026-11-13T16:16:05+04:00"
    },
    {
      "path": "/en/services/brand-identity-design",
      "locale": "en",
      "updated_at": "2026-11-13T16:15:29+04:00"
    },
    {
      "path": "/en/services/media-content-production",
      "locale": "en",
      "updated_at": "2026-11-13T16:15:34+04:00"
    },
    {
      "path": "/en/services/digital-marketing",
      "locale": "en",
      "updated_at": "2026-11-13T16:15:39+04:00"
    },
    {
      "path": "/en/services/web-design-development",
      "locale": "en",
      "updated_at": "2026-11-13T16:15:43+04:00"
    },
    {
      "path": "/en/services/creative-ai-solutions",
      "locale": "en",
      "updated_at": "2026-11-13T16:15:46+04:00"
    },
    {
      "path": "/en/services/training-workshops",
      "locale": "en",
      "updated_at": "2026-11-13T16:15:49+04:00"
    },
    {
      "path": "/ar",
      "locale": "ar",
      "updated_at": "2026-11-13T16:15:21+04:00"
    },
    {
      "path": "/ar/about",
      "locale": "ar",
      "updated_at": "2026-11-13T16:16:04+04:00"
    },
    {
      "path": "/ar/services",
      "locale": "ar",
      "updated_at": "2026-11-13T16:16:05+04:00"
    },
    {
      "path": "/ar/contact",
      "locale": "ar",
      "updated_at": "2026-11-13T16:16:05+04:00"
    },
    {
      "path": "/ar/services/brand-identity-design",
      "locale": "ar",
      "updated_at": "2026-11-13T16:15:29+04:00"
    },
    {
      "path": "/ar/services/media-content-production",
      "locale": "ar",
      "updated_at": "2026-11-13T16:15:34+04:00"
    },
    {
      "path": "/ar/services/digital-marketing",
      "locale": "ar",
      "updated_at": "2026-11-13T16:15:39+04:00"
    },
    {
      "path": "/ar/services/web-design-development",
      "locale": "ar",
      "updated_at": "2026-11-13T16:15:43+04:00"
    },
    {
      "path": "/ar/services/creative-ai-solutions",
      "locale": "ar",
      "updated_at": "2026-11-13T16:15:46+04:00"
    },
    {
      "path": "/ar/services/training-workshops",
      "locale": "ar",
      "updated_at": "2026-11-13T16:15:49+04:00"
    }
  ],
  "meta": {
    "locale": "en",
    "generated_at": "2026-11-13T16:16:07+04:00"
  }
}
```

### POST /contact

Submits the contact form. Call it **from the Next.js server** (a Server Action or route handler), not from
the browser. Browser calls are also blocked by CORS unless they come from `FRONTEND_URL`.

- **Headers (send both):**

  | Header | Value |
  |---|---|
  | `X-Site-Key` | `{API_SITE_KEY}` |
  | `X-Client-IP` | the **visitor's** IP: the first entry of `x-forwarded-for`, else `x-real-ip` |

- **Rate limit: 5 requests/minute per visitor.** Its counter is separate from the GET limit.
  - Because the Next server makes every call, the API counts per `X-Client-IP`, but **only when
    `X-Site-Key` is valid and the header holds a valid IPv4/IPv6 address**. The same IP is stored on the lead.
  - Otherwise (no key, wrong key, missing or invalid IP) the connecting IP is used. Without the
    headers, all visitors would share one limit of 5/min.
  - A wrong site key does **not** reject the submission; it only disables the per-visitor limit.
- Body: JSON `ContactPayload`.

| Field | Rules |
|---|---|
| `name` | required, max 120 |
| `email` | required, valid RFC email, max 190 |
| `phone` | optional, max 30, digits, spaces and `+ ( ) -` only |
| `company` | optional, max 120 |
| `service` | optional, slug of a **visible** service category |
| `message` | required, 10–5000 characters |
| `locale` | `en` or `ar`. It is stored with the lead **and selects the language of validation messages** |
| `website` | **honeypot**: render it as a visually hidden input (`tabIndex={-1}`, `autoComplete="off"`) and send `""`. If it is filled, the API answers 201 but stores nothing. |

On success the lead is stored, the team gets an email, and admin and sales users get an admin-panel
notification. The response holds the localized success text (`ui_strings['form.success']`):

`POST /api/v1/contact` with `{"name":"Layla Ahmed","email":"layla@example.com","message":"We need a new brand identity.","locale":"ar"}`

```json
// 201
{
  "data": {
    "message": "شكراً لتواصلك معنا. تم إرسال رسالتك وسنتواصل معك قريباً."
  },
  "meta": {
    "locale": "ar",
    "generated_at": "2026-11-13T16:16:09+04:00"
  }
}
```

Validation errors come back as **422**, in the payload's `locale`. Show `errors[field][0]` under each field:

`POST /api/v1/contact` with `{"name":"","email":"nope","message":"short","locale":"ar"}`

```json
// 422
{
  "message": "حقل الاسم مطلوب. (وخطآن آخران)",
  "errors": {
    "name": [
      "حقل الاسم مطلوب."
    ],
    "email": [
      "يُرجى إدخال بريد إلكتروني صحيح."
    ],
    "message": [
      "يُرجى كتابة المزيد من التفاصيل عن مشروعك، بما لا يقل عن 10 أحرف."
    ]
  }
}
```

---

## Errors

All errors are JSON: `{ "message": string }`. Validation errors also include `errors`.

| Status | When | Body |
|---|---|---|
| 401 | GET with a missing (non-local) or wrong `X-Site-Key` | `{"message": "Invalid site key."}` |
| 404 | Unknown page key, hidden or unknown category slug, unknown route | `{"message": "Not found."}` |
| 422 | Contact validation failed | `{"message": "…", "errors": {"field": ["…"]}}` (see above) |
| 429 | Rate limit exceeded | `{"message": "Too Many Attempts."}`, with `Retry-After` (seconds) and `X-RateLimit-*` headers |
| 5xx | Server error | `{"message": "Server Error."}` |

```json
// 404
{
  "message": "Not found."
}
```

```json
// 401
{
  "message": "Invalid site key."
}
```

```json
// 429
{ "message": "Too Many Attempts." }
```

**Recommended frontend handling:** 404 → `notFound()`. 422 → field errors. 429 → show
`ui_strings['form.error']` and ask the visitor to retry shortly. Anything else → `form.error` (forms)
or the error boundary (pages).

---

## Caching and revalidation

**On the API.** Each GET response is cached for 1 hour per endpoint + params + locale, and is
registered under its cache tag. Any content change clears the affected tags immediately.

**On the frontend.** Fetch with tags and a long or unlimited revalidate time. The API tells Next.js
exactly when to drop them:

```http
POST {FRONTEND_REVALIDATE_URL}
X-Revalidate-Secret: {REVALIDATE_SECRET}
Content-Type: application/json

{"tags": ["home", "categories", "category:digital-marketing"]}
```

The route handler must compare the secret in constant time, call `revalidateTag(tag)` for each tag,
and answer 2xx. The API sends **one** request per admin action (tags are deduplicated), from a queued job
(timeout 10 s, 3 tries, backoff 5 s / 30 s / 120 s). A final failure is only logged.

| Admin change | Tags sent |
|---|---|
| Settings, interface strings | `site`, `home` |
| Home section | `home`, `sitemap` |
| Stat, process step, client | `home` |
| FAQ without category / with category | `home` / `category:{slug}` (old and new when moved) |
| Service category | `site`, `home`, `categories`, `sitemap`, `category:{slug}` (old and new slug) |
| Service | `home`, `categories`, `sitemap`, `category:{slug}` (old and new parent) |
| Page, page block | `page:{key}`, `sitemap` |
| Media upload / edit / delete | the tags of the model that owns the file |
| Drag-and-drop reorder of any table | the tags of the reordered rows |
| Dashboard → "Refresh website cache" | every tag |

**Locally.** The webhook is sent by the queue worker. Run `php artisan queue:work` in `pixellap-api`.
If the Next.js app is not running, the job fails quietly; that is expected.
