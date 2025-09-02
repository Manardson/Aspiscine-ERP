<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProduseController extends Controller
{
    public function show()
    {
        return view('produse.show');
    }

    public function incarca(Request $request)
    {
        $data = array(
            'username' => 'smartbill_aspicine_api',
            'password' => 'aspicinesmartbillapi',
            'sort_by' =>$request->sort_by,
            'sort_direction' =>$request->sort_direction,
            
        );
       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://shop.aspiscine.ro/urban-products.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);

        $response = curl_exec($ch);
        
        $response=json_decode($response,true);
        curl_close($ch);
        return view('produse.tabel',compact('response'));
    }
}
