<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalendarUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['sometimes', 'boolean'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i', 'after:start_time'],
            'slot_duration' => ['sometimes', 'required', 'integer', 'min:5', 'max:120'],
            'time_format' => ['sometimes', 'required', 'string', 'in:12,24'],
            'timezone' => ['sometimes', 'required', 'string', 'timezone'],
            'business_days' => ['sometimes', 'required', 'array'],
            'business_days.*' => ['integer', 'in:1,2,3,4,5,6,7'],
            'visibility' => ['sometimes', 'required', 'string', 'in:todos,solo_yo'],
            'include_in_analytics' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
