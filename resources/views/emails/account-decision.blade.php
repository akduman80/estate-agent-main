<h1>İşletme üyeliği başvuru sonucu</h1>
<p>Merhaba {{ $user->name }},</p>
<p>ZYDEMLAK emlak işletmesi üyeliği başvurunuz <strong>{{ $decision }}</strong>.</p>
@if($note)<div>Yönetici notu: {!! \App\Support\RichContentHtml::render($note) !!}</div>@endif
@if($decision === 'onaylandı')<p>Artık hesabınıza şifre ve e-posta doğrulama koduyla giriş yapabilirsiniz.</p>@endif
