<?php

namespace App\Support;

use App\Models\Empresa;
use Carbon\Carbon;

class Expediente
{
    public const DIAS = [
        'dom' => Carbon::SUNDAY,
        'seg' => Carbon::MONDAY,
        'ter' => Carbon::TUESDAY,
        'qua' => Carbon::WEDNESDAY,
        'qui' => Carbon::THURSDAY,
        'sex' => Carbon::FRIDAY,
        'sab' => Carbon::SATURDAY,
    ];

    /**
     * @param  list<string>  $dias
     */
    public function __construct(
        private array $dias,
        private string $horaFim,
    ) {}

    public static function da(?Empresa $empresa): self
    {
        $config = $empresa?->config() ?? ConfiguracaoTenant::mesclar(null);

        return new self(
            array_values($config['expediente_dias'] ?? ConfiguracaoTenant::PADRAO['expediente_dias']),
            (string) ($config['expediente_hora_fim'] ?? '18:00'),
        );
    }

    /**
     * @return list<string>
     */
    public function dias(): array
    {
        return $this->dias;
    }

    public function horaFim(): string
    {
        return $this->horaFim;
    }

    /**
     * @return list<int>
     */
    public function diasDaSemanaCarbon(): array
    {
        $codigos = [];
        foreach ($this->dias as $dia) {
            if (isset(self::DIAS[$dia])) {
                $codigos[] = self::DIAS[$dia];
            }
        }

        return $codigos;
    }

    public function ehDiaUtil(Carbon $dia): bool
    {
        return in_array($dia->dayOfWeek, $this->diasDaSemanaCarbon(), true);
    }

    public function aplicarHora(mixed $data): ?Carbon
    {
        if ($data === null || $data === '') {
            return null;
        }
        $carbon = $data instanceof Carbon ? $data->copy() : Carbon::parse($data);
        [$hora, $minuto] = array_pad(explode(':', $this->horaFim), 2, '0');

        return $carbon->setTime((int) $hora, (int) $minuto, 0);
    }
}
