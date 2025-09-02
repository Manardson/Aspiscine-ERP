<table class="table"  id="table">
    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Nume & Prenume Client</th>
        <th scope="col">Telefon</th>
        <th scope="col">Email</th>
        <th scope="col">AWB</th>
        <th scope="col">Actiuni</th>
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
    <td><i   onclick="view_details({{$order->order_id}})"class="far fa-eye"></i></td>
  </tr>
  @php $nr++ @endphp
  @endforeach
</tbody>
</table>

<script>

function view_details(elem)
{
    $.ajax({

        url: "/details_comanda",
        method: "POST",
        data:{
            id:elem,
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

    }).done(function(response) {
      $("#modal_results").html(response);
      $("#modal_view").modal('show');
   });
}
</script>