<?php

namespace App\Http\Controllers;

use Google\Client as GoogleClient;
use Google\Service\SearchConsole;

class InicioController extends Controller
{
    public function inicio()
    {
        // $tiendaId = session('tienda_actual');
        // $articulos = ArticulosModel::count();
        // $ventas = Punto_VentaModel::where('created_at', '>=', now()->subDays(30))->where('id_tienda',$tiendaId )->sum('total');
        // $compras= CompPedidosModel::where('created_at', '>=', now()->subDays(30))->where('id_tienda',$tiendaId )->sum('total');
        // $presupuestos= PresupuestosModel::where('created_at', '>=', now()->subDays(30))->where('id_tienda',$tiendaId )->sum('total');
     
        // $pedidos = Punto_VentaModel::UltimosPedidos($tiendaId)  ; // devuelve Collection
        // $ultimascompras=CompPedidosModel::UltimosPedidos($tiendaId);

        $hoy = getdate();
        $hora=$hoy["hours"];
        
        if ($hora < 6) 
        { 
            $bienvenida = 'Buenas Madrugadas ';
        }
        elseif ($hora < 12) 
            { 
                $bienvenida = 'Buenos Días ';
            }
            elseif($hora<=18)
                {
                    $bienvenida = 'Buenas Tardes ';
                }
                else
                    { 
                        $bienvenida = 'Buenas Noches '; 
                    }

        return view('inicio', compact('bienvenida'));
    }
}

