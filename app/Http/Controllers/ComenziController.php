<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\OrderItem;
use App\Http\Classes\SmartBillCloudRestClient;
use Response;
use Mail;
use DB;
class ComenziController extends Controller
{

    public function show()
    {
        return view("comenzi.show");
    }

    public function load_comenzi()
    {
        $data = array(
            'username' => 'smartbill_aspicine_api',
            'password' => 'aspicinesmartbillapi',

        );
        $response = self::curl_call($data, 'https://shop.aspiscine.ro/urban_comenzi.php');

        return view('comenzi.show-table', compact('response'));
    }

    public function edit_order(Request $request)
    {
        $order = Order::where('id', $request->order_id)->first();
        if ($order) {
            $key = $request->name;
            $order->$key = $request->value;
            $order->save();
            return response("Comanda editata cu success");
        }
        return response("Ceva nu a mers bine");
    }

    public function show_details(Request $request)
    {

       
        $order = Order::where('order_id', $request->id)->first();
        
        if (!$order) {
            $data = array(
                'username' => 'smartbill_aspicine_api',
                'password' => 'aspicinesmartbillapi',
                'order_id' => $request->id,

            );
            $order_details = self::curl_call($data, 'https://shop.aspiscine.ro/urban_info_comanda.php');
            
            $order = new Order();
            $order->order_id = $request->id;
            $order->email = $order_details['details']['billing']['email'];
            $order->cif = $order_details['details']['meta_data'][0]['value'];
            $order->jnum = $order_details['jnum'];
            $order->address_1 = $order_details['details']['billing']['address_1'];
            $order->currency = $order_details['details']['currency'];
            $order->total = $order_details['details']['total'];
            $order->first_name = $order_details['details']['billing']['first_name'];
            $order->last_name = $order_details['details']['billing']['last_name'];
            $order->company = $order_details['details']['billing']['company'];
            $order->address_2 = $order_details['details']['billing']['address_2'];
            $order->city = $order_details['details']['billing']['city'];
            $order->state = $order_details['details']['billing']['state'];
            $order->postcode = $order_details['details']['billing']['postcode'];
            $order->country = $order_details['details']['billing']['country'];
            $order->phone = $order_details['details']['billing']['phone'];
            $order->livrare_first_name = $order_details['details']['shipping']['first_name'];
            $order->livrare_last_name = $order_details['details']['shipping']['last_name'];
            $order->livrare_company = $order_details['details']['shipping']['company'];
            $order->livrare_address_2 = $order_details['details']['shipping']['address_2'];
            $order->livrare_city = $order_details['details']['shipping']['city'];
            $order->livrare_state = $order_details['details']['shipping']['state'];
            $order->livrare_postcode = $order_details['details']['shipping']['postcode'];
            $order->livrare_country = $order_details['details']['shipping']['country'];
            $order->payment_method_title = $order_details['details']['payment_method_title'];
            $order->payment_method = $order_details['details']['payment_method'];
            $order->transport = $order_details['transport'];
            $order->greutate = $order_details['greutate'];
            $order->volum = $order_details['volum'];
            $order->save();

            foreach ($order_details['items'] as $item) {
                $order_item = new OrderItem();
                $order_item->order_id = $order->id;
                $order_item->name = $item['name'];
                $order_item->code = $item['code'];
                $order_item->isDiscount = $item['isDiscount'];
                $order_item->measuringUnitName = $item['measuringUnitName'];
                $order_item->currency = $item['currency'];
                $order_item->quantity = $item['quantity'];
                $order_item->price = $item['price'];
                $order_item->lungime = $item['lungime'];
                $order_item->dimensiune = $item['dimensiune'];
                $order_item->save();
            }
        }
        $items = OrderItem::where('order_id', $order->id)->get();


        return view('comenzi.show-details', compact('order', 'items'));
    }

    private function curl_call($data, $link)
    {
        set_time_limit(6000);
        ini_set('memory_limit', '-1');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($response, true);

        return $response;
    }


