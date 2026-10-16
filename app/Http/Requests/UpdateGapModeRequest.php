<?php

namespace App\Http\Requests;

use App\Models\ConsumerAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGapModeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        $caNumber = $this->input('ca_number');
        if (! $caNumber) {
            return true;
        }

        $consumer = ConsumerAccount::withoutGlobalScopes()
            ->where('ca_number', $caNumber)
            ->first();

        if (! $consumer) {
            return true;
        }

        return (int) $consumer->user_id === (int) $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ca_number' => ['required', 'string'],
            'gap_recovery_mode' => ['nullable', 'in:auto,hold_exact,hold_advance,amortize,amortize_safe,manual'],
            'custom_safe_ceiling' => ['nullable', 'integer', 'min:0', 'max:5000'],
        ];
    }
}
