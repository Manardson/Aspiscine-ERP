<table class="table"  id="table">
    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Nume & Prenume Client</th>
        <th scope="col">Telefon</th>
        <th scope="col">Email</th>
        <th scope="col">AWB</th>
        
      </tr>
    </thead>
    <tbody>
        @php $nr=1 @endphp
  
  @foreach($orders as $order)

  <tr>
    <th scope="row">{{$nr}}</th>
    <td>{{$order->first_name}} - {{$order->last_name}}</td>
    <td>{{$order->phone}}</td>
    <td>{{$order->email}} </td>
    <td>{{$order->awb}}</td>
  
  </tr>
  @php $nr++ @endphp
  @endforeach
</tbody>
</table>
<form id="detalii_comanda_curier" >
<div class="row">
    
    <div class="col-md-6">
        <div class="form-group">
            <label>Data ridicare colet</label>
                <input type="date" class="form-control" name="data" value="{{date('Y-m-d')}}" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Ora de ridicare</label>
                <input type="text"  class="form-control" name="ora_ridicare" value="" />
        </div>
    </div>
   


</div>
<div class="row">
    <div class="col-md-12">
        <button class="btn btn-info"  type="button" style="width:100%" id="button_submit_comanda">Comanda Curier</button>
    </div>
</div>
</form>

<script>
$("#button_submit_comanda").click(function(){
    var data=$("#detalii_comanda_curier").serialize();
    $.ajax({

        url:"/comanda_curier",
        method:"POST",
        data:data,
        headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },

    }).done(function(response) {
        if(response.status==1)
        {
            $("#modal_curier").modal('hide');
        }
            
            mesaj(response.mesaj);
    });


});
</script>