    public function genereaza_factura_awb(Request $request)
    {
        $order = Order::where('id', $request->id)->first();
        if ($order && $order->status == 0) {

            if ($order->nr_colete != null) {

                $order_item = OrderItem::where('order_id', $order->id)->get();

                if ($request->has('dpd') && $request->dpd == 1) {

                    if($order->livrare_address_1!=''&& $order->livrare_address_2!='')
                    {
                        $dpd_awb = self::dpd_generare_awb($order, $order_item);
                        
                        $dpd_response=self::dpd_make_call($dpd_awb);
                        
                        if($dpd_response[1]==1)
                        {
                            $order->awb=$dpd_response[0];
                            $order->curier="dpd";
                        }
                        else
                        {
                            return response($dpd_response[0]);
                        }
                        

                    }
                    else
                    {
                        return response('Metoda dpd curier, campurile adress_1 si adress_2 sunt obligatorii!');
                    }
                   
                } else {
                    $csv = self::create_csv($order);
                    $fan_awb = self::fan_generare_awb($order, $order_item);
                    $fan_array = explode(',', $fan_awb);
                    $order->awb = $fan_array[2];
                    $order->curier="fan";
                }


                $data = self::genereaza_factura_smartbill($order, $order_item);
                $factura = self::make_call_smartbill($data);
                $order->factura_nr = $factura['series'] . ":" . $factura['number'];
                $factura_pdf = self::vizualizare_factura($factura['series'], $factura['number']);
                $order->status = 1;
                $order->save();
                self::send_email("factura_" . $factura['series'] . "_" . $factura['number'] . ".pdf", $order->email, $order->awb,$order->curier);
                $data = array(
                    'username' => 'smartbill_aspicine_api',
                    'password' => 'aspicinesmartbillapi',
                    'order_id' => $order->order_id,

                );
                $change_status = self::curl_call($data, 'https://shop.aspiscine.ro/urban_update_order.php');
                return response('Comanda Procesata cu succes');
            }

            return response('Nr de colete este obligatoriu!');
            // $clientId = '7165741 ';
            // $username = 'asgreenfield';
            // $password = 'zapeqepaz';



        }
        return response('Comanda a fost procesata deja');
    }

    private function genereaza_factura_smartbill($order, $order_item)
    {
        $companyVatCode = 'RO28044503';
        $companyInvoiceSeries = 'AS2025';
        if ($order->livrare_address_1 == null) {
            $livrare = $order->address_1;
        } else {
            $livrare = $order->livrare_address_1;
        }

        if($order->cif!='')
        {
            $client_name=$order->company;
        }
        else
        {
            $client_name=$order->livrare_first_name . ' ' . $order->livrare_last_name;
        }
        $invoice = array(
            'companyVatCode' => $companyVatCode,
            'client'         => array(
                'name'             =>  $client_name,
                'vatCode'         => $order->cif,
                'regCom'         => $order->jnum,
                'address'         => $livrare,
                'isTaxPayer'     => false,
                'city'             => $order->livrare_city,
                'county'         => $order->state,
                'country'         => $order->country,
                'email'         => $order->email,
            ),
            'issueDate'     => date('Y-m-d'),
            'seriesName'     => $companyInvoiceSeries,
            'isDraft'         => false,
            //'dueDate' 		=> date('Y-m-d', time() + 14*3600),
            'mentions'         => 'Comanda online numarul: ' . $order->order_id,
            'observations'     => '',
            "useStock" => true,
            'deliveryDate'     => date('Y-m-d', time() + 1 * 3600),
            'products'         => array(),

        );
        foreach ($order_item as $item) {
            $invoice['products'][] = array(
                'name'                 => $item->name,
                'code'                 => $item->code,
                'isDiscount'         => false,
                'measuringUnitName' => $item->measuringUnitName,
                'currency'             => $item->currency,
                'quantity'             => $item->quantity,
                'price'             => $item->price,
                'isTaxIncluded'     => true,
                'taxName'             => "Normala",
                'taxPercentage'     => 21,
                "warehouseName" => 'Retail',
                'isService'         => false,

            );
        }

        $invoice['products'][] = array(
            'name'                 => 'Taxa Livrare',
            'code'                 => 'transport',
            'isDiscount'         => false,
            'measuringUnitName' => 'Buc',
            'currency'             => "RON",
            'quantity'             => 1,
            'price'             => $order->transport,
            'isTaxIncluded'     => true,
            'taxName'             => "Normala",
            'taxPercentage'     => 21,
            'isService'         => true,
        );

        return $invoice;
    }


    private function make_call_smartbill($response)
    {


        $username = 'alenasimona@yahoo.com';
        $token    = '002|251393c9ee8631ac1576b68ba874b3de';
        $sbcClient = new SmartBillCloudRestClient($username, $token);
        $output = $sbcClient->createInvoice($response);

        return $output;
    }


