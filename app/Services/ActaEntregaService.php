<?php

namespace App\Services;

use App\Models\EquipoUsuario;

class ActaEntregaService
{
    public function generarActa(EquipoUsuario $asignacion, string $entregadoPor): string
    {
        $asignacion->load(['equipo.tipoEquipo', 'usuario', 'area']);

        $equipo = $asignacion->equipo;
        $usuario = $asignacion->usuario;
        $fechaAsignacion = $asignacion->fechaAsignacion
            ? $asignacion->fechaAsignacion->format('d/m/Y')
            : 'N/A';

        $nombreEquipo = $equipo->nombreEquipo ?? 'N/A';
        $marca = $equipo->marca ?? 'N/A';
        $modelo = $equipo->modelo ?? 'N/A';
        $numeroSerie = $equipo->numeroSerie ?? 'N/A';
        $imei = $equipo->imei ?? 'N/A';
        $nombreUsuario = $usuario ? $usuario->nombreCompleto() : 'N/A';
        $correoUsuario = $usuario->correo ?? 'N/A';
        $departamento = $usuario->departamento->nombre ?? 'N/A';
        $condiciones = $asignacion->observaciones ?: 'Equipo en buen estado de funcionamiento.';

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Entrega de Equipo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 30px; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; text-transform: uppercase; letter-spacing: 2px; }
        .header p { font-size: 11px; color: #555; margin-top: 5px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 11px; }
        .info-row strong { min-width: 140px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px 10px; text-align: left; font-size: 11px; }
        th { background-color: #2c3e50; color: #fff; text-transform: uppercase; font-size: 10px; }
        .legal-text { margin: 25px 0; padding: 15px; border: 1px solid #ccc; background-color: #fafafa; font-size: 10px; line-height: 1.6; text-align: justify; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
        .signature-block { width: 45%; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-top: 80px; padding-top: 8px; }
        .signature-block .name { font-weight: bold; font-size: 11px; }
        .signature-block .role { font-size: 10px; color: #555; }
        .footer { text-align: center; margin-top: 40px; font-size: 9px; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
        .print-btn { display: block; margin: 20px auto; padding: 10px 30px; background: #2c3e50; color: #fff; border: none; cursor: pointer; font-size: 13px; border-radius: 4px; }
        .print-btn:hover { background: #34495e; }
        @media print { .print-btn { display: none; } body { padding: 10px; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Acta de Entrega de Equipo</h1>
        <p>Documento oficial de entrega y recepci&oacute;n de activo fijo</p>
    </div>

    <div class="info-row">
        <span><strong>Fecha de entrega:</strong> ' . $fechaAsignacion . '</span>
        <span><strong>Folio:</strong> AE-' . str_pad($asignacion->id_EquipoUsuario, 5, '0', STR_PAD_LEFT) . '</span>
    </div>
    <div class="info-row">
        <span><strong>Entregado por:</strong> ' . htmlspecialchars($entregadoPor) . '</span>
        <span><strong>Recibido por:</strong> ' . htmlspecialchars($nombreUsuario) . '</span>
    </div>
    <div class="info-row">
        <span><strong>Departamento:</strong> ' . htmlspecialchars($departamento) . '</span>
        <span><strong>Correo electr&oacute;nico:</strong> ' . htmlspecialchars($correoUsuario) . '</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cantidad</th>
                <th>Descripci&oacute;n</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>N&uacute;mero de Serie</th>
                <th>IMEI</th>
                <th>Condiciones</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align:center">1</td>
                <td>' . htmlspecialchars($nombreEquipo) . '</td>
                <td>' . htmlspecialchars($marca) . '</td>
                <td>' . htmlspecialchars($modelo) . '</td>
                <td>' . htmlspecialchars($numeroSerie) . '</td>
                <td>' . htmlspecialchars($imei) . '</td>
                <td>' . htmlspecialchars($condiciones) . '</td>
            </tr>
        </tbody>
    </table>

    <div class="legal-text">
        <strong>DECLARACI&Oacute;N DE RESPONSABILIDAD:</strong><br><br>
        El equipo mencionado en la presente acta es propiedad exclusiva de la empresa y se entrega al
        colaborador mencionado en las condiciones descritas. El receptor se compromete a:
        <br><br>
        1. Hacer buen uso del equipo asignado, utiliz&aacute;ndolo exclusivamente para actividades propias del trabajo.<br>
        2. Mantener el equipo en buen estado de conservaci&oacute;n y funcionamiento.<br>
        3. No prestar, ceder ni transferir el equipo a terceros sin autorizaci&oacute;n escrita.<br>
        4. Comunicar de manera inmediata cualquier da&ntilde;o, falla o robo del equipo al departamento de sistemas.<br>
        5. Devolver el equipo en las mismas condiciones en las que fue recibido al momento de baja, renuncia o
        reasignaci&oacute;n.<br>
        6. El equipo contar&aacute; con software instalado exclusivamente autorizado por el &aacute;rea de TI.<br>
        7. En caso de p&eacute;rdida o da&ntilde;o irreversible, el valor de reposici&oacute;n ser&aacute; descontado
        conforme a las pol&iacute;ticas de la empresa.<br><br>
        El incumplimiento de estas condiciones podr&aacute; dar lugar a las acciones disciplinarias y/o legales
        correspondientes conforme al reglamento interno de la empresa.
    </div>

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-line">
                <div class="name">' . htmlspecialchars($entregadoPor) . '</div>
                <div class="role">Entregado por</div>
            </div>
        </div>
        <div class="signature-block">
            <div class="signature-line">
                <div class="name">' . htmlspecialchars($nombreUsuario) . '</div>
                <div class="role">Recibido por</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Acta generada el ' . now()->format('d/m/Y H:i:s') . ' &mdash; Sistema de Helpdesk &mdash; Documento v&a;lido sin firma digital
    </div>

    <button class="print-btn" onclick="window.print()">Imprimir / Guardar PDF</button>
</body>
</html>';

        return $html;
    }
}
