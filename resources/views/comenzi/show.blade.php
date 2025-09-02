@extends('layouts.app')
@section('content')
@include('comenzi.modal')
@include('comenzi.modal-curier')
<div class="container-fluid">

<div class="row">
    <div class="col-md-6">
        <button class="btn btn-info" style="width:100%" id="comanda_curier">Comanda Curier</button>
    </div>
    <div class="col-md-6">
        <button class="btn btn-info" style="width:100%" id="confirma_comanda_curier">Confirma Comanda Curier</button>
    </div>
</div>

<div class="row mt-3">

    <div class="col-md-12">


        <div class="card">
            <div class="card-content">
                <div id="results">
                    <div class="alert alert-warning" role="alert">
                        Asteptam datele de la wordpress!
                    </div>

                </div>
                    
                
            </div>
        </div>
    </div>
</div>
@push('js')
<script>
function load_data()
{
    jQuery.ajax({

        url: "/load_comenzi",
        method: "POST",
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

    }).done(function(response) {

        jQuery("#results").html(response);
    });
}
jQuery(document).ready(function(){

load_data();
$("#comanda_curier").click(function(){

$.ajax({

    url:"/obtine_comenzi_curier",
    method:"POST",
    headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

    }).done(function(response) {
        $("#modal_curier").modal('show');
        jQuery("#modal_results_curier").html(response);
    });


});
});
$("#confirma_comanda_curier").click(function(){

    $.ajax({

        url:"/confirma_comanda_curier",
        method:"POST",
             headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
                    return confirm("Sigur doriti sa confirmati comanda curierului?");
      },
    }).done(function(response) {
       mesaj(response);
    });
    


});


</script>
@endpush
@endsection