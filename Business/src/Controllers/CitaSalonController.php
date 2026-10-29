<?php
declare(strict_types=1);
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\Services\DataService;
use App\Validators\CitaSalonValidator;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class CitaSalonController {

    public function __construct(private ContainerInterface $container) {}

    public function read(Request $request, Response $response, array $args): Response {
        try {
            $dataService = $this->container->get(DataService::class);
            $path = isset($args['id']) ? 'citas_salon/' . rawurlencode($args['id']) : 'citas_salon';
            $upstream = $dataService->get($path);
            $response->getBody()->write((string) $upstream->getBody());
            return $response
                ->withHeader('Content-Type', $upstream->getHeaderLine('Content-Type') ?: 'application/json; charset=UTF-8')
                ->withStatus($upstream->getStatusCode());
        } catch (ConnectException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        } catch (RequestException) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function create(Request $request, Response $response, array $args): Response {
        try {
            $request->getBody()->rewind();
            $body = (string) $request->getBody();

            $data = json_decode($body, true);

            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                return $this->json($response, ['errores' => ['body' => 'Debe enviar un objeto JSON válido']], 400);
            }

            $validator = new CitaSalonValidator();
            $errores = $validator->validate($data);

            if ($errores === []) {
                $errores = $validator->validarNueva($data);
            }

            if ($errores !== []) {
                return $this->json($response, ['errores' => $errores], 422);
            }

            $dataService = $this->container->get(DataService::class);
            $upstream = $dataService->post('citas_salon', json_encode($data, JSON_THROW_ON_ERROR));

            return $this->json($response, $this->cuerpo($upstream), $upstream->getStatusCode());
        } catch (ConnectException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        } catch (RequestException) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function update(Request $request, Response $response, array $args): Response {
        try {
            $request->getBody()->rewind();
            $body = (string) $request->getBody();

            $data = json_decode($body, true);

            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                return $this->json($response, ['errores' => ['body' => 'Debe enviar un objeto JSON válido']], 400);
            }

            $validator = new CitaSalonValidator();
            $errores = $validator->validate($data, true);

            if ($errores === []) {
                $errores = $validator->validarFechaFutura($data);
            }

            if ($errores !== []) {
                return $this->json($response, ['errores' => $errores], 422);
            }

            $dataService = $this->container->get(DataService::class);

            $actual = $dataService->get('citas_salon/' . rawurlencode($args['id']));

            if ($actual->getStatusCode() !== 200) {
                return $this->json($response, $this->cuerpo($actual), $actual->getStatusCode());
            }

            $conflicto = $validator->validarCambioEstado($this->cuerpo($actual)['data'] ?? [], $data);

            if ($conflicto !== null) {
                return $this->json($response, ['error' => $conflicto], 409);
            }

            $upstream = $dataService->put('citas_salon/' . rawurlencode($args['id']), json_encode($data, JSON_THROW_ON_ERROR));

            return $this->json($response, $this->cuerpo($upstream), $upstream->getStatusCode());
        } catch (ConnectException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        } catch (RequestException) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        try {
            $dataService = $this->container->get(DataService::class);
            $upstream = $dataService->delete('citas_salon/' . rawurlencode($args['id']));

            if ($upstream->getStatusCode() === 204) {
                return $response->withStatus(204);
            }

            return $this->json($response, $this->cuerpo($upstream), $upstream->getStatusCode());
        } catch (ConnectException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        } catch (RequestException) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    private function cuerpo(Response $upstream): array {
        $data = json_decode((string) $upstream->getBody(), true);

        return is_array($data) ? $data : ['error' => 'Respuesta inválida del servicio de datos'];
    }

    private function json(Response $response, array $data, int $status): Response {
        $response->getBody()->write(json_encode($data,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
}
