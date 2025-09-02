<div class="row">
    <div class="col-md-6">
        @if ($order->awb != '')
            <a href="/vezi_awb/{{ $order->order_id }}" target="_blank"><button type="button" class="btn btn-info"
                    id="vezi_awb" style="width:100%">Vezi AWB</button></a>
        @endif
    </div>

    <div class="col-md-6">
        @if ($order->factura_nr != '')
            <a href="/vezi_factura/{{ $order->order_id }}" target="_blank"><button type="button" class="btn btn-info"
                    style="width:100%">Vezi Factura</button></a>
        @endif
    </div>
</div>

<div class="card mt-3" style="border: 1px solid #b9b4b4 !important;">
<div class="row">
    <div class="col-md-12">
       

        <div class="alert alert-success" role="alert">
        @if($order->payment_method =="cod") Plata ramburs la livrare @elseif($order->payment_method =="netopiapayments") @endif
</div>
    </div>
</div>
    <div class="card-body">
        <h5 class="card-title">Date Client</h5>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Nume</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="first_name"
                        value="{{ $order->first_name }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Prenume</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="last_name"
                        value="{{ $order->last_name }}">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Adresa </label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="address_1"
                        value="{{ $order->address_1 }}">
                </div>
            </div>
        </div>

        <div class="row">


            <div class="col-md-4">
                <div class="form-group">
                    <label>Oras</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="city"
                        value="{{ $order->city }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Judet</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="state"
                        value="{{ $order->state }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Tara</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="country"
                        value="{{ $order->country }}">
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Cod Postal</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="postcode"
                        value="{{ $order->postcode }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Companie</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="company"
                        value="{{ $order->company }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Email</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="email"
                        value="{{ $order->email }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Telefon</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="phone"
                        value="{{ $order->phone }}">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card mt-3" style="border: 1px solid #b9b4b4 !important;">
    <div class="card-body">
        <h5 class="card-title">Date Livrare</h5>

        <div class="row">

            <div class="col-md-6">
                <div class="form-group">
                    <label>Destinatar Nume</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_fist_name"
                        value="{{ $order->livrare_first_name }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Destinatar Prenume</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_last_name"
                        value="{{ $order->livrare_last_name }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Companie</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_company"
                        value="{{ $order->livrare_company }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Addresa 1 ( strada pentru dpd curier)</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_address_1"
                        value="{{ $order->livrare_address_1 }}">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Adresa 2 ( numar strada pentru dpd curier)</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_address_2"
                        value="{{ $order->livrare_address_2 }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Oras</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_city"
                        value="{{ $order->livrare_city }}">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Judet</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_state"
                        value="{{ $order->livrare_state }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Cod Postal</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_postcode"
                        value="{{ $order->livrare_postcode }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Tara</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_country"
                        value="{{ $order->livrare_country }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Telefon</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="livrare_phone"
                        value="{{ $order->livrare_phone }}">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Mod de plata</label>

                    <input type="text" class="form-control" onchange="edit_input(this)" name="payment_method_title"
                        value="{{ $order->payment_method_title }}">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card mt-3" style="border: 1px solid #b9b4b4 !important;">
    <div class="card-body">
        <h5 class="card-title">Informatii Produse</h5>
        @foreach ($items as $item)

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Denumire Produs</label>
                        <input type="text" class="form-control" value="{{ $item->name }}" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Cod Produs </label>
                        <input type="text" class="form-control" value="{{ $item->code }}" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Cantitate</label>
                        <input type="text" class="form-control"
                            value="{{ $item->quantity }} - {{ $item->measuringUnitName }}" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Pret</label>
                        <input type="text" class="form-control"
                            value="{{ $item->price }} - {{ $item->currency }}" />
                    </div>
                </div>
            </div>

        @endforeach
    </div>
</div>
<div class="card mt-3" style="border: 1px solid #b9b4b4 !important;" >
    <div class="card-body">
        <h5 class="card-title">Smartbill & Fan Couerier</h5>
        <div class="row">

            <div class="col-md-6">
                <div class="form-group">
                    <label>Taxa Livrare</label>
                    <input type="text" name="transport" onchange="edit_input(this)" class="form-control"
                        value="{{ $order->transport }}" />
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Total</label>
                    <input type="text" name="total" onchange="edit_input(this)" class="form-control"
                        value="{{ $order->total }}" />
                </div>
            </div>


        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Nr Colete</label>
                    <input type="text" name="nr_colete" onchange="edit_input(this)" class="form-control"
                        value="{{ $order->nr_colete }}" />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Greutate</label>
                    <input type="text" name="greutate" onchange="edit_input(this)" class="form-control"
                        value="{{ $order->greutate }}" />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Volum</label>
                    <input type="text" name="volum" onchange="edit_input(this)" class="form-control"
                        value="{{ $order->volum }}" />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Dpd Curier</label>
                    <input type="checkbox" class="form-control" id="dpd_curier" value="1" />
                </div>
            </div>
        </div>
    </div>
</div>
@if ($order->status == 0)
    <div class="row">
        <div class="col-md-12">
            <button class="btn btn-info" style="width:100%" data-id="{{ $order->id }}"
                onclick="genereaza_factura_awb(this)">Confirma Comanda</button>
        </div>
    </div>
@endif
<script>
    function genereaza_factura_awb(elem) {
        $.ajax({

            url: "/genereaza_factura_awb",
            method: "POST",
            data: {
                id: $(elem).attr('data-id'),
                dpd:$("#dpd_curier").val(),
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },

        }).done(function(response) {

            mesaj(response);
        });

    }

    function edit_input(elem) {
        $.ajax({

            url: "/editeaza_date_comanda",
            method: "POST",
            data: {
                order_id: '{{ $order->id }}',
                name: $(elem).attr('name'),
                value: $(elem).val(),
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                return confirm("Sigur doriti sa editati acest element?");
            },
        }).done(function(response) {

            mesaj(response);
        });
    }
</script>
