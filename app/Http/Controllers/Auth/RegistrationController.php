<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\OtpPurpose;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequestedMail;
use App\Models\BusinessProfile;
use App\Models\LegalDocument;
use App\Models\User;
use App\Rules\TurkishIdentityNumber;
use App\Services\EmailOtpService;
use App\Services\OtpFeatureService;
use App\Services\TurkeyAddressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function create(Request $request, OtpFeatureService $otpFeatures)
    {
        $type = $request->query('type', 'individual');
        abort_unless(in_array($type, ['individual', 'business'], true), 404);

        return view('auth.register', [
            'type' => $type,
            'documents' => LegalDocument::query()->where('is_active', true)->latest('published_at')->get()->unique('slug'),
            'emailOtpEnabled' => $otpFeatures->emailEnabled(),
            'cities' => TurkeyAddressService::getCities(),
            'taxDirectory' => app(\App\Services\TaxOfficeDirectory::class),
        ]);
    }

    public function store(Request $request, EmailOtpService $otpService, OtpFeatureService $otpFeatures)
    {
        $type = $request->string('account_type')->toString();
        $emailOtpEnabled = $otpFeatures->emailEnabled();

        $rules = [
            'account_type' => ['required', Rule::in(['individual', 'business'])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'identity_number' => ['required', new TurkishIdentityNumber],
            'phone' => ['required', 'string', 'max:24'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
            'city' => ['required', 'string', Rule::in(array_keys(TurkeyAddressService::getCities()))],
            'district' => [
                'required',
                'string',
                Rule::in(array_keys(TurkeyAddressService::getDistricts($request->string('city')->toString()))),
            ],
            'neighborhood' => [
                'required',
                'string',
                Rule::in(array_keys(TurkeyAddressService::getNeighborhoods(
                    $request->string('city')->toString(),
                    $request->string('district')->toString(),
                ))),
            ],
            'consents' => ['array'],
        ];

        if ($type !== 'individual') {
            $rules += [
                'office_name' => ['required', 'string', 'max:255'],
                'license_number' => ['required', 'string', 'max:100', 'unique:business_profiles,license_number'],
                'tax_office_city_code' => ['required', 'string', Rule::in(array_keys(app(\App\Services\TaxOfficeDirectory::class)->cities()))],
                'tax_office_code' => ['bail', 'required', 'string', function ($attribute, $value, $fail) use ($request) {
                    $city = $request->input('tax_office_city_code');
                    if (! is_string($city) || ! app(\App\Services\TaxOfficeDirectory::class)->find($city, $value)) {
                        $fail('Seçtiğiniz ile bağlı geçerli bir vergi dairesi seçin.');
                    }
                }],
                'tax_number' => ['required', 'digits:10'],
                'business_phone' => ['nullable', 'string', 'max:24'],
                'address' => ['required', 'string', 'max:1000'],
            ];
        }

        $data = $request->validate($rules, [
            'tax_office_city_code.required' => 'Vergi dairesi ilini seçin.',
            'tax_office_city_code.in' => 'Geçerli bir vergi dairesi ili seçin.',
            'tax_office_code.required' => 'Vergi dairesini seçin.',
        ]);
        if ($type === 'business') {
            $office = app(\App\Services\TaxOfficeDirectory::class)->find($data['tax_office_city_code'], $data['tax_office_code']);
            $data['tax_office'] = $office['name'];
        }

        $identityHash = hash('sha256', preg_replace('/\D/', '', $data['identity_number']));
        if (User::query()->where('identity_number_hash', $identityHash)->exists()) {
            throw ValidationException::withMessages(['identity_number' => 'Bu kimlik numarasıyla daha önce hesap oluşturulmuş.']);
        }

        if ($type !== 'individual') {
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

        $user = DB::transaction(function () use ($data, $type, $request, $acceptedDocuments, $emailOtpEnabled): User {
            $identity = preg_replace('/\D/', '', $data['identity_number']);

            $user = User::create([
                'name' => $data['first_name'].' '.$data['last_name'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'],
                'identity_number' => $identity,
                'identity_number_hash' => $identity ? hash('sha256', $identity) : null,
                'city' => $data['city'],
                'district' => $data['district'],
                'neighborhood' => $data['neighborhood'],
                'password' => Hash::make($data['password']),
                'role' => $type === 'individual' ? UserRole::INDIVIDUAL : UserRole::AGENT,
                'account_status' => $emailOtpEnabled
                    ? AccountStatus::PENDING_EMAIL
                    : ($type === 'business' ? AccountStatus::PENDING_APPROVAL : AccountStatus::ACTIVE),
            ]);

            if (! $emailOtpEnabled) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            if ($type === 'business') {
                $taxNumber = preg_replace('/\D/', '', $data['tax_number']);
                $user->businessProfile()->create([
                    'office_name' => $data['office_name'],
                    'license_number' => $data['license_number'],
                    'tax_office' => $data['tax_office'],
                    'tax_office_city_code' => $data['tax_office_city_code'],
                    'tax_office_code' => $data['tax_office_code'],
                    'tax_number' => $taxNumber,
                    'tax_number_hash' => hash('sha256', $taxNumber),
                    'business_phone' => $data['business_phone'] ?? null,
                    'city' => $data['city'],
                    'district' => $data['district'],
                    'neighborhood' => $data['neighborhood'],
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

        if ($emailOtpEnabled) {
            $otpService->issue($user, OtpPurpose::EMAIL_VERIFICATION, $request->ip());
            $request->session()->put('verification_user_id', $user->id);

            return redirect()->route('verification.notice')->with('status', 'Doğrulama kodu e-posta adresinize gönderildi.');
        }

        if ($type === 'business') {
            Mail::to(config('security.approval_email'))->send(new ApprovalRequestedMail('emlak işletmesi üyeliği', $user, $user->businessProfile?->id));
        }

        return redirect()->route('login')->with('status', $type === 'business'
            ? 'Kaydınız alındı. İşletme hesabınız supervisor onayına gönderildi.'
            : 'Üyeliğiniz oluşturuldu. Giriş yapabilirsiniz.');
    }
}