    private function vizualizare_factura($serie, $numar)
    {
        $username = 'alenasimona@yahoo.com';
        $token    = '002|251393c9ee8631ac1576b68ba874b3de';
        $sbcClient = new SmartBillCloudRestClient($username, $token);

        $response = $sbcClient->PDFInvoice('RO28044503', $serie, $numar);

        $file = storage_path('facturi/');

        $name = 'factura_' . $serie . '_' . $numar . '.pdf';
        file_put_contents($file . $name, $response);
        return $name;
    }

    private function send_email($name, $email, $awb,$curier)
    {

        $data =
            [
                'awb' => $awb,
                'curier'=>$curier,
            ];

        Mail::send('emails.factura', $data, function ($m) use ($name, $email) {


            $m->attach(storage_path('facturi/' . $name), [

                'as' => $name,

                'mime' => 'application/pdf',

            ]);
            $m->from('facturi@aspiscine.ro', 'Aspiscine');

            $m->to($email, $email)->subject('Factura pentru comanda dvs!');
        });

        Mail::send('emails.factura', $data, function ($m) use ($name, $email) {


            $m->attach(storage_path('facturi/' . $name), [

                'as' => $name,

                'mime' => 'application/pdf',

            ]);
            $m->from('facturi@aspiscine.ro', 'Aspiscine');

            $m->to('sales@aspiscine.ro', 'Aspiscine Srl')->subject('Copie Factura pentru comanda dvs!');
        });
    }

    public function test_send_email()
    {

        $data =
            [
                'awb' => '111',
            ];
        $name = 'a';
        $email = 'b';

        Mail::send('emails.test', $data, function ($m) use ($name, $email) {



            $m->from('facturi@aspiscine.ro', 'Aspiscine');

            $m->to('office@urban-software.ro', 'Aspiscine Srl')->subject('Copie Factura pentru comanda dvs!');
        });
    }
    private function fan_generare_awb($order, $order_item)
    {
        $clientId = '7165741 ';
        $username = 'asgreenfield';
        $password = 'zapeqepaz';


        $cfile = new \CURLFile("fisier.csv", 'text/csv', 'fisier.csv');;
        $data = ([
            'client_id' => $clientId,
            'username' => $username,
            'user_pass' => $password,
            'fisier' => $cfile,


        ]);
        $ch = curl_init('https://www.selfawb.ro/import_awb_integrat.php');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        curl_close($ch);
        return $response;
    }

    private function dpd_generare_awb($order, $order_item)
    {
        $state_id=$this->get_cities($order->state,$order->city);
        if($order->payment_method=="netopiapayments") 
        {
            $total=0 ;
        }else 
        {
            $total=$order->total;
        }
    
        if($order->cif!='')
        {
            $client_name=$order->company;
        }
        else
        {
            $client_name=$order->livrare_first_name . ' ' . $order->livrare_last_name;
        }
        $array = [

            "userName" => "200927362",
            "password" => "3491818292",
            "service" => array(
                "serviceId" => 2505,
                "autoAdjustPickupDate" => true,
                "additionalServices" => array(
                    "cod" => array(
                        "amount" =>  $total,
                        "currencyCode" => "RON",
                        "includeShippingPrice" => false,
                        "cardPaymentForbidden" => true,
                    ),
                ),
            ),

            "content" => array(
                "parcelsCount" => 1,
                "totalWeight" => $order->greutate,
                "contents" => "produse",
                "package" => "BOX",

            ),

            "payment" => array(
                "courierServicePayer" => "SENDER",
            ),
            "recipient" => array(
                "phone1" => array(
                    "number" => $order->phone,
                ),
                "privatePerson" => "TRUE",
                "clientName" => $client_name,
                "contactName" => $client_name,
                "address" => array(
                    // "stateId"=>
                    "siteId" =>$state_id,
                    "streetType" => "str.",
                    "streetName" => $order->livrare_address_1,
                    "streetNo" => $order->livrare_address_2,
                )
            ),
        ];
        return $array;
    }

