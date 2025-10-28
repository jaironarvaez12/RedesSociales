@if(Session::has('alert'))
<div class="alert alert-warning background-warning">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <i class="icofont icofont-close-line-circled text-white"></i>
    </button>
    <strong><i class="fa fa-exclamation-triangle"></i> Alerta!</strong>
    {{Session::get('alert')}}
</div>
 @endif