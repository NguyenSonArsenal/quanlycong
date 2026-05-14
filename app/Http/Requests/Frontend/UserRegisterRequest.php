<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class UserRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => 'bail|required|string|max:50|unique:user,username,' . request('username') . ',id',
            'phone'    => 'bail|required|string|max:20|unique:user,phone,' . request('phone') . ',id',
            'email'    => 'bail|required|email|max:255|unique:user,email,' . request('email') . ',id',
            'password' => 'bail|required|string|min:6|confirmed',
        ];

    }
}
