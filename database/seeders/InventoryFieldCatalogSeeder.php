<?php

namespace Database\Seeders;

use App\Models\InventoryField;
use App\Models\InventoryFieldSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

/**
 * Migra el catálogo estático de config/equipo_campos_catalogo.php
 * a las tablas inventory_field_sections / inventory_fields.
 *
 * Es idempotente: si el catálogo ya existe en BD no toca nada,
 * para no pisar campos creados o editados por el usuario.
 */
class InventoryFieldCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (InventoryField::query()->exists()) {
            return;
        }

        $secciones = Config::get('equipo_campos_catalogo.secciones', []);

        $ordenSec = 1;
        foreach ($secciones as $claveSec => $seccion) {
            $seccionModel = InventoryFieldSection::updateOrCreate(
                ['key' => $claveSec],
                [
                    'title' => $seccion['titulo'],
                    'icon' => $seccion['icono'] ?? null,
                    'description' => $seccion['descripcion'] ?? null,
                    'order' => $ordenSec++,
                ],
            );

            $ordenCampo = 1;
            foreach ($seccion['campos'] as $claveCampo => $info) {
                InventoryField::updateOrCreate(
                    ['name' => $claveCampo],
                    [
                        'section_id' => $seccionModel->id,
                        'label' => $info['etiqueta'],
                        'type' => $this->mapTipo($info['tipo'] ?? 'texto'),
                        'help' => $info['ayuda'] ?? null,
                        'source' => InventoryField::SOURCE_COLUMN,
                        'order' => $ordenCampo++,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /** Traduce los tipos del catálogo legacy a tipos estándar. */
    private function mapTipo(string $legacy): string
    {
        return match ($legacy) {
            'texto' => 'text',
            'decimal' => 'decimal',
            'integer' => 'integer',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            'textarea' => 'textarea',
            'select' => 'select',
            'tags' => 'tags',
            default => 'text',
        };
    }
}