    private function dpd_make_call($array)
    {
        $base_url = "https://api.dpd.ro/v1/shipment";
        $ch = curl_init($base_url);
       
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $response=json_decode($response);
        if(property_exists($response,'id'))
        {
            curl_close($ch);
        return array($response->id,1);
        }
        else
        {
            
            return array($response->error->message,0);
        }
        
       
        
    }
    private function create_csv($order)
    {

        if ($order->payment_method == "netopiapayments") {
            $total = 0;
            $type = 'Standard';
        } else {
            $total = $order->total;
            $type = "Cont Colector";
        }
        $list = array(
            array('Tip serviciu', 'Banca', 'IBAN', 'Nr. Plicuri', 'Nr. colete', 'Greutate', 'Plata expeditie', 'Ramburs(bani)', 'Plata rambursului la', 'Valoare declarata', 'Persoana contact expeditor', 'Observatii', 'Continut', 'Nume destinatar', 'Persoana contact', 'Telefon', 'Fax', 'Email', 'Judet', 'Localitate', 'Strada', 'Nr', 'Cod postal', 'Bloc', 'Scara', 'Etaj', 'Apartament', 'Inaltime pachet', 'Latime pachet', 'Lungime pachet', 'Restituire', 'Centru Cost', 'Optiuni', 'Packing', 'Date personale'),
            array($type, '', '', '0', $order->nr_colete, $order->greutate, 'expeditor', $total, 'expeditor', '', '', '', '', $order->first_name . '-' . $order->last_name, '', $order->phone, '', '', $order->state, $order->city, $order->address_1, '', $order->postcode, '', '', '', '', '', '', '', '', '', '', '', '')

        );
        $fp = fopen('fisier.csv', 'w');

        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
    }


    public function  comenzi_curier(Request $request)
    {
        $orders = Order::where('status', '1')->get();
        return view('comenzi.show-comenzi-curier', compact('orders'));
    }


    public function comanda_curier(Request $request)
    {
        if ($request->ora_ridicare != '' && $request->data != '') {
            $orders = Order::where('status', '1')->get();
            if (count($orders) > 0) {
                $nr_colete = 0;
                $greutate = 0;
                $volum = 0;
                foreach ($orders as $order) {
                    $nr_colete += $order->nr_colete;
                    $greutate += $order->greutate;
                    $volum += $order->volum;
                }

                $mesaj = self::fan_comanda_curier($nr_colete, $greutate, $volum, $request->ora_ridicare, $request->data);



                return response([
                    'mesaj' => $mesaj,
                    'status' => 1,
                ]);
            }

            return response([

                'mesaj' => 'Nu poti comanda un curier fara comenzi active',
                'status' => 0,

            ]);
        }

        return response([
            'mesaj' => 'Campurile ora/ data sunt obligatorii',
            'status' => 0
        ]);
    }


    private function fan_comanda_curier($nr_colete, $greutate, $volum, $ora_ridicare, $date)
    {
        $clientId = '7165741 ';
        $username = 'asgreenfield';
        $password = 'zapeqepaz';


        $data = ([
            'client_id' => $clientId,
            'username' => $username,
            'user_pass' => $password,
            'pers_contact' => 'Alena Nicolaescu',
            'tel' => '0723088352',
            'email' => 'sales@aspiscine.ro',
            'nr_colete' => $nr_colete,
            'greutate' => $greutate,
            'inaltime' => 1,
            'lungime' => 1,
            'latime' => $volum,
            'ora_ridicare' => $ora_ridicare,
            'data_cmd' => $date

        ]);

        $ch = curl_init('https://www.selfawb.ro/comanda_curier_integrat.php');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        curl_close($ch);
        return $response;
    }

    public function confirma_comanda_curier()
    {
        $orders = Order::where('status', '1')->get();
        if (count($orders) > 0) {
            foreach ($orders as $order) {

                $order->status = 2;
                $order->save();
            }
            return response('Success');
        }
        return response('Nu exista nicio comanda pentru procesare');
    }




    // public function test_errors()
    // {
    //     $data=([
    //         'client_id' =>'7032158 ',
    //         'username' =>'clienttest',
    //         'user_pass' =>'testing',

    //     ]);

    //     $ch = curl_init('https://www.selfawb.ro/export_lista_erori_imp_awb_integrat.php');
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     $response = curl_exec($ch);

    //     curl_close($ch);
    //     return $response;
    // }
    private function get_cities($judet,$city)
    {
        $judete=file_get_contents('judete.txt');
        $judete=explode(PHP_EOL,$judete);

        foreach($judete as $elem)
        {
            $a=explode(":",$elem);
            $new_jud[$a[0]]=str_replace("\r","",$a[1]);
        }

        if(array_key_exists($judet,$new_jud))
        {
            $cities=DB::table('cities')->where('localitate',$city)->where('judet',$new_jud[$judet])->first();
           if($cities) 
           {
            $a=explode(".",$cities->id);
            return $a[0];

           }
        }
        return false;
    }
}
