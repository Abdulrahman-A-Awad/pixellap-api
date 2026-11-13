<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/contact
 *
 * The body `locale` (en|ar) also selects the language of validation messages.
 * Honeypot: a filled `website` field skips validation entirely; the controller
 * answers 201 without storing anything, so bots learn nothing.
 */
class StoreContactRequest extends FormRequest
{
    public const HONEYPOT = 'website';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $locale = $this->input('locale');

        if (is_string($locale) && in_array($locale, (array) config('app.available_locales'), true)) {
            app()->setLocale($locale);
        }
    }

    public function isSpam(): bool
    {
        return filled($this->input(self::HONEYPOT));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        if ($this->isSpam()) {
            return [];
        }

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\s+()\-]+$/'],
            'company' => ['nullable', 'string', 'max:120'],
            'service' => [
                'nullable', 'string',
                Rule::exists('service_categories', 'slug')->where('is_visible', true),
            ],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'locale' => ['nullable', 'string', Rule::in((array) config('app.available_locales'))],
            self::HONEYPOT => ['nullable'],
        ];
    }
}
