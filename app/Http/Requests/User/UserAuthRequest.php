<?php
namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserAuthRequest extends ApiRequest
{

    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }
}
