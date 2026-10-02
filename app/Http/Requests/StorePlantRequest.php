<?php

namespace App\Http\Requests;

use App\Models\Plant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'common_name' => ['required', 'string', 'max:255'],
            'scientific_name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(Plant::CATEGORIES)],
            'habitat' => ['required', 'string', 'max:255'],
            'plant_parts' => ['required', 'string', 'max:255'],
            'physical_features' => ['required', 'string', 'max:2000'],
            'known_toxins' => ['nullable', 'string', 'max:2000'],
            'extract_types' => ['nullable', 'string', 'max:2000'],
            'source_label' => ['required', Rule::in(Plant::SOURCE_LABELS)],
            'reference' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
