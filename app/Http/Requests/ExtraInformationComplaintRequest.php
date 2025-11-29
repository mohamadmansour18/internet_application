<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtraInformationComplaintRequest extends FormRequest
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
            'extra_text' => ['nullable' , 'string' , 'required_without:extra_attachment'],
            'extra_attachment' => ['nullable' , 'file' , 'image' , 'mimes:jpeg,jpg,png' , 'max:2048' , 'required_without:extra_text'],
        ];
    }
}
