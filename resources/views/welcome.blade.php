@extends('layouts.app')
@section('content')

{{-- <form action="/upload_file" method="post" class="d-flex justify-content-center mt-5 " enctype="multipart/form-data">
    @csrf
    <div style="width: 300px;" class="bg-light shadow-sm">
        @if ($errors->has('uploadFileError'))
            <div class="alert {{$errors->first('class')}} ">{{$errors->first('uploadFileError')}}</div> @endif

        <input class="form-control " type="file" id="excel_file" accept=".xlsx" name="excel_file" required>

        <div class="text-center">

            <label class="form-label mt-2" id="excel_file_label" for="excel_file">

                <i class="fas fa-upload"></i>&nbsp;Alege un xl...

            </label>
        </div>

        <div class="text-center mt-2 mb-2">

            <button class="btn btn-primary" type="submit">Upload</button>

        </div>

    </div>

</form> --}}

@if($status ?? '' && $messages ?? '')
<div id="aa">
    <div style="color:white;text-align:center"class=" alert @if($status==0) alert-danger @else alert-success  @endif ">{{$messages}}</div>
    
</div>

@if($status == 1)
<div class="container-fluid">
<div class="row">
    <div class="col-md-6"></div>
    <div class="col-md-6"><button class="btn btn-success" type="button" onclick="start_upload()">Start Actualizare</button></div>
</div>
@php $nr=0; @endphp
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="table-responsive">
                <table class="table" >
                    <thead>
                      <tr>
                        <th scope="col">#</th>
                        <th scope="col">Cod Produs</th>
                        <th scope="col">Pret</th>
                        <th scope="col">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($data as $elem)
                      @if($nr >0)
                      <tr class="for_upload">
                        <th  scope="row">{{$nr}}</th>
                        <td class="cod">{{$elem[0]}}</td>
                        <td class="pret" >{{$elem[1]}}</td>
                        <td class="actiuni" style="width:250px"></td>
                      </tr>
                
                      @endif
                      @php $nr++ @endphp
                      @endforeach
                   
                    </tbody>
                  </table>

            </div>
        </div>
    </div>
</div>


</div>
@endif
@endif

<script>
//     var fileInput = document.getElementById('excel_file');

// fileInput.onchange = function(e){
//     var fullPath = fileInput.value;
//     if (fullPath) {
//         var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
//         var filename = fullPath.substring(startIndex);
//         if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
//             filename = filename.substring(1);
//         }
//         $('#xls_file_label').text(filename);
//     }
// };

function start_upload()
{
   
    var cod_produs=$(".for_upload").first().children('td.cod').text();
    if(cod_produs!='')
    {
        var pret=$(".for_upload").first().children('td.pret').text();
       $.ajax({

            url: "/actualizare_produs",
            method: "POST",
            data: {
                cod_produs: cod_produs,
                pret: pret,
                
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },

            }).done(function(response) {

            if(response.status==0)
            {   
               $(".for_upload").first().children('td.actiuni').text(response.message).addClass('bg_red');
            }
            else
            {
                $(".for_upload").first().children('td.actiuni').text(response.message).addClass('bg_green');
            }
            $(".for_upload").first().removeClass('for_upload');
            window.setTimeout(() => {
                start_upload();
            }, 2000);
            
            });
    }
    else
    {
        $("#aa").append('<div style="color:white;text-align:center"class=" alert alert-success  ">Upload Finalizat cu Succes</div>');
    }
}


</script>


@endsection