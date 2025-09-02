<table class="table"  id="table">
    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Nume & Prenume Client</th>
        <th scope="col">Telefon</th>
        <th scope="col">Email</th>
        <th scope="col">Data Creare</th>
        <th scope="col">Actiuni</th>
        
      </tr>
    </thead>
    <tbody>
        @php $nr=1 @endphp
  
  @if(isset($response['orders']) && count($response['orders'] )>0)
  @foreach($response['orders'] as $order)

  <tr>
    <th scope="row">{{$nr}}</th>
    <td>{{$order['details']['billing']['first_name']}} - {{$order['details']['billing']['last_name']}}</td>
    <td>{{$order['details']['billing']['phone']}}</td>
    <td>{{$order['details']['billing']['email']}} </td>
    <td>{{date('Y-m-d',strtotime($order['details']['date_created']['date']))}}</td>
    <td><i   onclick="view_details({{$order['details']['id']}})"class="far fa-eye"></i></td>
  </tr>
  @php $nr++ @endphp
  @endforeach

  @else
 <tr>
<th colspan="6" class="text-center"><h3> Nu sunt comenzi noi</h3></th>
 </tr>
  @endif
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