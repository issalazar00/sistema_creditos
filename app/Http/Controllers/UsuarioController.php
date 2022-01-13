<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $usuarios = Usuario::select();
        if ($request->usuario && ($request->usuario != '')) {
            $usuarios  =     $usuarios->where('document', 'LIKE', "%$request->usuario%")
                ->orWhere('name', 'LIKE', "%$request->usuario%")
                ->orWhere('email', 'LIKE', "%$request->usuario%");
        }
        $usuarios = $usuarios->paginate(5);

        return $usuarios;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $usuario = Usuario::findOrFail($id);
        Usuario::destroy($id);
        return redirect('usuario')->with('mensaje', 'Usuario eliminado correctamente');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $usuario = new Usuario();
        $usuario->email = $request['email'];
        $usuario->password = $request['password'];
        $usuario->name = $request['name'];
        $usuario->last_name = $request['last_name'];
        $usuario->cell_phone = $request['cell_phone'];
        $usuario->address = $request['address'];
        $usuario->type_document = $request['type_document'];
        $usuario->document = $request['document'];
        $usuario->photo = 'undefindef';
        $usuario->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function show(Usuario $usuario)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function edit(Usuario $usuario)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Usuario $usuario)
    {
        $usuario = Usuario::find($request->id);
        
        $usuario->email = $request['email'];
        $usuario->password = $request['password'];
        $usuario->name = $request['name'];
        $usuario->last_name = $request['last_name'];
        $usuario->cell_phone = $request['cell_phone'];
        $usuario->address = $request['address'];
        $usuario->type_document = $request['type_document'];
        $usuario->document = $request['document'];
        $usuario->photo = 'undefindef';
        $usuario->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        //
    }

    public function changeStatus(Usuario $usuario)
    {
        //
        $u = Usuario::find($usuario->id);
        $u->status = !$u->status;
        $u->save();
    }
}
