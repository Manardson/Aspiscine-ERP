<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\OrderItem;
use Storage;
use Response;

class OrderController extends Controller
{

    public function show()
    {
        return view('orders.show');
    }

    public function load_all_orders(Request $request)
    {
        if ($request->value == "all") {
            $orders = Order::orderby('created_at', 'DESC')->limit(10)->get();
        } else {
            $orders = Order::where('first_name', 'like', '%' . $request->value . '%')
                ->orwhere('last_name', 'like', '%' . $request->value . '%')
                ->orwhere('email', 'like', '%' . $request->value . '%')
                ->orwhere('awb', 'like', '%' . $request->value . '%')
                ->orwhere('factura_nr', 'like', '%' . $request->value . '%')
                ->orwhere('company', 'like', '%' . $request->value . '%')
                ->orwhere('phone', 'like', '%' . $request->value . '%')
                ->get();
        }

        return view('orders.show-table', compact('orders'));
    }


    public function vezi_factura($order_id)
    {
        $order = Order::where('order_id', $order_id)->first();
        if ($order) {
            $array = explode(":", $order->factura_nr);
            return response()->file(storage_path('facturi/') . "factura_" . $array[0] . "_" . $array[1] . ".pdf");
        }
        return response('Eroare');
    }


    public function vezi_awb($order_id)
    {
        $order = Order::where('order_id', $order_id)->first();
        if ($order) {

            if ($order->curier == "fan") {


                $data = ([
                    'client_id' => '7165741 ',
                    'username' => 'asgreenfield',
                    'user_pass' => 'zapeqepaz',
                    'nr' => $order->awb,
                    'page' => 'A4',
                    'language' => 'ro',

                ]);

                $ch = curl_init('https://www.selfawb.ro/view_awb_integrat_pdf.php');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);

                curl_close($ch);
                return Response::make($response, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="awb.pdf"'
                ]);
            } else {
                $array = [

                    "userName" => "200927362",
                    "password" => "3491818292",
                    "paperSize" => "A4",
                    "format"=>"pdf",
                    "parcels" => array(
                        array(
                        "parcel" => array(
                            "id" => $order->awb,
                        )
                        )
                    )
                ];

               
                
                $base_url = "https://api.dpd.ro/v1/print";
                $ch = curl_init($base_url);

                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array));
                
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
               
                curl_close($ch);
                return Response::make($response, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="awb.pdf"'
                ]);
            }
        }
        return response('Eroare');
    }
}
