@if(Session::has('sesion'))
<div class="alert alert-warning background-warning">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <i class="icofont icofont-close-line-circled text-white"></i>
    </button>
    <strong><i class="fa fa-history"></i> Se ha Cerrado la Sesion!</strong>
    {{Session::get('sesion')}}
</div>
 @endif