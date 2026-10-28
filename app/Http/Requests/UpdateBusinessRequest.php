<?php

namespace App\Http\Requests;

use App\Models\Business;

class UpdateBusinessRequest extends BusinessDataRequest
{
    public function authorize(): bool
    {
        return $this->user()?->business instanceof Business;
    }

    public function rules(): array
    {
        return $this->businessRules($this->user()->business->id);
    }
}
