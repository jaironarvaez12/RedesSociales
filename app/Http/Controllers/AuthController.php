<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Hash;
use Session;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('layouts.login');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
   public function store(Request $request)
    {
        $username = strtoupper($request->input('email'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Credenciales base y con condición de activo
        $credencialesBase    = ['email' => $username, 'password' => $password];
        $credencialesActivas = $credencialesBase; // <- usa 1/true si es boolean

        // === Rama AJAX (modal de verificación) ===
        if ($request->expectsJson() || $request->boolean('verify')) {
            try {
                if (\Auth::validate($credencialesActivas)) {
                    return response()->json(['ok' => true], 200);
                }
                // Si el password es correcto pero el usuario NO está activo
                if (\Auth::validate($credencialesBase)) {
                    return response()->json(['ok' => false, 'message' => 'Usuario inactivo'], 423);
                }
                return response()->json(['ok' => false, 'message' => 'Credenciales inválidas'], 422);
            } catch (\Illuminate\Session\TokenMismatchException $e) {
                return response()->json(['message' => 'CSRF token mismatch'], 419);
            }
        }

        // === Flujo normal de login ===
        if (\Auth::attempt($credencialesActivas, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        // Diferenciar mensajes
        if (\Auth::validate($credencialesBase)) {
            \Session::flash('message', 'Usuario inactivo. Contacta al administrador.');
        } else {
            \Session::flash('message', 'Los Datos Ingresados Son incorrectos');
        }

        return redirect('login')->withInput();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function logout(Request $request)
    {
       
        Auth::logout();

        $request->session()->invalidate();
 
        $request->session()->regenerateToken();

        return Redirect('login');
    }

}
