<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\OtpPurpose;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\LegalDocument;
use App\Models\User;
use App\Rules\TurkishIdentityNumber;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function create(Request $request)
    {
        $type = $request->query('type', 'individual');
        abort_unless(in_array($type, ['individual', 'business'], true), 404);

        return view('auth.register', [
            'type' => $type,
            'documents' => LegalDocument::query()->where('is_active', true)->latest('published_at')->get()->unique('slug'),
        ]);
    }

    public function store(Request $request, EmailOtpService $otpService)
    {
        $type = $request->string('account_type')->toString();

        $rules = [
            'account_type' => ['required', Rule::in(['individual', 'business'])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:24'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
            'consents' => ['array'],
        ];

        if ($type === 'individual') {
            $rules['identity_number'] = ['required', new TurkishIdentityNumber];
        } else {
            $rules += [
                'office_name' => ['required', 'string', 'max:255'],
                'license_number' => ['required', 'string', 'max:100', 'unique:business_profiles,license_number'],
                'tax_office' => ['required', 'string', 'max:150'],
                'tax_number' => ['required', 'digits:10'],
                'business_phone' => ['nullable', 'string', 'max:24'],
                'city' => ['required', 'string', 'max:100'],
                'district' => ['required', 'string', 'max:100'],
                'address' => ['required', 'string', 'max:1000'],
            ];
        }

        $data = $request->validate($rules);

        if ($type === 'individual') {
            $identityHash = hash('sha256', preg_replace('/\D/', '', $data['identity_number']));
            if (User::query()->where('identity_number_hash', $identityHash)->exists()) {
                throw ValidationException::withMessages(['identity_number' => 'Bu kimlik numarasıyla daha önce hesap oluşturulmuş.']);
            }
        } else {
            $taxHash = hash('sha256', preg_replace('/\D/', '', $data['tax_number']));
            if (BusinessProfile::query()->where('tax_number_hash', $taxHash)->exists()) {
                throw ValidationException::withMessages(['tax_number' => 'Bu vergi numarasıyla daha önce işletme hesabı oluşturulmuş.']);
            }
        }

        $requiredDocuments = LegalDocument::query()->where('is_active', true)->where('is_required', true)->pluck('id');
        $acceptedDocuments = collect($data['consents'] ?? [])->map(fn ($id) => (int) $id);

        if ($requiredDocuments->diff($acceptedDocuments)->isNotEmpty()) {
            return back()->withErrors(['consents' => 'Zorunlu sözleşme ve aydınlatma metinlerini kabul etmelisiniz.'])->withInput();
        }

        $user = DB::transaction(function () use ($data, $type, $request, $acceptedDocuments): User {
            $identity = $type === 'individual' ? preg_replace('/\D/', '', $data['identity_number']) : null;

            $user = User::create([
                'name' => $data['first_name'].' '.$data['last_name'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'],
                'identity_number' => $identity,
                'identity_number_hash' => $identity ? hash('sha256', $identity) : null,
                'password' => Hash::make($data['password']),
                'role' => $type === 'individual' ? UserRole::INDIVIDUAL : UserRole::AGENT,
                'account_status' => AccountStatus::PENDING_EMAIL,
            ]);

            if ($type === 'business') {
                $taxNumber = preg_replace('/\D/', '', $data['tax_number']);
                $user->businessProfile()->create([
                    'office_name' => $data['office_name'],
                    'license_number' => $data['license_number'],
                    'tax_office' => $data['tax_office'],
                    'tax_number' => $taxNumber,
                    'tax_number_hash' => hash('sha256', $taxNumber),
                    'business_phone' => $data['business_phone'] ?? null,
                    'city' => $data['city'],
                    'district' => $data['district'],
                    'address' => $data['address'],
                ]);
            }

            foreach ($acceptedDocuments as $documentId) {
                $user->consents()->create([
                    'legal_document_id' => $documentId,
                    'accepted_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            return $user;
        });

        $otpService->issue($user, OtpPurpose::EMAIL_VERIFICATION, $request->ip());
        $request->session()->put('verification_user_id', $user->id);

        return redirect()->route('verification.notice')->with('status', 'Doğrulama kodu e-posta adresinize gönderildi.');
    }
}
