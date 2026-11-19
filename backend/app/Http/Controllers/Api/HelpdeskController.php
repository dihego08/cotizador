<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteContacto;
use App\Models\HelpdeskAtencion;
use App\Models\HelpdeskCategoria;
use App\Models\HelpdeskPausa;
use App\Models\Usuario;
use App\Rules\ExisteEnCuenta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HelpdeskController extends Controller
{
    public function categorias()
    {
        $categorias = HelpdeskCategoria::with('subcategorias')
            ->whereNull('id_padre')
            ->where('activo', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categorias,
        ]);
    }

    public function index(Request $request)
    {
        $estado = $request->query('estado_cierre');
        $categoria = $request->query('categoria');
        $search = $request->query('search');

        $atenciones = HelpdeskAtencion::with(['cliente', 'contacto', 'subcategoria', 'tecnico', 'tecnicoSeguimiento', 'pausas'])
            ->where('id_empresa', $this->empresaActiva($request))
            ->when($estado, fn($q) => $q->where('estado_cierre', $estado))
            ->when($categoria, fn($q) => $q->where('categoria', $categoria))
            ->when($search, function ($q) use ($search) {
                $q->where(fn($sub) => $sub->where('codigo_ticket', 'like', "%{$search}%")
                    ->orWhere('nombre_solicitante', 'like', "%{$search}%")
                    ->orWhere('documento_solicitante', 'like', "%{$search}%")
                    ->orWhere('descripcion_requerimiento', 'like', "%{$search}%")
                    ->orWhereHas('cliente', fn($c) => $c->where('razon_social', 'like', "%{$search}%")));
            })
            ->orderBy('id_atencion', 'desc')
            ->paginate($request->query('per_page', 25));

        return response()->json([
            'status' => 'success',
            'data' => $atenciones,
        ]);
    }

    public function show(Request $request, $id)
    {
        $atencion = HelpdeskAtencion::with(['cliente', 'contacto', 'subcategoria', 'tecnico', 'tecnicoSeguimiento', 'pausas'])
            ->where('id_empresa', $this->empresaActiva($request))
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $atencion,
        ]);
    }

    public function store(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'id_empresa' => ['required', $this->reglaEmpresa($request)],
            'id_cliente' => ['required', new ExisteEnCuenta(Cliente::class)],
            'id_contacto' => [
                'nullable',
                new ExisteEnCuenta(ClienteContacto::class, fn($q) => $q->where('id_cliente', $request->input('id_cliente'))),
            ],
            'tipo_usuario' => 'required|in:CORPORATIVO,PARTICULAR',
            'documento_solicitante' => 'required|string|max:20',
            'nombre_solicitante' => 'required|string|max:150',
            'categoria' => 'required|in:HARDWARE,SOFTWARE,REDES_CONECTIVIDAD,SEGURIDAD,SISTEMAS,OTROS',
            'id_subcategoria' => 'nullable|exists:helpdesk_categorias,id_categoria',
            'canal_atencion' => 'required|in:LLAMADA,ACCESO_REMOTO,PRESENCIAL,CORREO,WHATSAPP',
            'descripcion_requerimiento' => 'required|string',
            'acciones_realizadas' => 'required|string',
            'estado_cierre' => 'required|in:CONCLUIDO,EN_PROCESO,ESCALADO_N2,ESPERA_REPUESTO',
            'nivel_satisfaccion' => 'nullable|integer|between:1,5',
            'acciones_programadas' => 'nullable|string',
            'fecha_proxima_revision' => 'nullable|date',
            'id_tecnico_seguimiento' => ['nullable', new ExisteEnCuenta(Usuario::class)],
            'fecha_atencion' => 'required|date',
            'hora_inicio' => 'required|date',
            'hora_fin' => 'required|date',
            'tiempo_pausa_segundos' => 'nullable|integer|min:0',
            'tiempo_efectivo_segundos' => 'nullable|integer|min:0',
        ]);

        $atencion = DB::transaction(function () use ($validated, $usuario) {
            $year = date('Y');
            // Correlativo propio de cada empresa
            $lastTicket = HelpdeskAtencion::where('id_empresa', $validated['id_empresa'])
                ->where('codigo_ticket', 'like', "HD-{$year}-%")
                ->orderBy('id_atencion', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastTicket && preg_match("/HD-{$year}-(\d+)/", $lastTicket->codigo_ticket, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
            $codigo = sprintf("HD-%s-%04d", $year, $nextNumber);

            $inicio = Carbon::parse($validated['hora_inicio']);
            $fin = Carbon::parse($validated['hora_fin']);
            $pausaSec = (int) ($validated['tiempo_pausa_segundos'] ?? 0);
            
            $totalSec = max(0, $fin->diffInSeconds($inicio));
            $efectivoSec = max(0, $totalSec - $pausaSec);

            $validated['codigo_ticket'] = $codigo;
            $validated['id_tecnico'] = $usuario->id_usuario;
            $validated['tiempo_pausa_segundos'] = $pausaSec;
            $validated['tiempo_efectivo_segundos'] = $validated['tiempo_efectivo_segundos'] ?? $efectivoSec;

            return HelpdeskAtencion::create($validated);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket de atención helpdesk creado con éxito.',
            'data' => $atencion->load(['cliente', 'contacto', 'subcategoria', 'tecnico']),
        ], 201);
    }

    public function registrarPausa(Request $request, $id)
    {
        $atencion = HelpdeskAtencion::where('id_empresa', $this->empresaActiva($request))->findOrFail($id);

        $validated = $request->validate([
            'motivo_pausa' => 'nullable|string|max:150',
        ]);

        $pausa = HelpdeskPausa::create([
            'id_atencion' => $atencion->id_atencion,
            'hora_inicio_pausa' => now(),
            'motivo_pausa' => $validated['motivo_pausa'] ?? 'Pausa en la atención',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pausa iniciada.',
            'data' => $pausa,
        ]);
    }

    public function reanudarPausa(Request $request, $id)
    {
        $atencion = HelpdeskAtencion::where('id_empresa', $this->empresaActiva($request))->findOrFail($id);

        $pausa = HelpdeskPausa::where('id_atencion', $atencion->id_atencion)
            ->whereNull('hora_fin_pausa')
            ->latest('id_pausa')
            ->firstOrFail();

        $horaFin = now();
        $pausa->update(['hora_fin_pausa' => $horaFin]);

        $segundos = $horaFin->diffInSeconds($pausa->hora_inicio_pausa);

        $atencion->increment('tiempo_pausa_segundos', $segundos);

        return response()->json([
            'status' => 'success',
            'message' => 'Atención reanudada.',
            'pausa_duracion_segundos' => $segundos,
            'total_pausa_segundos' => $atencion->tiempo_pausa_segundos,
        ]);
    }
}
