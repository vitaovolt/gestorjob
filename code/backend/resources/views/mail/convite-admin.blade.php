<p>Olá, {{ $admin->name }}.</p>
<p>Você foi convidado como admin de <strong>{{ $empresaNome }}</strong> no Gestor Job.</p>
<p>Defina sua senha neste link (válido por 7 dias):</p>
<p><a href="{{ $url }}">{{ $url }}</a></p>
<p>Se você não esperava este e-mail, ignore.</p>
