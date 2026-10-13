<?php

namespace App\Http\Requests;

use App\Models\Sighting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSightingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('sighting')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(Sighting::REVIEW_DECISIONS)],
            'note' => ['nullable', 'required_if:decision,needs correction,rejected', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required_if' => 'Explain what the submitter should correct or why the record was rejected.',
        ];
    }
}
