<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $entidad = $request->query('entidad');
        $idUsuario = $request->query('id_usuario');
        $search = $request->query('search');

        $registros = Auditoria::with('usuario:id_usuario,nombre_usuario,email')
            ->when($entidad, fn($q) => $q->where('entidad', $entidad))
            ->when($idUsuario, fn($q) => $q->where('id_usuario', $idUsuario))
            ->when($search, fn($q) => $q->where('descripcion', 'like', "%{$search}%"))
            ->orderByDesc('id_auditoria')
            ->paginate($request->query('per_page', 50));

        return response()->json([
            'status' => 'success',
            'data' => $registros,
        ]);
    }
}
