<?php

namespace App\Http\Requests;

use App\Models\Plant;
use App\Models\Sighting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSightingRequest extends FormRequest
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
            'plant_id' => ['required', 'exists:plants,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'observed_on' => ['required', 'date', 'before_or_equal:today'],
            'plant_part' => ['required', Rule::in(Plant::PLANT_PARTS)],
            'visibility' => ['required', Rule::in(Sighting::VISIBILITIES)],
            'location_name' => ['nullable', 'string', 'max:255'],
            'identification_id' => ['nullable', Rule::exists('identifications', 'id')->where('user_id', $this->user()->id)],
            'photo' => [
                $this->isMethod('post') ? 'required_without:identification_id' : 'nullable',
                'image', 'mimes:jpg,jpeg,png,webp', 'max:8192',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
