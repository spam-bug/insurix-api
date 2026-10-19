<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** A profile photo or logo upload. */
class UploadImageRequest extends FormRequest
{
    /**
     * Agents only ever change their own images.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=4000,max_height=4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.max' => 'Pick an image under 2 MB.',
            'image.mimes' => 'Use a JPG, PNG or WebP image.',
            'image.dimensions' => 'Use an image between 64 and 4,000 pixels on each side.',
        ];
    }
}
