

@if($response['status']==1)
<div class="alert alert-success" role="alert">
    {{$response['messages']}}
  </div>
  <input type="text" id="search" placeholder="Tasteaza pentru a cauta" class="form-control">
  <table class="table"  id="table">
    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Nume Produs</th>
        <th scope="col">Sku</th>
        <th scope="col">Pret</th>
        
      </tr>
    </thead>
    <tbody>
        @php $nr=1 @endphp

  @foreach($response['products'] as $product)
  
  <tr>
    <th scope="row">{{$nr}}</th>
    <td>{{$product['name']}}</td>
    <td>{{$product['sku']}}</td>
    <td contenteditable  data-sku="{{$product['sku']}}" onfocusout="confirma(this)">{{$product['price']}}</td>
  </tr>
  @php $nr++ @endphp
  @endforeach
</tbody>
</table>

<script>
function confirma(elem)
{
    jQuery("#modal_messages").text('Sunteti sigur ca doriti sa schimbati pretul produsului:' + jQuery(elem).attr('data-sku')+ " in " +jQuery(elem).text() + " slei");
    jQuery("#button_confirma").attr('data-sku',jQuery(elem).attr('data-sku'));
    jQuery("#button_confirma").attr('data-price',jQuery(elem).text());
    jQuery("#modal_confirma").modal('show');
}
var $rows = $('#table tr');
$('#search').keyup(function() {
    var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();
    
    $rows.show().filter(function() {
        var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
        return !~text.indexOf(val);
    }).hide();
});
jQuery("#button_confirma").click(function(){

    jQuery.ajax({

        url: "/actualizare_produs",
        method: "POST",
        data: {
            cod_produs: jQuery("#button_confirma").attr('data-sku'),
            pret: jQuery("#button_confirma").attr('data-price'),
            
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

    }).done(function(response) {
        
        mesaj(response.messages);
        jQuery("#modal_confirma").modal('hide');
          

     });

});
</script>

@else

<div class="alert alert-danger" role="alert">
    {{$response['messages']}}
  </div>
@endif

