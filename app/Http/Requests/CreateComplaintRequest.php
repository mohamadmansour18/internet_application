<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateComplaintRequest extends FormRequest
{
    protected $stopOnFirstFailure = true ;
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
            'agency_id' => ['required', 'integer', 'exists:agencies,id'],
            'complaint_type_id' => ['required', 'integer', 'exists:complaint_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location_text' => ['required', 'string', 'max:255'],
            'attachments' => ['nullable', 'array' , 'max:3'],
            'attachments.*' => ['file', 'image' , 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }
}
