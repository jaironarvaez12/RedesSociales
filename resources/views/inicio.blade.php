@extends('layouts.master')

@section('titulo', 'Inicio')

@section('titulo_pagina', $bienvenida.' , Bienvenid@  a ' . config('app.name') )

@section('contenido')
{{-- @include('mensajes.MsjExitoso')
@include('mensajes.MsjError')
@include('mensajes.MsjValidacion') --}}

 <div class="dashboard-main-body">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
  <h6 class="fw-semibold mb-0">Datos</h6>
  <ul class="d-flex align-items-center gap-2">
    <li class="fw-medium">
      <a href="{{ url('/') }}" class="d-flex align-items-center gap-1 hover-text-primary">
        <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
       Inicio
      </a>
    </li>
  
   
  </ul>
</div>
    
    {{-- <div class="row gy-4">
        <div class="col-12">
            <div class="card radius-12">
                <div class="card-body p-16">
                    <div class="row gy-4">
                       <div class="col-xxl-3 col-xl-4 col-sm-6">
                            <div class="px-20 py-16 shadow-none radius-8 h-100 gradient-deep-1 left-line line-bg-primary position-relative overflow-hidden">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">
                                    <div>
                                        <span class="mb-2 fw-medium text-secondary-light text-md">Total de Ventas</span>
                                        <h6 class="fw-semibold mb-1">{{$ventas}} €</h6>
                                    </div>

                                    <!-- Icono con enlace a pedidos.create -->
                                    <a href="{{ route('pedidos.create') }}"
                                        class="w-44-px h-44-px radius-8 d-inline-flex justify-content-center align-items-center text-2xl mb-12 bg-primary-100 text-primary-600"
                                        aria-label="Crear pedido" title="Crear pedido">
                                        <i class="ri-shopping-cart-fill" aria-hidden="true"></i>
                                    </a>
                                </div>

                                <p class="text-sm mb-0">
                                <span class="bg-success-focus px-1 rounded-2 fw-medium text-success-main text-sm">
                                    <i class="ri-arrow-right-up-line"></i> 80%
                                </span> Ultimo Mes
                                </p>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-xl-4 col-sm-6">
                            <div class="px-20 py-16 shadow-none radius-8 h-100 gradient-deep-2 left-line line-bg-lilac position-relative overflow-hidden">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">
                                    <div>
                                        <span class="mb-2 fw-medium text-secondary-light text-md">Total Compras</span>
                                        <h6 class="fw-semibold mb-1">{{$compras}} €</h6>
                                    </div>
                                    
                                     <a href="{{ route('comppedidos.create') }}"
                                        class="w-44-px h-44-px radius-8 d-inline-flex justify-content-center align-items-center text-2xl mb-12 bg-lilac-200 text-lilac-600"
                                        aria-label="Crear Compra" title="Crear Compra">
                                        <i class="ri-handbag-fill" aria-hidden="true"></i>
                                    </a>
                                </div>
                                <p class="text-sm mb-0"><span class="bg-success-focus px-1 rounded-2 fw-medium text-success-main text-sm"><i class="ri-arrow-right-up-line"></i> 95%</span> Ultimo Mes</p>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-xl-4 col-sm-6">
                            <div class="px-20 py-16 shadow-none radius-8 h-100 gradient-deep-3 left-line line-bg-success position-relative overflow-hidden">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-8">
                                    <div>
                                        <span class="mb-2 fw-medium text-secondary-light text-md">Total Presupuestos</span>
                                        <h6 class="fw-semibold mb-1">{{$presupuestos}} €</h6>
                                    </div>
                                   

                                    <a href="{{ route('presupuestos.create') }}"
                                        class="w-44-px h-44-px radius-8 d-inline-flex justify-content-center align-items-center text-2xl mb-12 bg-success-200 text-success-600"
                                        aria-label="Crear Compra" title="Crear Compra">
                                        <i class="ri-shopping-cart-fill" aria-hidden="true"></i>
                                    </a>
                                </div>
                                <p class="text-sm mb-0"><span class="bg-danger-focus px-1 rounded-2 fw-medium text-danger-main text-sm"><i class="ri-arrow-right-down-line"></i> 30%</span> Ultimo Mes</p>
                            </div>
                        </div>
                      
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xxl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                        <h6 class="mb-2 fw-bold text-lg mb-0">Ventas Recientes</h6>
                        <a href="{{ route('pedidos.index') }}" class="text-primary-600 hover-text-primary d-flex align-items-center gap-1">
                            Ver Todas
                            <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                        </a>
                    </div>
                </div>
                <div class="card-body p-24">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Id</th>
                                    <th scope="col">Fecha  </th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pedidos as $pedido)
                                     <tr>
                                    <td>
                                        <span class="text-secondary-light">{{$pedido->id_venta}}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary-light">{{ date('d-m-Y g:i:s A', strtotime($pedido->fecha_creacion)) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary-light">{{$pedido->nombre_cliente}}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary-light">{{$pedido->total}}</span>
                                    </td>
                                  
                                </tr>
                                @endforeach
                               
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                        <h6 class="mb-2 fw-bold text-lg mb-0">Compras Recientes</h6>
                        <a href="{{ route('comppedidos.index') }}" class="text-primary-600 hover-text-primary d-flex align-items-center gap-1">
                            Ver Todas
                            <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                        </a>
                    </div>
                </div>
                <div class="card-body p-24">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr>
                                   <th scope="col">Id</th>
                                   <th scope="col">Fecha  </th>
                                   <th scope="col">Proveedor</th>
                                   <th scope="col">Metodo de Pago</th>
                                   <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                 @foreach ($ultimascompras as $compra)
                                     <tr>
                                    <td>
                                        <span class="text-secondary-light">{{$compra->id_pedido}}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary-light">{{ date('d-m-Y g:i:s A', strtotime($compra->fecha)) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary-light">{{$compra->nombre_proveedor}}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary-light">{{$compra->nombre_metodo}}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary-light">{{$compra->total}}</span>
                                    </td>
                                  
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
  </div>

@endsection

@section('scripts')

@endsection