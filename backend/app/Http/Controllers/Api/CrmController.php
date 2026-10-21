<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\CrmActividad;
use App\Models\CrmOportunidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        $idEmpresa = $request->query('id_empresa', $request->user()->id_empresa);
        $etapa = $request->query('etapa');
        $search = $request->query('search');
        $prioridad = $request->query('prioridad');
        $misTratos = $request->query('mis_tratos');

        $query = CrmOportunidad::with([
            'cliente.contactos',
            'usuario',
            'cotizacion',
            'actividades' => fn($q) => $q->orderBy('fecha_actividad', 'desc'),
        ])
            ->withCount('actividades')
            ->when($idEmpresa, fn($q) => $q->where('id_empresa', $idEmpresa))
            ->when($etapa, fn($q) => $q->where('etapa', $etapa))
            ->when($prioridad, fn($q) => $q->where('prioridad', $prioridad))
            ->when($misTratos, fn($q) => $q->where('id_usuario', $request->user()->id_usuario))
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

        $cotizacionesCliente = \App\Models\Cotizacion::where('id_cliente', $oportunidad->id_cliente)
            ->where('id_empresa', $oportunidad->id_empresa)
            ->select('id_cotizacion', 'numero_cotizacion', 'asunto', 'total', 'moneda', 'estado', 'fecha_emision')
            ->orderBy('id_cotizacion', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $oportunidad,
            'cotizaciones_cliente' => $cotizacionesCliente,
        ]);
    }

    public function vincularCotizacion(Request $request, $id)
    {
        $oportunidad = CrmOportunidad::findOrFail($id);
        $idCotizacion = $request->input('id_cotizacion');

        if ($idCotizacion) {
            $cot = \App\Models\Cotizacion::findOrFail($idCotizacion);
            $oportunidad->id_cotizacion = $cot->id_cotizacion;
            $oportunidad->valor_estimado = (float)$cot->total;
            $oportunidad->moneda = $cot->moneda;
            if (in_array($oportunidad->etapa, ['PROSPECTO', 'CONTACTADO'])) {
                $oportunidad->etapa = 'PROPUESTA';
                $oportunidad->probabilidad = 60;
            }
            $oportunidad->save();

            CrmActividad::create([
                'id_empresa' => $oportunidad->id_empresa,
                'id_cliente' => $oportunidad->id_cliente,
                'id_oportunidad' => $oportunidad->id_oportunidad,
                'id_usuario' => $request->user()->id_usuario,
                'tipo' => 'NOTA',
                'titulo' => "Cotización formal vinculada: {$cot->numero_cotizacion}",
                'descripcion' => "Se vinculó la cotización {$cot->numero_cotizacion} ({$cot->moneda} " . number_format($cot->total, 2) . "). La etapa comercial se ajustó a 'PROPUESTA'.",
                'fecha_actividad' => now(),
                'completado' => true,
            ]);
        } else {
            $oldCotNum = $oportunidad->cotizacion ? $oportunidad->cotizacion->numero_cotizacion : 'N/A';
            $oportunidad->id_cotizacion = null;
            $oportunidad->save();

            CrmActividad::create([
                'id_empresa' => $oportunidad->id_empresa,
                'id_cliente' => $oportunidad->id_cliente,
                'id_oportunidad' => $oportunidad->id_oportunidad,
                'id_usuario' => $request->user()->id_usuario,
                'tipo' => 'NOTA',
                'titulo' => 'Cotización desvinculada',
                'descripcion' => "Se desvinculó la cotización {$oldCotNum} de este trato.",
                'fecha_actividad' => now(),
                'completado' => true,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $idCotizacion ? 'Cotización vinculada correctamente.' : 'Cotización desvinculada.',
            'data' => $oportunidad->load(['cliente', 'usuario', 'cotizacion']),
        ]);
    }

    public function store(Request $request)
    {
        $usuario = $request->user();

        // Soporte para crear prospecto rápido si no existe aún en la base de datos
        $idCliente = $request->input('id_cliente');
        if (empty($idCliente) && !empty($request->input('nuevo_cliente_nombre'))) {
            $nuevoCliente = Cliente::create([
                'tipo_documento' => $request->input('nuevo_cliente_tipo_doc', 'OTROS'),
                'num_documento' => $request->input('nuevo_cliente_num_doc', 'LEAD-' . date('ymd') . '-' . rand(100, 999)),
                'razon_social' => trim($request->input('nuevo_cliente_nombre')),
                'telefono' => $request->input('nuevo_cliente_telefono'),
                'email' => $request->input('nuevo_cliente_email'),
                'estado_crm' => 'PROSPECTO',
                'origen_lead' => $request->input('nuevo_cliente_origen', 'PROSPECCION_DIRECTA'),
                'created_at' => now(),
            ]);
            $idCliente = $nuevoCliente->id_cliente;
        }

        $validated = $request->validate([
            'id_empresa' => 'required|exists:empresas,id_empresa',
            'id_cliente' => $idCliente ? 'nullable' : 'required|exists:clientes,id_cliente',
            'id_cotizacion' => 'nullable|exists:cotizaciones,id_cotizacion',
            'titulo' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'valor_estimado' => 'nullable|numeric|min:0',
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

        $valorEstimado = !empty($validated['valor_estimado']) ? (float)$validated['valor_estimado'] : 0.00;
        $moneda = $validated['moneda'] ?? 'USD';

        // Si se vinculó una cotización existente, sincronizar su total y moneda
        if (!empty($validated['id_cotizacion'])) {
            $cot = \App\Models\Cotizacion::find($validated['id_cotizacion']);
            if ($cot) {
                $valorEstimado = (float)$cot->total;
                $moneda = $cot->moneda;
            }
        }

        $oportunidad = CrmOportunidad::create([
            'id_empresa' => $validated['id_empresa'],
            'id_cliente' => $idCliente ?: $validated['id_cliente'],
            'id_usuario' => $usuario->id_usuario,
            'id_cotizacion' => $validated['id_cotizacion'] ?? null,
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'valor_estimado' => $valorEstimado,
            'moneda' => $moneda,
            'etapa' => $validated['etapa'] ?? 'PROSPECTO',
            'probabilidad' => $probabilidad,
            'prioridad' => $validated['prioridad'] ?? 'MEDIA',
            'fecha_cierre_estimada' => $validated['fecha_cierre_estimada'] ?? null,
        ]);

        // Registrar actividad automática de creación
        $descMonto = $valorEstimado > 0 ? "con valor estimado de {$moneda} " . number_format($valorEstimado, 2) : "con valor por definir";
        CrmActividad::create([
            'id_empresa' => $oportunidad->id_empresa,
            'id_cliente' => $oportunidad->id_cliente,
            'id_oportunidad' => $oportunidad->id_oportunidad,
            'id_usuario' => $usuario->id_usuario,
            'tipo' => 'NOTA',
            'titulo' => 'Oportunidad creada',
            'descripcion' => "Trato registrado en etapa '{$oportunidad->etapa}' {$descMonto}.",
            'fecha_actividad' => now(),
            'completado' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Oportunidad comercial registrada con éxito.',
            'data' => $oportunidad->load(['cliente', 'usuario', 'cotizacion']),
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
            'valor_estimado' => 'nullable|numeric|min:0',
            'moneda' => 'nullable|string|max:5',
            'etapa' => 'sometimes|required|in:PROSPECTO,CONTACTADO,PROPUESTA,NEGOCIACION,GANADO,PERDIDO',
            'probabilidad' => 'nullable|integer|between:0,100',
            'prioridad' => 'nullable|in:BAJA,MEDIA,ALTA',
            'fecha_cierre_estimada' => 'nullable|date',
            'motivo_perdida' => 'nullable|string',
        ]);

        // Sincronizar con cotización si se vinculó una
        if (isset($validated['id_cotizacion']) && $validated['id_cotizacion']) {
            $cot = \App\Models\Cotizacion::find($validated['id_cotizacion']);
            if ($cot) {
                $validated['valor_estimado'] = (float)$cot->total;
                $validated['moneda'] = $cot->moneda;
                if ($oportunidad->etapa === 'PROSPECTO' || $oportunidad->etapa === 'CONTACTADO') {
                    $validated['etapa'] = 'PROPUESTA';
                    $validated['probabilidad'] = 60;
                }
            }
        }

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
            'archivo' => 'nullable|file|max:25600', // max 25MB
        ]);

        $archivoPath = null;
        $archivoNombre = null;
        $archivoSize = null;

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $archivoNombre = $file->getClientOriginalName();
            $archivoSize = $file->getSize();
            $archivoPath = $file->store('crm_adjuntos', 'public');
        }

        $actividad = CrmActividad::create([
            'id_empresa' => $validated['id_empresa'],
            'id_cliente' => $validated['id_cliente'],
            'id_oportunidad' => $validated['id_oportunidad'] ?? null,
            'id_usuario' => $usuario->id_usuario,
            'tipo' => $validated['tipo'],
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'archivo_path' => $archivoPath,
            'archivo_nombre' => $archivoNombre,
            'archivo_size' => $archivoSize,
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

    public function descargarArchivo($id)
    {
        $actividad = CrmActividad::findOrFail($id);

        if (!$actividad->archivo_path || !Storage::disk('public')->exists($actividad->archivo_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'El archivo adjunto no existe en el servidor.',
            ], 404);
        }

        return Storage::disk('public')->download($actividad->archivo_path, $actividad->archivo_nombre);
    }
}
