<h1>İşletme üyeliği başvuru sonucu</h1>
<p>Merhaba {{ $user->name }},</p>
<p>ZYDEMLAK emlak işletmesi üyeliği başvurunuz <strong>{{ $decision }}</strong>.</p>
@if($note)<p>Yönetici notu: {{ $note }}</p>@endif
@if($decision === 'onaylandı')<p>Artık hesabınıza şifre ve e-posta doğrulama koduyla giriş yapabilirsiniz.</p>@endif
