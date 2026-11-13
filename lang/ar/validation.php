<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Validation (Arabic — Modern Standard Arabic)
|--------------------------------------------------------------------------
| Laravel ships no Arabic messages, so the rules used by the public API are
| defined here, plus the contact form's attribute names.
*/

return [

    'accepted' => 'يجب قبول :attribute.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'boolean' => 'يجب أن تكون قيمة :attribute إما صحيحة أو خاطئة.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صحيحاً.',
    'exists' => 'القيمة المحددة في حقل :attribute غير صالحة.',
    'filled' => 'يجب ألّا يكون حقل :attribute فارغاً.',
    'in' => 'القيمة المحددة في حقل :attribute غير صالحة.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'max' => [
        'array' => 'يجب ألّا يحتوي :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألّا يتجاوز حجم :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألّا تتجاوز قيمة :attribute :max.',
        'string' => 'يجب ألّا يتجاوز طول :attribute :max حرفاً.',
    ],
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
        'file' => 'يجب ألّا يقل حجم :attribute عن :min كيلوبايت.',
        'numeric' => 'يجب ألّا تقل قيمة :attribute عن :min.',
        'string' => 'يجب ألّا يقل طول :attribute عن :min أحرف.',
    ],
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'prohibited' => 'حقل :attribute غير مسموح به.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'url' => 'صيغة الرابط في حقل :attribute غير صحيحة.',

    'custom' => [
        'email' => [
            'email' => 'يُرجى إدخال بريد إلكتروني صحيح.',
        ],
        'phone' => [
            'regex' => 'يجب أن يقتصر رقم الهاتف على الأرقام والمسافات والرموز + ( ) -.',
        ],
        'service' => [
            'exists' => 'يُرجى اختيار خدمة من القائمة.',
        ],
        'message' => [
            'min' => 'يُرجى كتابة المزيد من التفاصيل عن مشروعك، بما لا يقل عن :min أحرف.',
        ],
    ],

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'company' => 'اسم الشركة',
        'service' => 'الخدمة',
        'message' => 'الرسالة',
        'locale' => 'اللغة',
    ],

];
