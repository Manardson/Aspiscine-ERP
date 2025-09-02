<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Auth::routes();

// Route::get("/invoice_generate/{order_id}",[App\Http\Controllers\InvoiceController::class,'smartbill_generare_factura']);
// Route::get("/obtine_informatii_wordpress/{order_id}",[App\Http\Controllers\InvoiceController::class,'wordpress_order_info']);
Route::get("/import_produse",[App\Http\Controllers\InvoiceController::class,'importa_produse']);
Route::get("/update_produse",[App\Http\Controllers\InvoiceController::class,'update_produse']);
// Route::get("/generare_facturi",[App\Http\Controllers\InvoiceController::class,'generate_invoices']);
Route::get("/",function(){
    return redirect('/home');
    
});


Route::middleware(['auth'])->group(function () {
    //Route::get('/home', [App\Http\Controllers\HomeController::class,'home']);
    Route::get('/logout', [App\Http\Controllers\Auth\LoginController::class,'logout']);
    Route::post("/upload_file",[App\Http\Controllers\ExcelController::class,'read_file']);
    Route::post("/actualizare_produs",[App\Http\Controllers\ExcelController::class,'update_produs']);
    Route::get('/home',[App\Http\Controllers\ProduseController::class,'show']);
    Route::post('/load_products',[App\Http\Controllers\ProduseController::class,'incarca']);
    Route::get('/comenzi',[App\Http\Controllers\ComenziController::class,'show']);
    Route::post("/load_comenzi",[App\Http\Controllers\ComenziController::class,'load_comenzi']);
    Route::post('/details_comanda',[App\Http\Controllers\ComenziController::class,'show_details']);
    Route::post("/genereaza_factura_awb",[App\Http\Controllers\ComenziController::class,'genereaza_factura_awb']);
    Route::post("/editeaza_date_comanda",[App\Http\Controllers\ComenziController::class,'edit_order']);

    Route::post("/obtine_comenzi_curier",[App\Http\Controllers\ComenziController::class,'comenzi_curier']);
    Route::post("/comanda_curier",[App\Http\Controllers\ComenziController::class,'comanda_curier']);
    Route::post("/confirma_comanda_curier",[App\Http\Controllers\ComenziController::class,'confirma_comanda_curier']);


    Route::get("/all_orders",[App\Http\Controllers\OrderController::class,'show']);
    Route::post("/load_all_orders",[App\Http\Controllers\OrderController::class,'load_all_orders']);

    Route::get('/vezi_awb/{order_id}',[App\Http\Controllers\OrderController::class,'vezi_awb']);
    Route::get('/vezi_factura/{order_id}',[App\Http\Controllers\OrderController::class,'vezi_factura']);
});
Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
