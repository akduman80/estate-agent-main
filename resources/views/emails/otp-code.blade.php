<h1>ZYDEMLAK doğrulama kodu</h1>
<p>Merhaba {{ $user->first_name ?: $user->name }},</p>
<p>
@if($purpose === \App\Enums\OtpPurpose::PASSWORD_RESET)
    Şifrenizi yenilemek için tek kullanımlık kodunuz:
@else
    Tek kullanımlık doğrulama kodunuz:
@endif
</p>
<p style="font-size: 30px; font-weight: bold; letter-spacing: 8px;">{{ $code }}</p>
<p>Bu kod {{ config('security.otp.ttl_minutes') }} dakika geçerlidir ve yalnızca bir kez kullanılabilir.</p>
<p>Bu işlemi siz başlatmadıysanız kodu kimseyle paylaşmayın.</p>
