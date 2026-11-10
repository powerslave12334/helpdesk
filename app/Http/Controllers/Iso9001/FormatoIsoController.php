<?php

namespace App\Http\Controllers\Iso9001;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Módulo ISO 9001: panel de formatos FO-IT y acceso a sus documentos.
 *
 * El catálogo vive en config/formatos_iso.php. Los formatos habilitados
 * declaran ahí su clave "controlador" y este controlador delega en ellos las
 * acciones de listado, alta, PDF, borrado y firmado, de modo que habilitar
 * un formato nuevo no requiere tocar las rutas. La verificación de huella
 * se resuelve por folio (FO-IT-XX-NNNN) contra el formato correspondiente.
 */
class FormatoIsoController extends Controller
{
    /** Catálogo de formatos ordenado. */
    private function formatos(): array
    {
        $formatos = config('formatos_iso.formatos', []);

        usort($formatos, fn (array $a, array $b): int => ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0));

        return $formatos;
    }

    /** Busca un formato por clave (FO-IT-01, ...); 404 si no existe. */
    private function buscarFormato(string $clave): array
    {
        foreach ($this->formatos() as $formato) {
            if ($formato['clave'] === $clave) {
                return $formato;
            }
        }

        abort(404, 'Formato ISO 9001 no encontrado.');
    }

    /** Panel con la cuadrícula de formatos (estilo "Nuevo Equipo"). */
    public function index(): View
    {
        return view('iso9001.panel', [
            'formatos' => $this->formatos(),
        ]);
    }

    /**
     * Documentos del formato: la lista si el formato está habilitado o el
     * marcador de "próximamente" si aún no tiene controlador.
     */
    public function documentos(string $clave)
    {
        $formato = $this->buscarFormato($clave);

        if (! empty($formato['controlador'])) {
            return app($formato['controlador'])->index();
        }

        return view('iso9001.formato', compact('formato'));
    }

    /** Formulario de nuevo documento del formato. */
    public function create(string $clave)
    {
        return $this->invocar($clave, 'create');
    }

    /** Guarda el documento (la validación la resuelve el FormRequest del formato). */
    public function store(string $clave)
    {
        return $this->invocar($clave, 'store');
    }

    /** PDF del documento (stream o descarga según ?descargar=1). */
    public function pdf(string $clave, string $id)
    {
        return $this->invocar($clave, 'pdf', ['id' => $id]);
    }

    /** Borrado lógico del documento. */
    public function destroy(string $clave, string $id)
    {
        return $this->invocar($clave, 'destroy', ['id' => $id]);
    }

    /** Firma del documento (la validación la resuelve el FormRequest del formato). */
    public function firmar(string $clave, string $id)
    {
        return $this->invocar($clave, 'firmar', ['id' => $id]);
    }

    /**
     * Verificación de la huella SHA-256 de un documento por su folio
     * (p. ej. FO-IT-01-0001). El folio define el formato y su controlador
     * hace el cotejo; el resultado se muestra en una vista genérica.
     */
    public function verificar(Request $request): View
    {
        $folio = trim((string) $request->query('folio'));
        $resultado = null;
        $documento = null;

        if (preg_match('/^(FO-IT-\d+)-(\d+)$/', $folio, $partes) === 1) {
            $formato = $this->formatoHabilitado($partes[1]);

            if ($formato !== null) {
                $verificacion = app()->call(
                    [app($formato['controlador']), 'verificar'],
                    ['id' => $partes[2]]
                );

                if ($verificacion === null) {
                    $resultado = 'no_encontrado';
                } else {
                    $resultado = $verificacion['resultado'];
                    $documento = $verificacion['documento'];
                }
            } else {
                $resultado = 'no_encontrado';
            }
        } elseif ($folio !== '') {
            $resultado = 'no_encontrado';
        }

        return view('iso9001.verificar', [
            'folio' => $folio,
            'resultado' => $resultado,
            'documento' => $documento,
        ]);
    }

    /** Formato del catálogo que ya tiene controlador; null si no aplica. */
    private function formatoHabilitado(string $clave): ?array
    {
        foreach ($this->formatos() as $formato) {
            if ($formato['clave'] === $clave && ! empty($formato['controlador'])) {
                return $formato;
            }
        }

        return null;
    }

    /**
     * Invoca un método del controlador propio del formato resolviendo sus
     * dependencias (p. ej. el FormRequest) con el contenedor; 404 si el
     * formato aún no está habilitado.
     *
     * @param  array<string, mixed>  $parametros
     * @return mixed
     */
    private function invocar(string $clave, string $metodo, array $parametros = [])
    {
        $formato = $this->buscarFormato($clave);

        if (empty($formato['controlador'])) {
            abort(404, "El formato {$formato['clave']} aún no está habilitado.");
        }

        return app()->call([app($formato['controlador']), $metodo], $parametros);
    }
}
