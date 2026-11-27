<?php

namespace App\Livewire;

use App\Models\Monitoreo;
use App\Services\NagiosService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class MonitoreoDashboard extends Component
{
    #[Url]
    public $buscar = '';

    #[Url]
    public $tipo = '';

    #[Url]
    public $estatus = '';

    public $resumen = [];

    public $tipos = [];

    public $hosts = [];

    public $totalHosts = 0;

    public $ultimaSync = '--:--';

    public $ultimoSyncTimestamp = 0;

    public $syncEnProgreso = false;

    public $page = 1;

    public function mount()
    {
        $this->cargarDatos();
        $this->autoSincronizarNagios(app(NagiosService::class));
    }

    public function cargarDatos()
    {
        $query = Monitoreo::activos()->orderBy('tipo')->orderBy('nombre');
        $monitoreos = $query->get();

        $this->resumen = [
            'total' => $monitoreos->count(),
            'online' => $monitoreos->where('estatus', 'online')->count(),
            'offline' => $monitoreos->where('estatus', 'offline')->count(),
            'degradado' => $monitoreos->where('estatus', 'degradado')->count(),
        ];

        $this->tipos = $monitoreos->pluck('tipo')->unique()->sort()->all();

        // Aplicar filtros
        $filtrados = $monitoreos->filter(function ($m) {
            $coincideBuscar = empty($this->buscar) ||
                stripos($m->nagios_host_name ?: $m->host_o_url, $this->buscar) !== false ||
                stripos($m->host_o_url, $this->buscar) !== false ||
                stripos($m->nombre, $this->buscar) !== false;

            $coincideTipo = empty($this->tipo) || $m->tipo === $this->tipo;

            $coincideEstatus = empty($this->estatus) || $m->estatus === $this->estatus;

            return $coincideBuscar && $coincideTipo && $coincideEstatus;
        });

        // Agrupar por host para mostrar una card por computadora
        $hosts = $filtrados->groupBy(function ($m) {
            return $m->nagios_host_name ?: $m->host_o_url;
        });

        // Convertir a array simple para Livewire (evitar problemas de serialización)
        $hostsArray = [];
        foreach ($hosts as $nombreHost => $serviciosHost) {
            $primerServicio = $serviciosHost->first();
            $hostActivo = $primerServicio->activo;
            $estadoMasGrave = 'online';
            $ordenGravedad = ['desconocido' => 0, 'online' => 1, 'degradado' => 2, 'offline' => 3];
            foreach ($serviciosHost as $s) {
                if (($ordenGravedad[$s->estatus] ?? 0) > ($ordenGravedad[$estadoMasGrave] ?? 0)) {
                    $estadoMasGrave = $s->estatus;
                }
            }

            $serviciosArray = [];
            foreach ($serviciosHost as $s) {
                $serviciosArray[] = [
                    'id_Monitoreo' => $s->id_Monitoreo,
                    'nombre' => $s->nombre,
                    'nagios_service_description' => $s->nagios_service_description,
                    'estatus' => $s->estatus,
                    'mensaje_ultimo_check' => $s->mensaje_ultimo_check,
                    'badgeClass' => $s->badgeClass(),
                    'etiquetaEstatus' => $s->etiquetaEstatus(),
                ];
            }

            $svcCpu = $serviciosHost->first(function ($s) {
                return stripos($s->nagios_service_description ?? '', 'cpu') !== false;
            });
            $svcMemoria = $serviciosHost->first(function ($s) {
                return stripos($s->nagios_service_description ?? '', 'mem') !== false;
            });
            $svcDisco = $serviciosHost->first(function ($s) {
                $desc = $s->nagios_service_description ?? '';

                return stripos($desc, 'disk') !== false || stripos($desc, 'disco') !== false;
            });

            $hayMetricas = ($svcCpu && $svcCpu->cpu_porcentaje !== null)
                || ($svcMemoria && $svcMemoria->memoria_porcentaje !== null)
                || ($svcDisco && $svcDisco->disco_porcentaje !== null);

            $hostsArray[] = [
                'nombreHost' => $nombreHost,
                'host_o_url' => $primerServicio->host_o_url,
                'id_Monitoreo' => $primerServicio->id_Monitoreo,
                'tipo' => $primerServicio->tipo,
                'activo' => $hostActivo,
                'estadoMasGrave' => $estadoMasGrave,
                'hayMetricas' => $hayMetricas,
                'ultimo_check' => $primerServicio->ultimo_check ? $primerServicio->ultimo_check->format('d/m/Y H:i') : '—',
                'ultimo_sincronizado_nagios' => $primerServicio->ultimo_sincronizado_nagios ? $primerServicio->ultimo_sincronizado_nagios->format('d/m/Y H:i') : null,
                'servicios' => $serviciosArray,
                'svcCpu' => $svcCpu ? [
                    'cpu_porcentaje' => $svcCpu->cpu_porcentaje,
                ] : null,
                'svcMemoria' => $svcMemoria ? [
                    'memoria_porcentaje' => $svcMemoria->memoria_porcentaje,
                    'memoria_usada_gb' => $svcMemoria->memoria_usada_gb,
                    'memoria_total_gb' => $svcMemoria->memoria_total_gb,
                ] : null,
                'svcDisco' => $svcDisco ? [
                    'disco_porcentaje' => $svcDisco->disco_porcentaje,
                    'disco_usado_gb' => $svcDisco->disco_usado_gb,
                    'disco_total_gb' => $svcDisco->disco_total_gb,
                ] : null,
            ];
        }

        $this->totalHosts = count($hostsArray);

        // Paginación: 6 tarjetas (hosts) por página
        $porPagina = 6;
        $offset = ($this->page - 1) * $porPagina;
        $this->hosts = array_slice($hostsArray, $offset, $porPagina);
    }

    public function updatedBuscar()
    {
        $this->page = 1;
        $this->cargarDatos();
    }

    public function updatedTipo()
    {
        $this->page = 1;
        $this->cargarDatos();
    }

    public function updatedEstatus()
    {
        $this->page = 1;
        $this->cargarDatos();
    }

    public function updatedPage()
    {
        $this->cargarDatos();
    }

    public function sincronizarNagios(NagiosService $nagios)
    {
        $this->syncEnProgreso = true;

        try {
            $resultado = $nagios->obtenerEstado();

            if (! $resultado['success']) {
                $this->dispatch('notify', 'error', 'Error al conectar con Nagios: '.$resultado['error']);

                return;
            }

            $servicios = $nagios->normalizarServicios($resultado['data']);

            $sincronizados = 0;

            DB::beginTransaction();
            try {
                foreach ($servicios as $svc) {
                    $monitoreo = Monitoreo::where('nagios_host_name', $svc['host'])
                        ->where('nagios_service_description', $svc['service'])
                        ->first();

                    $estadoMapeado = $nagios->mapearEstado($svc['estado']);
                    $ultimoCheck = $svc['last_check'] ?? now();

                    $datosMetricas = [
                        'cpu_porcentaje' => $svc['cpu_porcentaje'] ?? null,
                        'memoria_porcentaje' => $svc['memoria_porcentaje'] ?? null,
                        'memoria_usada_gb' => $svc['memoria_usada_gb'] ?? null,
                        'memoria_total_gb' => $svc['memoria_total_gb'] ?? null,
                        'memoria_libre_gb' => $svc['memoria_libre_gb'] ?? null,
                        'disco_porcentaje' => $svc['disco_porcentaje'] ?? null,
                        'disco_usado_gb' => $svc['disco_usado_gb'] ?? null,
                        'disco_total_gb' => $svc['disco_total_gb'] ?? null,
                        'disco_libre_gb' => $svc['disco_libre_gb'] ?? null,
                    ];

                    if ($monitoreo) {
                        $monitoreo->update(array_merge([
                            'estatus' => $estadoMapeado,
                            'tiempo_respuesta_ms' => $svc['latency'],
                            'mensaje_ultimo_check' => $svc['output'],
                            'ultimo_check' => $ultimoCheck,
                            'ultimo_sincronizado_nagios' => now(),
                        ], $datosMetricas));
                    } else {
                        Monitoreo::create(array_merge([
                            'nombre' => $svc['host'].' - '.$svc['service'],
                            'tipo' => 'servicio',
                            'host_o_url' => $svc['host'],
                            'puerto' => null,
                            'intervalo_minutos' => 5,
                            'estatus' => $estadoMapeado,
                            'tiempo_respuesta_ms' => $svc['latency'],
                            'ultimo_check' => $ultimoCheck,
                            'mensaje_ultimo_check' => $svc['output'],
                            'activo' => true,
                            'fechaAlta' => now(),
                            'nagios_host_name' => $svc['host'],
                            'nagios_service_description' => $svc['service'],
                            'ultimo_sincronizado_nagios' => now(),
                        ], $datosMetricas));
                    }

                    $sincronizados++;
                }

                DB::commit();

                $this->ultimaSync = now()->toLocaleTimeString('es-MX', ['hour' => '2-digit', 'minute' => '2-digit']);
                $this->ultimoSyncTimestamp = time();
                $this->cargarDatos();
                $this->dispatch('notify', 'success', 'Sincronización con Nagios completada: '.$sincronizados.' servicios actualizados.');
            } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatch('notify', 'error', 'Error al sincronizar con Nagios: '.$e->getMessage());
            }
        } finally {
            $this->syncEnProgreso = false;
        }
    }

    public function autoSincronizarNagios(NagiosService $nagios)
    {
        $this->syncEnProgreso = true;

        try {
            $resultado = $nagios->obtenerEstado();

            if (! $resultado['success']) {
                return;
            }

            $servicios = $nagios->normalizarServicios($resultado['data']);

            $sincronizados = 0;

            DB::beginTransaction();
            try {
                foreach ($servicios as $svc) {
                    $monitoreo = Monitoreo::where('nagios_host_name', $svc['host'])
                        ->where('nagios_service_description', $svc['service'])
                        ->first();

                    $estadoMapeado = $nagios->mapearEstado($svc['estado']);
                    $ultimoCheck = $svc['last_check'] ?? now();

                    $datosMetricas = [
                        'cpu_porcentaje' => $svc['cpu_porcentaje'] ?? null,
                        'memoria_porcentaje' => $svc['memoria_porcentaje'] ?? null,
                        'memoria_usada_gb' => $svc['memoria_usada_gb'] ?? null,
                        'memoria_total_gb' => $svc['memoria_total_gb'] ?? null,
                        'memoria_libre_gb' => $svc['memoria_libre_gb'] ?? null,
                        'disco_porcentaje' => $svc['disco_porcentaje'] ?? null,
                        'disco_usado_gb' => $svc['disco_usado_gb'] ?? null,
                        'disco_total_gb' => $svc['disco_total_gb'] ?? null,
                        'disco_libre_gb' => $svc['disco_libre_gb'] ?? null,
                    ];

                    if ($monitoreo) {
                        $monitoreo->update(array_merge([
                            'estatus' => $estadoMapeado,
                            'tiempo_respuesta_ms' => $svc['latency'],
                            'mensaje_ultimo_check' => $svc['output'],
                            'ultimo_check' => $ultimoCheck,
                            'ultimo_sincronizado_nagios' => now(),
                        ], $datosMetricas));
                    } else {
                        Monitoreo::create(array_merge([
                            'nombre' => $svc['host'].' - '.$svc['service'],
                            'tipo' => 'servicio',
                            'host_o_url' => $svc['host'],
                            'puerto' => null,
                            'intervalo_minutos' => 5,
                            'estatus' => $estadoMapeado,
                            'tiempo_respuesta_ms' => $svc['latency'],
                            'ultimo_check' => $ultimoCheck,
                            'mensaje_ultimo_check' => $svc['output'],
                            'activo' => true,
                            'fechaAlta' => now(),
                            'nagios_host_name' => $svc['host'],
                            'nagios_service_description' => $svc['service'],
                            'ultimo_sincronizado_nagios' => now(),
                        ], $datosMetricas));
                    }

                    $sincronizados++;
                }

                DB::commit();

                $this->ultimaSync = now()->toLocaleTimeString('es-MX', ['hour' => '2-digit', 'minute' => '2-digit']);
                $this->ultimoSyncTimestamp = time();
                $this->cargarDatos();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        } finally {
            $this->syncEnProgreso = false;
        }
    }

    public function toggle($id)
    {
        $monitoreo = Monitoreo::findOrFail($id);
        $monitoreo->activo = ! $monitoreo->activo;
        $monitoreo->save();

        $this->cargarDatos();
        $this->dispatch('notify', 'success', $monitoreo->activo ? 'Monitoreo activado' : 'Monitoreo desactivado');
    }

    public function eliminar($id)
    {
        $monitoreo = Monitoreo::findOrFail($id);
        $monitoreo->delete();

        $this->cargarDatos();
        $this->dispatch('notify', 'success', 'Objeto de monitoreo eliminado correctamente');
    }

    public function render()
    {
        return view('livewire.monitoreo-dashboard');
    }
}
