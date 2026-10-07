<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Edits a venue's address; its name and federation ID come from the federation, so they are not editable.
 */
final class UpdateVenueRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get the custom messages for the validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address.required' => __('venues.validation.address_required'),
            'address.string' => __('venues.validation.address_required'),
            'address.max' => __('venues.validation.address_too_long'),
        ];
    }
}
