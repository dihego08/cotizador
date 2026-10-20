<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteContacto;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $helpdeskOnly = $request->boolean('es_helpdesk');

        $clientes = Cliente::with('contactos')
            ->when($search, function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                    ->orWhere('num_documento', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('atencion', 'like', "%{$search}%");
            })
            ->when($helpdeskOnly, fn($q) => $q->where('es_cliente_helpdesk', true))
            ->orderBy('razon_social')
            ->paginate($request->query('per_page', 25));

        return response()->json([
            'status' => 'success',
            'data' => $clientes,
        ]);
    }

    public function show($id)
    {
        $cliente = Cliente::with(['contactos', 'cotizaciones' => fn($q) => $q->latest('id_cotizacion')->take(5)])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $cliente,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_documento' => 'required|in:DNI,RUC,CE,PASAPORTE,OTROS',
            'num_documento' => 'required|string|max:15|unique:clientes,num_documento',
            'razon_social' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'departamento' => 'nullable|string|max:50',
            'provincia' => 'nullable|string|max:50',
            'distrito' => 'nullable|string|max:50',
            'ubigeo' => 'nullable|string|max:6',
            'email' => 'nullable|email|max:150',
            'telefono' => 'nullable|string|max:50',
            'atencion' => 'nullable|string|max:150',
            'es_cliente_helpdesk' => 'boolean',
        ]);

        $cliente = Cliente::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Cliente registrado con éxito.',
            'data' => $cliente,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $validated = $request->validate([
            'tipo_documento' => 'sometimes|required|in:DNI,RUC,CE,PASAPORTE,OTROS',
            'num_documento' => 'sometimes|required|string|max:15|unique:clientes,num_documento,' . $id . ',id_cliente',
            'razon_social' => 'sometimes|required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'departamento' => 'nullable|string|max:50',
            'provincia' => 'nullable|string|max:50',
            'distrito' => 'nullable|string|max:50',
            'ubigeo' => 'nullable|string|max:6',
            'email' => 'nullable|email|max:150',
            'telefono' => 'nullable|string|max:50',
            'atencion' => 'nullable|string|max:150',
            'es_cliente_helpdesk' => 'boolean',
        ]);

        $cliente->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Cliente actualizado con éxito.',
            'data' => $cliente,
        ]);
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cliente eliminado con éxito.',
        ]);
    }

    public function addContacto(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $validated = $request->validate([
            'tipo_documento' => 'required|in:DNI,CE,PASAPORTE,OTROS',
            'num_documento' => 'required|string|max:15',
            'nombres_apellidos' => 'required|string|max:150',
            'cargo' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'telefono' => 'nullable|string|max:50',
            'activo' => 'boolean',
        ]);

        $validated['id_cliente'] = $cliente->id_cliente;
        $contacto = ClienteContacto::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Contacto añadido correctamente.',
            'data' => $contacto,
        ], 201);
    }

    public function removeContacto($id, $contactoId)
    {
        $contacto = ClienteContacto::where('id_cliente', $id)->where('id_contacto', $contactoId)->firstOrFail();
        $contacto->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Contacto eliminado correctamente.',
        ]);
    }
}
