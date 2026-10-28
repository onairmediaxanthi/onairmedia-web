<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BusinessDataRequest
{
    public function rules(): array
    {
        return [
            ...$this->businessRules(),
            'email' => ['required', 'email:rfc,strict', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()],
            'terms' => ['accepted'],
            'marketing' => ['nullable', 'boolean'],
            'website_url' => ['prohibited'], // honeypot: μόνο τα bots το συμπληρώνουν
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'email.unique' => 'Αυτό το email έχει ήδη λογαριασμό. Συνδεθείτε ή ζητήστε νέο κωδικό.',
            'terms.accepted' => 'Για την εγγραφή χρειάζεται να αποδεχτείτε τους όρους.',
            'password.uncompromised' => 'Αυτός ο κωδικός έχει εμφανιστεί σε διαρροές δεδομένων. Διαλέξτε άλλον.',
        ];
    }
}
