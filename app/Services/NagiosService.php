<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NagiosService
{
    protected ?string $baseUrl = null;

    protected ?string $user = null;

    protected ?string $pass = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.nagios.url', env('NAGIOS_URL', '')), '/');
        $this->user = (string) config('services.nagios.user', env('NAGIOS_USER', ''));
        $this->pass = (string) config('services.nagios.pass', env('NAGIOS_PASS', ''));
    }

    /**
     * Obtiene el estado de todos los hosts y servicios desde Nagios.
     *
     * Soporta dos formatos de respuesta:
     *  - JSON (aplicación/json)
     *  - Texto plano delimitado por pipes (text/plain): hostname|servicename|state|state_label|output|last_check
     *
     * @return array ['success' => bool, 'error' => string|null, 'data' => array]
     */
    public function obtenerEstado(): array
    {
        if (empty($this->baseUrl) || empty($this->user) || empty($this->pass)) {
            return [
                'success' => false,
                'error' => 'Faltan credenciales de Nagios en la configuración',
                'data' => [],
            ];
        }

        try {
            $response = Http::withBasicAuth($this->user, $this->pass)
                ->timeout(15)
                ->get($this->baseUrl);

            if ($response->failed()) {
                Log::error('Nagios API error: HTTP '.$response->status().' - '.$response->body());

                return [
                    'success' => false,
                    'error' => 'HTTP '.$response->status(),
                    'data' => [],
                ];
            }

            $contenido = $response->body();
            $contentType = $response->header('content-type') ?? '';
            $esJson = str_contains($contentType, 'application/json') || trim($contenido)[0] === '{' || trim($contenido)[0] === '[';

            if ($esJson) {
                $data = $response->json();

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $preview = substr(trim(strip_tags($contenido)), 0, 200);
                    Log::error('Nagios response is not valid JSON. Content preview: '.$preview);

                    return [
                        'success' => false,
                        'error' => 'Respuesta no válida de Nagios: '.$preview,
                        'data' => [],
                    ];
                }

                return [
                    'success' => true,
                    'error' => null,
                    'data' => $data ?? [],
                ];
            }

            // Formato texto plano delimitado por pipes
            $lineas = array_filter(explode("\n", trim($contenido)));

            if (count($lineas) < 2) {
                return [
                    'success' => false,
                    'error' => 'Respuesta vacía de Nagios',
                    'data' => [],
                ];
            }

            // Primera línea es el header
            $header = explode('|', $lineas[0]);
            $datos = [];

            for ($i = 1; $i < count($lineas); $i++) {
                $valores = explode('|', $lineas[$i]);
                $fila = [];

                foreach ($header as $idx => $columna) {
                    $fila[trim($columna)] = $valores[$idx] ?? '';
                }

                $datos[] = $fila;
            }

            return [
                'success' => true,
                'error' => null,
                'data' => $datos,
            ];
        } catch (\Exception $e) {
            Log::error('Nagios connection failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Mapea el código de estado de Nagios a los estados usados en tu módulo Monitoreo.
     *
     * Nagios: 0=OK, 1=WARNING, 2=CRITICAL, 3=UNKNOWN
     * Tu modelo: online, degradado, offline, desconocido
     */
    public function mapearEstado(int $estadoNagios): string
    {
        return match ($estadoNagios) {
            0 => 'online',
            1 => 'degradado',
            2 => 'offline',
            3 => 'desconocido',
            default => 'desconocido',
        };
    }

    /**
     * Extrae métricas de CPU, memoria y disco del output de un plugin de Nagios.
     *
     * Soporta formatos típicos:
     *  - CPU: "OK: Percent was 37.58 %"
     *  - Disco: "WARNING: Used disk space was 82.40 % (Used: 394.35 GB, Free: 84.43 GB, Total: 478.78 GB)"
     *  - Memoria: "OK: Memory usage was 58.60 % (Available: 7.08 GB, Total: 17.10 GB, Free: 7.08 GB, Used: 10.03 GB)"
     *
     * @return array ['cpu_porcentaje' => float|null, 'memoria_porcentaje' => float|null, 'memoria_usada_gb' => float|null, 'memoria_total_gb' => float|null, 'memoria_libre_gb' => float|null, 'disco_porcentaje' => float|null, 'disco_usado_gb' => float|null, 'disco_total_gb' => float|null, 'disco_libre_gb' => float|null]
     */
    public function extraerMetricas(string $output, string $nombreServicio): array
    {
        $metricas = [
            'cpu_porcentaje' => null,
            'memoria_porcentaje' => null,
            'memoria_usada_gb' => null,
            'memoria_total_gb' => null,
            'memoria_libre_gb' => null,
            'disco_porcentaje' => null,
            'disco_usado_gb' => null,
            'disco_total_gb' => null,
            'disco_libre_gb' => null,
        ];

        $nombreLower = strtolower($nombreServicio);

        // --- CPU ---
        if (str_contains($nombreLower, 'cpu') || str_contains($output, 'Percent was')) {
            if (preg_match('/Percent was\s+([\d.]+)\s*%/i', $output, $m)) {
                $metricas['cpu_porcentaje'] = (float) $m[1];
            }
        }

        // --- Memoria ---
        if (str_contains($nombreLower, 'mem') || str_contains($output, 'Memory usage')) {
            if (preg_match('/Memory usage was\s+([\d.]+)\s*%/i', $output, $m)) {
                $metricas['memoria_porcentaje'] = (float) $m[1];
            }
            if (preg_match('/Used:\s*([\d.]+)\s*GB/i', $output, $m)) {
                $metricas['memoria_usada_gb'] = (float) $m[1];
            }
            if (preg_match('/Total:\s*([\d.]+)\s*GB/i', $output, $m)) {
                $metricas['memoria_total_gb'] = (float) $m[1];
            }
            if (preg_match('/(?:Free|Available):\s*([\d.]+)\s*GB/i', $output, $m)) {
                $metricas['memoria_libre_gb'] = (float) $m[1];
            }
        }

        // --- Disco ---
        if (str_contains($nombreLower, 'disk') || str_contains($nombreLower, 'disco') || str_contains($output, 'Used disk space')) {
            if (preg_match('/Used disk space was\s+([\d.]+)\s*%/i', $output, $m)) {
                $metricas['disco_porcentaje'] = (float) $m[1];
            }
            if (preg_match('/Used:\s*([\d.]+)\s*GB/i', $output, $m2)) {
                $metricas['disco_usado_gb'] = (float) $m2[1];
            }
            if (preg_match('/Free:\s*([\d.]+)\s*GB/i', $output, $m3)) {
                $metricas['disco_libre_gb'] = (float) $m3[1];
            }
            if (preg_match('/Total:\s*([\d.]+)\s*GB/i', $output, $m4)) {
                $metricas['disco_total_gb'] = (float) $m4[1];
            }
        }

        return $metricas;
    }

    /**
     * Extrae la lista de servicios del JSON de Nagios normalizada.
     * Soporta tanto JSON como el formato pipe-delimited que devuelve estado.php.
     *
     * @return array<int, array> Cada elemento: ['host' => ..., 'service' => ..., 'estado' => int, 'output' => string, 'latency' => int|null, 'last_check' => string|null, 'cpu_porcentaje' => float|null, ...]
     */
    public function normalizarServicios(array $data): array
    {
        $servicios = [];

        // Detectar si es formato pipe-delimited (tiene claves como 'hostname', 'servicename')
        $esPipeDelimited = ! empty($data) && isset($data[0]) && is_array($data[0])
            && (isset($data[0]['hostname']) || isset($data[0]['host_name']));

        if ($esPipeDelimited) {
            foreach ($data as $fila) {
                $nombreHost = $fila['hostname'] ?? $fila['host_name'] ?? '';
                $nombreServicio = $fila['servicename'] ?? $fila['service_description'] ?? $fila['service'] ?? '';
                $estado = $fila['state'] ?? $fila['current_state'] ?? $fila['estado'] ?? 3;
                $output = $fila['output'] ?? $fila['plugin_output'] ?? $fila['mensaje'] ?? '';
                $lastCheckRaw = $fila['last_check'] ?? $fila['last_check_time'] ?? null;

                // Convertir timestamp Unix a datetime si es numérico
                $lastCheck = null;
                if ($lastCheckRaw !== null && $lastCheckRaw !== '' && is_numeric($lastCheckRaw)) {
                    $lastCheck = Carbon::createFromTimestamp((int) $lastCheckRaw)->format('Y-m-d H:i:s');
                }

                $metricas = $this->extraerMetricas((string) $output, (string) $nombreServicio);

                $servicios[] = [
                    'host' => $nombreHost,
                    'service' => $nombreServicio,
                    'estado' => (int) $estado,
                    'output' => (string) $output,
                    'latency' => null,
                    'last_check' => $lastCheck,
                    'cpu_porcentaje' => $metricas['cpu_porcentaje'],
                    'memoria_porcentaje' => $metricas['memoria_porcentaje'],
                    'memoria_usada_gb' => $metricas['memoria_usada_gb'],
                    'memoria_total_gb' => $metricas['memoria_total_gb'],
                    'memoria_libre_gb' => $metricas['memoria_libre_gb'],
                    'disco_porcentaje' => $metricas['disco_porcentaje'],
                    'disco_usado_gb' => $metricas['disco_usado_gb'],
                    'disco_total_gb' => $metricas['disco_total_gb'],
                    'disco_libre_gb' => $metricas['disco_libre_gb'],
                ];
            }

            return $servicios;
        }

        // Formato JSON estructurado
        $hosts = $data['hosts'] ?? $data['host'] ?? $data ?? [];

        foreach ($hosts as $hostKey => $hostData) {
            $nombreHost = $hostData['host_name'] ?? $hostData['host'] ?? $hostKey;
            $serviciosHost = $hostData['services'] ?? $hostData['service'] ?? [];

            foreach ($serviciosHost as $svcKey => $svcData) {
                $nombreServicio = $svcData['description'] ?? $svcData['service_description'] ?? $svcData['service'] ?? $svcKey;
                $estado = $svcData['current_state'] ?? $svcData['estado'] ?? $svcData['state'] ?? 3;
                $output = $svcData['plugin_output'] ?? $svcData['output'] ?? $svcData['mensaje'] ?? '';
                $latency = $svcData['latency'] ?? $svcData['tiempo_respuesta_ms'] ?? null;

                $metricas = $this->extraerMetricas((string) $output, (string) $nombreServicio);

                $servicios[] = [
                    'host' => $nombreHost,
                    'service' => $nombreServicio,
                    'estado' => (int) $estado,
                    'output' => (string) $output,
                    'latency' => $latency !== null ? (int) $latency : null,
                    'last_check' => $svcData['last_check'] ?? null,
                    'cpu_porcentaje' => $metricas['cpu_porcentaje'],
                    'memoria_porcentaje' => $metricas['memoria_porcentaje'],
                    'memoria_usada_gb' => $metricas['memoria_usada_gb'],
                    'memoria_total_gb' => $metricas['memoria_total_gb'],
                    'memoria_libre_gb' => $metricas['memoria_libre_gb'],
                    'disco_porcentaje' => $metricas['disco_porcentaje'],
                    'disco_usado_gb' => $metricas['disco_usado_gb'],
                    'disco_total_gb' => $metricas['disco_total_gb'],
                    'disco_libre_gb' => $metricas['disco_libre_gb'],
                ];
            }
        }

        return $servicios;
    }
}
