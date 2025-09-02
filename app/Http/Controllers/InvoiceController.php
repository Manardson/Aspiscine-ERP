<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Invoice;
use App\Http\Classes\SmartBillCloudRestClient;
use Response;
use Mail;
class InvoiceController extends Controller
{
    public function smartbill_generare_factura($order_id)
    {

        $invoice = Invoice::where('id', $order_id)->first();
        if (!$invoice) {
            $invoice = new Invoice();
            $invoice->order_id = $order_id;

            $invoice->save();
        }
    }

    public function generate_invoices()
    {
        if (isset($_GET['username']) && isset($_GET['password']) && $_GET['username'] == "smartbill_aspicine_api" && $_GET['password'] = "aspicinesmartbillapi") {
            $invoices = Invoice::where('status', 0)->get();

            if (count($invoices) > 0) {
                foreach ($invoices as $elem) {
                    $response = self::wordpress_order_info($elem->order_id);
                    if ($response != false && $response['output']['errorText'] == '') {
                        $elem->status = 1;
                        $elem->factura_nr = $response['output']['series'] . ":" . $response['output']['number'];
                        $elem->email=$response['email'];
                        $elem->save();
                        $file_name=self::vizualizare_factura($response['output']['series'],$response['output']['number']);
                        self::send_email($file_name,$response['email']);
                    }
                }
                return response('Success');
            }
            return response('Nimic de efectuat');
            
        }
        return response('Nu ai voie aici');
    }
    private function wordpress_order_info($order_id)
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://shop.aspiscine.ro/urban-api.php?order_id=' . $order_id . '&username=smartbill_aspicine_api&password=aspicinesmartbillapi');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $response = json_decode($response, true);
       
        $username = 'alenasimona@yahoo.com';
        $token    = '002|251393c9ee8631ac1576b68ba874b3de';
        $sbcClient = new SmartBillCloudRestClient($username, $token);
        $output = $sbcClient->createInvoice($response);
       
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
            $rezultat['output']=$output;
            $rezultat['email']=$response['client']['email'];
            curl_close($ch);
            return $rezultat;
        } else return false;
    }

    public function importa_produse()
    {
        set_time_limit(1000);
        $username = 'alenasimona@yahoo.com';
        $token    = '002|251393c9ee8631ac1576b68ba874b3de';
        $sbcClient = new SmartBillCloudRestClient($username, $token);
        $data['cif'] = 'RO28044503';
        $data['warehouseName'] = 'Retail';
        $data['date'] = date('Y-m-d');

        $output = $sbcClient->productsStock($data);
        $nr = 0;
        dd($output);
        foreach ($output[0]['products'] as $elem) {
            if ($nr > 576) {


                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://aspiscine.ro/urban-add.php?username=smartbill_aspicine_api&password=aspicinesmartbillapi&sku=' . $elem['productCode'] . '&title=' . str_replace(" ", '%20', $elem['productName']));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                echo $response;

                echo $nr;
                sleep(2);
            }
            $nr++;
        }
    }

    public function update_produse()
    {
        if (isset($_GET['username']) && isset($_GET['password']) && $_GET['username'] == "smartbill_aspicine_api" && $_GET['password'] = "aspicinesmartbillapi" && isset($_GET['to']) && isset($_GET['from'])) {

            set_time_limit(6000);

            
            $username = 'alenasimona@yahoo.com';
            $token    = '002|251393c9ee8631ac1576b68ba874b3de';
            $sbcClient = new SmartBillCloudRestClient($username, $token);
            $data['cif'] = 'RO28044503';
            $data['warehouseName'] = 'Retail';
            $data['date'] = date('Y-m-d');
            $output = $sbcClient->productsStock($data);
              
            $data = array(
                'username' => 'smartbill_aspicine_api',
                'password' => 'aspicinesmartbillapi',
                'type' => 'stock',
                'cod_produs' => '',
                'value' => ''
            );
            $nr = 0;
            $array=$output[0]['products'];
            
            if($_GET['from']>=count($array))
            {
                $from=count($array)-1;
            }
            else
            {
                $from=$_GET['from'];
            }
            if($_GET['to']>=count($array))
            {
                $to=count($array)-1;
            }
            else
            {
                $to=$_GET['to'];
            }
            for($i=$to;$i<=$from;$i++)
            {
              
                $data['cod_produs'] = $array[$i]['productCode'];
                $data['value'] = $array[$i]['quantity'];

                $response = self::curl_call($data);
                echo $response['messages'] . '<br>';
                
                usleep(500);
                if($response['status']==1)
                    $nr++;
            }
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=iso-8859-1';
            mail("webmaster@aspiscine.ro", 'Update Aspiscine', 'Update executat cu success.S-a actualizat' . $nr . ' produse. De la  indicele '. $_GET['to'] .' pana la indicele' . $_GET['from']);
            return response('Success');
        }

        return response('Nu ai voie aici');
    }

    private function curl_call($data)
    {
        set_time_limit(6000);
        ini_set('memory_limit', '-1');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://shop.aspiscine.ro/urban-update.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        
        curl_close($ch);
        $response = json_decode($response, true);

        return $response;
    }


    
}
