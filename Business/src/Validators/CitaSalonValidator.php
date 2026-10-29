<?php
declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;
use DateTimeZone;
use Respect\Validation\ValidatorBuilder as v;

final class CitaSalonValidator {
    private const ESTADOS = ['Pendiente', 'Completada', 'Cancelada'];
    private const ESTADOS_FINALES = ['Completada', 'Cancelada'];

    private function reglas(): array {
        return [
            'nombre_cliente' => [
                'regla' => v::stringType()
                    ->notBlank()
                    ->length(v::between(3, 100))
                    ->regex('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü ]+$/u'),
                'mensaje' => 'El nombre del cliente es obligatorio y debe tener entre 3 y 100 caracteres, solo letras y espacios.',
                'obligatorio' => true,
            ],
            'telefono_cliente' => [
                'regla' => v::stringType()
                    ->notBlank()
                    ->regex('/^[2-8][0-9]{3}-?[0-9]{4}$/'),
                'mensaje' => 'El teléfono es obligatorio y debe tener el formato 8888-8888 u 88888888.',
                'obligatorio' => true,
            ],
            'servicio_solicitado' => [
                'regla' => v::stringType()
                    ->notBlank()
                    ->length(v::between(3, 80)),
                'mensaje' => 'El servicio solicitado es obligatorio y debe tener entre 3 y 80 caracteres.',
                'obligatorio' => true,
            ],
            'estilista_asignado' => [
                'regla' => v::stringType()
                    ->notBlank()
                    ->length(v::between(3, 100))
                    ->regex('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü ]+$/u'),
                'mensaje' => 'El estilista asignado es obligatorio y debe tener entre 3 y 100 caracteres, solo letras y espacios.',
                'obligatorio' => true,
            ],
            'fecha_hora_cita' => [
                'regla' => v::dateTime('Y-m-d H:i:s'),
                'mensaje' => 'La fecha y hora de la cita es obligatoria y debe tener el formato AAAA-MM-DD HH:MM:SS.',
                'obligatorio' => true,
            ],
            'precio_servicio' => [
                'regla' => v::numericVal()
                    ->positive()
                    ->lessThanOrEqual(99999999.99),
                'mensaje' => 'El precio del servicio es obligatorio y debe ser mayor a 0 y menor o igual a 99999999.99.',
                'obligatorio' => true,
            ],
            'estado' => [
                'regla' => v::in(self::ESTADOS),
                'mensaje' => 'El estado debe ser: Pendiente, Completada o Cancelada.',
                'obligatorio' => false,
            ],
        ];
    }

    public function validate(array $data, bool $parcial = false): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config) {
            if ($parcial && !array_key_exists($campo, $data)) {
                continue;
            }

            $valor = $data[$campo] ?? null;

            if ($valor === null || $valor === '') {
                if ($config['obligatorio']) {
                    $errores[$campo] = $config['mensaje'];
                }
                continue;
            }

            if ($config['regla']->validate($valor)->hasFailed()) {
                $errores[$campo] = $config['mensaje'];
            }
        }

        return $errores;
    }

    public function validarNueva(array $data): array {
        $errores = $this->validarFechaFutura($data);
        $estado = $data['estado'] ?? 'Pendiente';

        if ($estado !== 'Pendiente') {
            $errores['estado'] = 'Toda cita nueva debe registrarse en estado Pendiente.';
        }

        return $errores;
    }

    public function validarFechaFutura(array $data): array {
        if (!array_key_exists('fecha_hora_cita', $data)) {
            return [];
        }

        $zona = new DateTimeZone('America/Costa_Rica');
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $data['fecha_hora_cita'], $zona);

        if ($fecha === false || $fecha <= new DateTimeImmutable('now', $zona)) {
            return ['fecha_hora_cita' => 'La cita debe programarse en una fecha y hora futura.'];
        }

        return [];
    }

    public function validarCambioEstado(array $actual, array $data): ?string {
        $estadoActual = (string) ($actual['estado'] ?? '');

        if (in_array($estadoActual, self::ESTADOS_FINALES, true)) {
            return "La cita ya está {$estadoActual} y no se puede modificar.";
        }

        return null;
    }
}
