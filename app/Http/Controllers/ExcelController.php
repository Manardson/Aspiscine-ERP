<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Excel;
use App\Imports\ExcelImport;
use Validator;
class ExcelController extends Controller
{
    public function read_file(Request $request)
    {
        if ($request->isMethod('post')) { 

            $messages='Something it was wrong!Please try again.';
            $status=0;
            $validator = Validator::make($request->all(), [
                'excel_file' => 'required|file|mimes:xlsx,application/excel',
            ]);

            if($validator->fails()){ // get error if something is wrong
                return view('welcome',compact('messages','status'));
            }
            $data = Excel::toArray(new ExcelImport(), $request->file('excel_file'));
            $data = $data[0];

            $messages='Upload Success';
            $status=1;
            return view('welcome',compact("data",'messages','status'));

        
                
        }
        return view('welcome');
        

        
    }

    public function update_produs(Request $request)
    {
       
        $data = array(
            'username' => 'smartbill_aspicine_api',
            'password' => 'aspicinesmartbillapi',
            'type' => 'pret',
            'cod_produs' => $request->cod_produs,
            'value' =>$request->pret
        );
       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://shop.aspiscine.ro/urban-update.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);

        $response = curl_exec($ch);
        $response=json_decode($response,true);
        curl_close($ch);
        return response($response);
    }
}
