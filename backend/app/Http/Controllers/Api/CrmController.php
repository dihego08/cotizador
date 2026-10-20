<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\CrmActividad;
use App\Models\CrmOportunidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        $idEmpresa = $request->query('id_empresa', $request->user()->id_empresa);
        $etapa = $request->query('etapa');
        $search = $request->query('search');
        $prioridad = $request->query('prioridad');

        $query = CrmOportunidad::with(['cliente', 'usuario', 'cotizacion'])
            ->withCount('actividades')
            ->when($idEmpresa, fn($q) => $q->where('id_empresa', $idEmpresa))
            ->when($etapa, fn($q) => $q->where('etapa', $etapa))
            ->when($prioridad, fn($q) => $q->where('prioridad', $prioridad))
            ->when($search, function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                    ->orWhereHas('cliente', fn($c) => $c->where('razon_social', 'like', "%{$search}%")
                        ->orWhere('num_documento', 'like', "%{$search}%"));
            });

        $oportunidades = $query->orderBy('updated_at', 'desc')->get();

        // Métricas rápidas de pipeline
        $metricas = [
            'total_pipeline' => $oportunidades->whereNotIn('etapa', ['GANADO', 'PERDIDO'])->sum('valor_estimado'),
            'total_ganado' => $oportunidades->where('etapa', 'GANADO')->sum('valor_estimado'),
            'total_perdido' => $oportunidades->where('etapa', 'PERDIDO')->sum('valor_estimado'),
            'conteo_abiertas' => $oportunidades->whereNotIn('etapa', ['GANADO', 'PERDIDO'])->count(),
            'conteo_ganadas' => $oportunidades->where('etapa', 'GANADO')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $oportunidades,
            'metricas' => $metricas,
        ]);
    }

    public function show($id)
    {
        $oportunidad = CrmOportunidad::with([
            'cliente.contactos',
            'usuario',
            'cotizacion',
            'actividades.usuario'
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $oportunidad,
        ]);
    }

    public function store(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'id_empresa' => 'required|exists:empresas,id_empresa',
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'id_cotizacion' => 'nullable|exists:cotizaciones,id_cotizacion',
            'titulo' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'valor_estimado' => 'required|numeric|min:0',
            'moneda' => 'nullable|string|max:5',
            'etapa' => 'nullable|in:PROSPECTO,CONTACTADO,PROPUESTA,NEGOCIACION,GANADO,PERDIDO',
            'probabilidad' => 'nullable|integer|between:0,100',
            'prioridad' => 'nullable|in:BAJA,MEDIA,ALTA',
            'fecha_cierre_estimada' => 'nullable|date',
        ]);

        $probabilidad = $validated['probabilidad'] ?? match ($validated['etapa'] ?? 'PROSPECTO') {
            'PROSPECTO' => 20,
            'CONTACTADO' => 40,
            'PROPUESTA' => 60,
            'NEGOCIACION' => 80,
            'GANADO' => 100,
            'PERDIDO' => 0,
            default => 20,
        };

        $oportunidad = CrmOportunidad::create([
            'id_empresa' => $validated['id_empresa'],
            'id_cliente' => $validated['id_cliente'],
            'id_usuario' => $usuario->id_usuario,
            'id_cotizacion' => $validated['id_cotizacion'] ?? null,
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'valor_estimado' => $validated['valor_estimado'],
            'moneda' => $validated['moneda'] ?? 'USD',
            'etapa' => $validated['etapa'] ?? 'PROSPECTO',
            'probabilidad' => $probabilidad,
            'prioridad' => $validated['prioridad'] ?? 'MEDIA',
            'fecha_cierre_estimada' => $validated['fecha_cierre_estimada'] ?? null,
        ]);

        // Registrar actividad automática de creación
        CrmActividad::create([
            'id_empresa' => $oportunidad->id_empresa,
            'id_cliente' => $oportunidad->id_cliente,
            'id_oportunidad' => $oportunidad->id_oportunidad,
            'id_usuario' => $usuario->id_usuario,
            'tipo' => 'NOTA',
            'titulo' => 'Oportunidad creada',
            'descripcion' => "Oportunidad registrada en etapa '{$oportunidad->etapa}' con valor estimado de {$oportunidad->moneda} {$oportunidad->valor_estimado}.",
            'fecha_actividad' => now(),
            'completado' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Oportunidad comercial registrada con éxito.',
            'data' => $oportunidad->load(['cliente', 'usuario']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $oportunidad = CrmOportunidad::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:150',
            'id_cliente' => 'sometimes|required|exists:clientes,id_cliente',
            'id_cotizacion' => 'nullable|exists:cotizaciones,id_cotizacion',
            'descripcion' => 'nullable|string',
            'valor_estimado' => 'sometimes|required|numeric|min:0',
            'moneda' => 'nullable|string|max:5',
            'etapa' => 'sometimes|required|in:PROSPECTO,CONTACTADO,PROPUESTA,NEGOCIACION,GANADO,PERDIDO',
            'probabilidad' => 'nullable|integer|between:0,100',
            'prioridad' => 'nullable|in:BAJA,MEDIA,ALTA',
            'fecha_cierre_estimada' => 'nullable|date',
            'motivo_perdida' => 'nullable|string',
        ]);

        $oldEtapa = $oportunidad->etapa;
        $oportunidad->update($validated);

        // Si cambió la etapa, registrar en bitácora
        if (isset($validated['etapa']) && $validated['etapa'] !== $oldEtapa) {
            CrmActividad::create([
                'id_empresa' => $oportunidad->id_empresa,
                'id_cliente' => $oportunidad->id_cliente,
                'id_oportunidad' => $oportunidad->id_oportunidad,
                'id_usuario' => $request->user()->id_usuario,
                'tipo' => 'NOTA',
                'titulo' => "Etapa actualizada: {$validated['etapa']}",
                'descripcion' => "Avanzó de '{$oldEtapa}' a '{$validated['etapa']}'." . ($oportunidad->motivo_perdida ? " Motivo: {$oportunidad->motivo_perdida}" : ''),
                'fecha_actividad' => now(),
                'completado' => true,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Oportunidad actualizada.',
            'data' => $oportunidad->load(['cliente', 'usuario', 'cotizacion']),
        ]);
    }

    public function updateEtapa(Request $request, $id)
    {
        $oportunidad = CrmOportunidad::findOrFail($id);

        $validated = $request->validate([
            'etapa' => 'required|in:PROSPECTO,CONTACTADO,PROPUESTA,NEGOCIACION,GANADO,PERDIDO',
            'motivo_perdida' => 'nullable|string',
        ]);

        $oldEtapa = $oportunidad->etapa;
        $newEtapa = $validated['etapa'];

        $probabilidad = match ($newEtapa) {
            'PROSPECTO' => 20,
            'CONTACTADO' => 40,
            'PROPUESTA' => 60,
            'NEGOCIACION' => 80,
            'GANADO' => 100,
            'PERDIDO' => 0,
        };

        $oportunidad->update([
            'etapa' => $newEtapa,
            'probabilidad' => $probabilidad,
            'motivo_perdida' => $newEtapa === 'PERDIDO' ? ($validated['motivo_perdida'] ?? 'Desestimado por el cliente') : null,
        ]);

        CrmActividad::create([
            'id_empresa' => $oportunidad->id_empresa,
            'id_cliente' => $oportunidad->id_cliente,
            'id_oportunidad' => $oportunidad->id_oportunidad,
            'id_usuario' => $request->user()->id_usuario,
            'tipo' => 'NOTA',
            'titulo' => "Cambio de etapa: {$newEtapa}",
            'descripcion' => "El trato pasó de '{$oldEtapa}' a '{$newEtapa}'.",
            'fecha_actividad' => now(),
            'completado' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Oportunidad movida a {$newEtapa}.",
            'data' => $oportunidad,
        ]);
    }

    public function destroy($id)
    {
        $oportunidad = CrmOportunidad::findOrFail($id);
        $oportunidad->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Oportunidad eliminada.',
        ]);
    }

    public function storeActividad(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'id_empresa' => 'required|exists:empresas,id_empresa',
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'id_oportunidad' => 'nullable|exists:crm_oportunidades,id_oportunidad',
            'tipo' => 'required|in:LLAMADA,REUNION,WHATSAPP,CORREO,NOTA',
            'titulo' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'fecha_actividad' => 'nullable|date',
            'proximo_seguimiento' => 'nullable|date',
            'completado' => 'nullable|boolean',
        ]);

        $actividad = CrmActividad::create([
            'id_empresa' => $validated['id_empresa'],
            'id_cliente' => $validated['id_cliente'],
            'id_oportunidad' => $validated['id_oportunidad'] ?? null,
            'id_usuario' => $usuario->id_usuario,
            'tipo' => $validated['tipo'],
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'fecha_actividad' => $validated['fecha_actividad'] ?? now(),
            'proximo_seguimiento' => $validated['proximo_seguimiento'] ?? null,
            'completado' => $validated['completado'] ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Actividad registrada.',
            'data' => $actividad->load('usuario'),
        ], 201);
    }
}
