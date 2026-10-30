<?php

namespace App\Http\Requests;

use App\Models\Business;
use App\Rules\GreekVat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Κοινοί κανόνες για τα στοιχεία επιχείρησης (εγγραφή και επεξεργασία) */
class BusinessDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Καθαρισμός πριν τον έλεγχο: κενά, παύλες, +30 */
    protected function prepareForValidation(): void
    {
        $digits = fn ($v) => preg_replace('/\D/', '', (string) $v);
        $mobile = $digits($this->input('mobile'));
        if (str_starts_with($mobile, '0030')) {
            $mobile = substr($mobile, 4);
        } elseif (str_starts_with($mobile, '30') && strlen($mobile) === 12) {
            $mobile = substr($mobile, 2);
        }

        $this->merge([
            'vat' => $digits($this->input('vat')),
            'gemi' => $this->filled('gemi') ? $digits($this->input('gemi')) : null,
            'postal_code' => $digits($this->input('postal_code')),
            'mobile' => $mobile,
            'phone' => $this->filled('phone') ? $digits($this->input('phone')) : null,
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }

    /** @return array<string, mixed> */
    protected function businessRules(?int $ignoreBusinessId = null): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:150'],
            'trade_name' => ['nullable', 'string', 'max:150'],
            'business_type' => ['required', Rule::in(array_keys(Business::TYPES))],
            'business_type_other' => ['nullable', 'required_if:business_type,other', 'string', 'max:100'],
            'vat' => ['required', new GreekVat, Rule::unique('businesses', 'vat')->ignore($ignoreBusinessId)],
            'tax_office' => ['nullable', 'string', 'max:80'],
            'gemi' => ['nullable', 'digits_between:9,12'],
            'address' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:80'],
            'postal_code' => ['required', 'digits:5'],
            'mobile' => ['required', 'regex:/^69\d{8}$/'],
            'phone' => ['nullable', 'digits:10'],
            'contact_person' => ['required', 'string', 'max:100'],
            'website' => ['nullable', 'url:https,http', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'vat.unique' => 'Υπάρχει ήδη λογαριασμός με αυτόν τον ΑΦΜ. Αν είναι δικός σας, συνδεθείτε ή επικοινωνήστε μαζί μας.',
            'mobile.regex' => 'Γράψτε κινητό 10 ψηφίων που ξεκινά από 69.',
            'postal_code.digits' => 'Ο ταχυδρομικός κώδικας έχει 5 ψηφία.',
            'gemi.digits_between' => 'Ο αριθμός ΓΕΜΗ έχει 9 έως 12 ψηφία.',
            'phone.digits' => 'Το σταθερό έχει 10 ψηφία.',
            'business_type_other.required_if' => 'Γράψτε το είδος της επιχείρησής σας.',
        ];
    }

    public function attributes(): array
    {
        return [
            'legal_name' => 'επωνυμία', 'trade_name' => 'διακριτικός τίτλος', 'business_type' => 'είδος επιχείρησης',
            'vat' => 'ΑΦΜ', 'tax_office' => 'ΔΟΥ', 'gemi' => 'αριθμός ΓΕΜΗ', 'address' => 'διεύθυνση', 'city' => 'πόλη',
            'postal_code' => 'Τ.Κ.', 'mobile' => 'κινητό', 'phone' => 'σταθερό', 'contact_person' => 'υπεύθυνος επικοινωνίας',
            'website' => 'ιστοσελίδα', 'email' => 'email', 'password' => 'κωδικός', 'terms' => 'όροι χρήσης',
        ];
    }

    public function rules(): array
    {
        return $this->businessRules();
    }
}
