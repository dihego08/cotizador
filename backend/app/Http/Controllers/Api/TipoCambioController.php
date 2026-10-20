<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoCambio;
use Illuminate\Http\Request;

class TipoCambioController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => TipoCambio::orderBy('fecha', 'desc')->take(30)->get(),
        ]);
    }

    public function actual()
    {
        $tc = TipoCambio::orderBy('fecha', 'desc')->first();

        return response()->json([
            'status' => 'success',
            'data' => $tc,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'required|date|unique:tipo_cambio,fecha',
            'compra' => 'required|numeric|min:0.0001',
            'venta' => 'required|numeric|min:0.0001',
        ]);

        $tc = TipoCambio::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de cambio registrado con éxito.',
            'data' => $tc,
        ], 201);
    }
}
