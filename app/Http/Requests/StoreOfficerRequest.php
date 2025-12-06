<?php

namespace App\Http\Requests;

use App\Rules\AllowedEmailDomain;
use App\Rules\ArabicOnly;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as password_rule;

class StoreOfficerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255' , new ArabicOnly()],
            'email' => ['required', 'email', 'max:255', 'unique:users,email' , new AllowedEmailDomain()],
            'password' => ['required' , password_rule::min(6)->numbers()->letters()],
            'agency_id' => ['required' , 'integer' , 'exists:agencies,id'],
        ];
    }
}
