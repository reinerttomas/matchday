<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TeamPageAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An event the public team page sends in the background; nobody sees its validation errors.
 */
final class StoreTeamPageEventRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(TeamPageAction::class)],
            'in_app_browser' => ['nullable', 'string', 'max:32'],
        ];
    }
}
