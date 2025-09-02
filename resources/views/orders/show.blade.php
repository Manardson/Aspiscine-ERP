@extends('layouts.app')
@section('content')
@include('comenzi.modal')

<div class="container-fluid">


<div class="row">
    <div class="col-md-12">
        <input type="hidden" id="all" value="all" />
        <input type="text"  onchange="load_data(this)"  class="form-control"   placeholder="Cauta Comanda"  value="" />
    </div>
</div>
<div class="row mt-3">

    <div class="col-md-12">


        <div class="card">
            <div class="card-content">
                <div id="results">
                    

                </div>
                    
                
            </div>
        </div>
    </div>
</div>
@push('js')
<script>
function load_data(elem)
{
    var value=$(elem).val();
    jQuery.ajax({

        url: "/load_all_orders",
        method: "POST",
        data:{
            value:value
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

    }).done(function(response) {

        jQuery("#results").html(response);
    });
}
jQuery(document).ready(function(){

load_data("#all");

});

</script>
@endpush
@endsection