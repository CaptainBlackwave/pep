<?php

namespace App\Core\Http\Requests;

use App\Core\Models\CommunityAccessCode;
use App\Core\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint; private access is enforced by the one-use code.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $accessCodeRequired = Setting::where('key', 'access_code_required')->value('value') === '1';

        $rules = [
            'username' => 'required|string|max:20|unique:users',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];

        if ($accessCodeRequired) {
            $rules['access_code'] = [
                'required',
                'string',
                'max:80',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!CommunityAccessCode::isUsableCode((string) $value)) {
                        $fail('The access code is invalid or has already been used.');
                    }
                },
            ];
        } else {
            $rules['access_code'] = 'nullable|string|max:80';
        }

        return $rules;
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken.',
            'email.unique'    => 'An account with this email already exists.',
            'password.min'    => 'Password must be at least 8 characters.',
            'access_code.required' => 'An access code is required to join this private community.',
        ];
    }
}
