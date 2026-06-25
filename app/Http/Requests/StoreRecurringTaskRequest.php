<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskFrequency;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:255'],
            'category_id'  => ['nullable'],
            'frequency'    => ['required', Rule::enum(TaskFrequency::class)],
            'days'         => ['exclude_unless:frequency,weekly', 'required', 'array', 'min:1'],
            'days.*'       => ['string', Rule::in(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])],
            'day_of_month' => ['exclude_unless:frequency,monthly', 'required', 'integer', 'between:1,31'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.required'           => 'Please select at least one day of the week.',
            'day_of_month.required'   => 'Please enter the day of the month.',
            'day_of_month.between'    => 'The day of month must be between 1 and 31.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
