<h1>Onayınızı bekleyen {{ $approvalType }}</h1>
<p>{{ $user->name }} ({{ $user->email }}) tarafından bir {{ $approvalType }} onaya gönderildi.</p>
<p><a href="{{ url('/admin') }}">Yönetici panelini aç</a></p>
