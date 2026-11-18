<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function login(): View|RedirectResponse
    {
        $usuario = session('usuario');

        if (is_array($usuario) && ($usuario['rol'] ?? 'cliente') === 'admin') {
            return redirect()->route('admin.productos');
        }

        return view('admin.login');
    }

    public function ingresar(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = User::where('email', $credenciales['email'])->first();

        if (! $usuario || ! Hash::check($credenciales['password'], $usuario->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Credenciales incorrectas.']);
        }

        if ($usuario->rol !== 'admin') {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Esta cuenta no tiene permisos de administrador.']);
        }

        $request->session()->put('usuario', [
            'id' => $usuario->id,
            'nombre' => $usuario->name,
            'email' => $usuario->email,
            'rol' => $usuario->rol,
        ]);

        $request->session()->regenerate();

        return redirect()->route('admin.productos')
            ->with('mensaje', 'Bienvenido al panel de administración.');
    }

    public function salir(Request $request): RedirectResponse
    {
        $request->session()->forget('usuario');

        return redirect()->route('admin.login')->with('mensaje', 'Sesión cerrada.');
    }

    public function productos(): View
    {
        return view('admin.productos', [
            'productos' => Producto::orderByDesc('id')->paginate(10),
            'total' => Producto::count(),
        ]);
    }

    public function eliminar(Producto $producto): RedirectResponse
    {
        $nombre = $producto->nombre;
        $producto->delete();

        return back()->with('mensaje', "Producto \"{$nombre}\" eliminado.");
    }
}
