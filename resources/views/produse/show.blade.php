@extends('layouts.app')
@section('content')
@include('produse.modal')
<div class="container-fluid">
<div class="row">

    <div class="col-md-12">


        <div class="card">
            <div class="card-content">
                <form id="form_order">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ordonare dupa</label>
                                <select name="sort_by" class="form-control" onchange="load_data()">
                                    <option value="title" selected>Denumire</option>
                                    <option value="sku">Cod Produs</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tip Ordonare</label>
                                <select name="sort_direction" class="form-control" onchange="load_data()">
                                    <option value="ASC" selected>ASC</option>
                                    <option value="DESC">DESC</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
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
    var data=$('#form_order').serialize();
    jQuery.ajax({

        url: "/load_products",
        method: "POST",
        data:data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

    }).done(function(response) {

        jQuery("#results").html(response);
    });
}
jQuery(document).ready(function(){

load_data();
});
</script>
@endpush
@endsection