<p>Olá, {{ $user->name }}.</p>
<p>A tarefa <strong>{{ $tarefa->titulo }}</strong> vence hoje ({{ $dia->format('d/m/Y') }}).</p>
<p><a href="{{ $url }}">Abrir no Gestor Job</a></p>
<p>Se você não esperava este aviso, fale com o admin da agência.</p>
