<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\CitaSalonModel;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class CitaSalonController {
    private const ESTADOS = ['Pendiente', 'Completada', 'Cancelada'];

    private ContainerInterface $container;

    public function __construct(ContainerInterface $c) {
        $this->container = $c;
    }

    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');

        if (isset($args['id'])) {
            $id = filter_var($args['id'], FILTER_VALIDATE_INT);

            if ($id === false || $id < 1) {
                return $this->json($response, ['error' => 'El identificador debe ser un entero positivo.'], 422);
            }

            $cita = CitaSalonModel::query()
                ->select([
                    'citas_salon.id',
                    'citas_salon.nombre_cliente',
                    'citas_salon.telefono_cliente',
                    'citas_salon.servicio_solicitado',
                    'citas_salon.estilista_asignado',
                    'citas_salon.fecha_hora_cita',
                    'citas_salon.precio_servicio',
                    'citas_salon.estado',
                    'citas_salon.created_at'
                ])
                ->where('citas_salon.id', $id)
                ->first();

            if ($cita === null) {
                return $this->json($response, ['error' => 'Cita no encontrada.'], 404);
            }

            return $this->json($response, ['data' => $cita->toArray()], 200);
        }

        $citas = CitaSalonModel::query()
            ->select([
                'citas_salon.id',
                'citas_salon.nombre_cliente',
                'citas_salon.telefono_cliente',
                'citas_salon.servicio_solicitado',
                'citas_salon.estilista_asignado',
                'citas_salon.fecha_hora_cita',
                'citas_salon.precio_servicio',
                'citas_salon.estado',
                'citas_salon.created_at'
            ])
            ->get()
            ->toArray();

        return $this->json($response, ['data' => $citas], 200);
    }

    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $errors = [];
        $nombreCliente = trim((string) ($data['nombre_cliente'] ?? ''));
        $telefonoCliente = trim((string) ($data['telefono_cliente'] ?? ''));
        $servicioSolicitado = trim((string) ($data['servicio_solicitado'] ?? ''));
        $estilistaAsignado = trim((string) ($data['estilista_asignado'] ?? ''));
        $fechaHoraCita = trim((string) ($data['fecha_hora_cita'] ?? ''));
        $precioServicio = $data['precio_servicio'] ?? null;
        $estado = $data['estado'] ?? 'Pendiente';

        if ($nombreCliente === '' || mb_strlen($nombreCliente) > 100) {
            $errors['nombre_cliente'] = 'Es obligatorio y debe tener máximo 100 caracteres.';
        }

        if ($telefonoCliente === '' || mb_strlen($telefonoCliente) > 20) {
            $errors['telefono_cliente'] = 'Es obligatorio y debe tener máximo 20 caracteres.';
        }

        if ($servicioSolicitado === '' || mb_strlen($servicioSolicitado) > 80) {
            $errors['servicio_solicitado'] = 'Es obligatorio y debe tener máximo 80 caracteres.';
        }

        if ($estilistaAsignado === '' || mb_strlen($estilistaAsignado) > 100) {
            $errors['estilista_asignado'] = 'Es obligatorio y debe tener máximo 100 caracteres.';
        }

        if (!$this->esFechaHoraValida($fechaHoraCita)) {
            $errors['fecha_hora_cita'] = 'Es obligatoria y debe tener el formato AAAA-MM-DD HH:MM:SS.';
        }

        if (!is_numeric($precioServicio) || (float) $precioServicio < 0 || (float) $precioServicio > 99999999.99) {
            $errors['precio_servicio'] = 'Es obligatorio y debe ser un número entre 0 y 99999999.99.';
        }

        if (!in_array($estado, self::ESTADOS, true)) {
            $errors['estado'] = 'Debe ser uno de estos valores: ' . implode(', ', self::ESTADOS) . '.';
        }

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $this->container->get('eloquent');
        $cita = CitaSalonModel::create([
            'nombre_cliente' => $nombreCliente,
            'telefono_cliente' => $telefonoCliente,
            'servicio_solicitado' => $servicioSolicitado,
            'estilista_asignado' => $estilistaAsignado,
            'fecha_hora_cita' => $fechaHoraCita,
            'precio_servicio' => round((float) $precioServicio, 2),
            'estado' => $estado,
        ]);

        return $this->json($response, ['data' => $cita->fresh()->toArray()], 201);
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = filter_var($args['id'], FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            return $this->json($response, ['error' => 'El identificador debe ser un entero positivo.'], 422);
        }

        $this->container->get('eloquent');
        $cita = CitaSalonModel::find($id);

        if ($cita === null) {
            return $this->json($response, ['error' => 'Cita no encontrada.'], 404);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $errors = [];

        if (array_key_exists('nombre_cliente', $data)) {
            $nombreCliente = trim((string) ($data['nombre_cliente'] ?? ''));

            if ($nombreCliente === '' || mb_strlen($nombreCliente) > 100) {
                $errors['nombre_cliente'] = 'Es obligatorio y debe tener máximo 100 caracteres.';
            } else {
                $cita->nombre_cliente = $nombreCliente;
            }
        }

        if (array_key_exists('telefono_cliente', $data)) {
            $telefonoCliente = trim((string) ($data['telefono_cliente'] ?? ''));

            if ($telefonoCliente === '' || mb_strlen($telefonoCliente) > 20) {
                $errors['telefono_cliente'] = 'Es obligatorio y debe tener máximo 20 caracteres.';
            } else {
                $cita->telefono_cliente = $telefonoCliente;
            }
        }

        if (array_key_exists('servicio_solicitado', $data)) {
            $servicioSolicitado = trim((string) ($data['servicio_solicitado'] ?? ''));

            if ($servicioSolicitado === '' || mb_strlen($servicioSolicitado) > 80) {
                $errors['servicio_solicitado'] = 'Es obligatorio y debe tener máximo 80 caracteres.';
            } else {
                $cita->servicio_solicitado = $servicioSolicitado;
            }
        }

        if (array_key_exists('estilista_asignado', $data)) {
            $estilistaAsignado = trim((string) ($data['estilista_asignado'] ?? ''));

            if ($estilistaAsignado === '' || mb_strlen($estilistaAsignado) > 100) {
                $errors['estilista_asignado'] = 'Es obligatorio y debe tener máximo 100 caracteres.';
            } else {
                $cita->estilista_asignado = $estilistaAsignado;
            }
        }

        if (array_key_exists('fecha_hora_cita', $data)) {
            $fechaHoraCita = trim((string) ($data['fecha_hora_cita'] ?? ''));

            if (!$this->esFechaHoraValida($fechaHoraCita)) {
                $errors['fecha_hora_cita'] = 'Es obligatoria y debe tener el formato AAAA-MM-DD HH:MM:SS.';
            } else {
                $cita->fecha_hora_cita = $fechaHoraCita;
            }
        }

        if (array_key_exists('precio_servicio', $data)) {
            $precioServicio = $data['precio_servicio'];

            if (!is_numeric($precioServicio) || (float) $precioServicio < 0 || (float) $precioServicio > 99999999.99) {
                $errors['precio_servicio'] = 'Es obligatorio y debe ser un número entre 0 y 99999999.99.';
            } else {
                $cita->precio_servicio = round((float) $precioServicio, 2);
            }
        }

        if (array_key_exists('estado', $data)) {
            $estado = $data['estado'];

            if (!in_array($estado, self::ESTADOS, true)) {
                $errors['estado'] = 'Debe ser uno de estos valores: ' . implode(', ', self::ESTADOS) . '.';
            } else {
                $cita->estado = $estado;
            }
        }

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $cita->save();

        return $this->json($response, ['data' => $cita->fresh()->toArray()], 200);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = filter_var($args['id'], FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            return $this->json($response, ['error' => 'El identificador debe ser un entero positivo.'], 422);
        }

        $this->container->get('eloquent');
        $cita = CitaSalonModel::find($id);

        if ($cita === null) {
            return $this->json($response, ['error' => 'Cita no encontrada.'], 404);
        }

        $cita->delete();

        return $response->withStatus(204);
    }

    private function esFechaHoraValida(string $fechaHora): bool {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $fechaHora);

        return $date !== false && $date->format('Y-m-d H:i:s') === $fechaHora;
    }

    private function json(Response $response, array $data, int $status): Response {
        $response->getBody()->write(json_encode($data,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
}
