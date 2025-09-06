<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\ComenziController;
use App\Order;
use App\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;

class DpdAddressTransformationTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ComenziController();
    }

    /**
     * Test DPD AWB generation with complete address data.
     *
     * @return void
     */
    public function test_dpd_awb_generation_with_complete_address()
    {
        // Create a mock order with complete address data
        $order = new Order();
        $order->id = 1;
        $order->state = "B";
        $order->city = "Bucuresti";
        $order->livrare_address_1 = "Strada Victoriei";
        $order->livrare_address_2 = "123";
        $order->livrare_first_name = "Ion";
        $order->livrare_last_name = "Popescu";
        $order->phone = "0721234567";
        $order->payment_method = "cash";
        $order->total = 100.50;
        $order->greutate = 2.5;
        $order->cif = "";
        $order->company = "";

        $orderItems = collect([]);

        // Mock the get_cities method to return a valid city ID
        $controller = Mockery::mock(ComenziController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('get_cities')
                  ->with('B', 'Bucuresti')
                  ->andReturn('1');

        $result = $controller->dpd_generare_awb($order, $orderItems);

        // Assert the result is not false (city mapping succeeded)
        $this->assertNotFalse($result);

        // Assert the structure is correct
        $this->assertArrayHasKey("userName", $result);
        $this->assertArrayHasKey("password", $result);
        $this->assertArrayHasKey("recipient", $result);
        $this->assertArrayHasKey("address", $result["recipient"]);

        // Assert address fields are properly set
        $this->assertEquals("Strada Victoriei", $result["recipient"]["address"]["streetName"]);
        $this->assertEquals("123", $result["recipient"]["address"]["streetNo"]);
        $this->assertEquals("Ion Popescu", $result["recipient"]["clientName"]);
        $this->assertEquals("0721234567", $result["recipient"]["phone1"]["number"]);
    }

    /**
     * Test DPD AWB generation with missing address_1.
     *
     * @return void
     */
    public function test_dpd_awb_generation_with_missing_address_1()
    {
        $order = new Order();
        $order->id = 1;
        $order->state = "B";
        $order->city = "Bucuresti";
        $order->livrare_address_1 = ""; // Missing street name
        $order->livrare_address_2 = "123";
        $order->livrare_first_name = "Ion";
        $order->livrare_last_name = "Popescu";
        $order->phone = "0721234567";
        $order->payment_method = "cash";
        $order->total = 100.50;
        $order->greutate = 2.5;
        $order->cif = "";
        $order->company = "";

        $orderItems = collect([]);

        // Mock the get_cities method to return a valid city ID
        $controller = Mockery::mock(ComenziController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('get_cities')
                  ->with('B', 'Bucuresti')
                  ->andReturn('1');

        $result = $controller->dpd_generare_awb($order, $orderItems);

        // Assert that empty street name is passed to DPD
        $this->assertEquals("", $result["recipient"]["address"]["streetName"]);
        $this->assertEquals("123", $result["recipient"]["address"]["streetNo"]);
    }

    /**
     * Test DPD AWB generation with missing address_2.
     *
     * @return void
     */
    public function test_dpd_awb_generation_with_missing_address_2()
    {
        $order = new Order();
        $order->id = 1;
        $order->state = "B";
        $order->city = "Bucuresti";
        $order->livrare_address_1 = "Strada Victoriei";
        $order->livrare_address_2 = ""; // Missing street number
        $order->livrare_first_name = "Ion";
        $order->livrare_last_name = "Popescu";
        $order->phone = "0721234567";
        $order->payment_method = "cash";
        $order->total = 100.50;
        $order->greutate = 2.5;
        $order->cif = "";
        $order->company = "";

        $orderItems = collect([]);

        // Mock the get_cities method to return a valid city ID
        $controller = Mockery::mock(ComenziController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('get_cities')
                  ->with('B', 'Bucuresti')
                  ->andReturn('1');

        $result = $controller->dpd_generare_awb($order, $orderItems);

        // Assert that empty street number is passed to DPD
        $this->assertEquals("Strada Victoriei", $result["recipient"]["address"]["streetName"]);
        $this->assertEquals("", $result["recipient"]["address"]["streetNo"]);
    }

    /**
     * Test address validation before DPD call.
     *
     * @return void
     */
    public function test_address_validation_before_dpd_call()
    {
        $order = Order::factory()->create([
            "livrare_address_1" => "",
            "livrare_address_2" => "123",
            "nr_colete" => 1,
            "status" => 0
        ]);

        $request = new Request();
        $request->merge([
            "id" => $order->id,
            "dpd" => 1
        ]);

        $response = $this->controller->genereaza_factura_awb($request);

        // Should return error message for missing address_1
        $this->assertEquals("Eroare validare adresa DPD: Strada (address_1) este obligatorie", $response->getContent());
    }

    /**
     * Test city mapping functionality.
     *
     * @return void
     */
    public function test_city_mapping_functionality()
    {
        // Create a mock cities table entry
        \DB::table("cities")->insert([
            "id" => "1.123",
            "localitate" => "Bucuresti",
            "judet" => "Bucuresti"
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod("get_cities");
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, "B", "Bucuresti");

        $this->assertEquals("1", $result);
    }

    /**
     * Test city mapping with invalid data.
     *
     * @return void
     */
    public function test_city_mapping_with_invalid_data()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod("get_cities");
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, "INVALID", "InvalidCity");

        $this->assertFalse($result);
    }

    /**
     * Test DPD AWB generation with company data.
     *
     * @return void
     */
    public function test_dpd_awb_generation_with_company_data()
    {
        $order = new Order();
        $order->id = 1;
        $order->state = "B";
        $order->city = "Bucuresti";
        $order->livrare_address_1 = "Strada Victoriei";
        $order->livrare_address_2 = "123";
        $order->livrare_first_name = "Ion";
        $order->livrare_last_name = "Popescu";
        $order->phone = "0721234567";
        $order->payment_method = "cash";
        $order->total = 100.50;
        $order->greutate = 2.5;
        $order->cif = "RO12345678";
        $order->company = "Test Company SRL";

        $orderItems = collect([]);

        // Mock the get_cities method to return a valid city ID
        $controller = Mockery::mock(ComenziController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('get_cities')
                  ->with('B', 'Bucuresti')
                  ->andReturn('1');

        $result = $controller->dpd_generare_awb($order, $orderItems);

        // When CIF is present, company name should be used
        $this->assertEquals("Test Company SRL", $result["recipient"]["clientName"]);
        $this->assertEquals("Test Company SRL", $result["recipient"]["contactName"]);
    }

    /**
     * Test DPD AWB generation with netopia payment method.
     *
     * @return void
     */
    public function test_dpd_awb_generation_with_netopia_payment()
    {
        $order = new Order();
        $order->id = 1;
        $order->state = "B";
        $order->city = "Bucuresti";
        $order->livrare_address_1 = "Strada Victoriei";
        $order->livrare_address_2 = "123";
        $order->livrare_first_name = "Ion";
        $order->livrare_last_name = "Popescu";
        $order->phone = "0721234567";
        $order->payment_method = "netopiapayments";
        $order->total = 100.50;
        $order->greutate = 2.5;
        $order->cif = "";
        $order->company = "";

        $orderItems = collect([]);

        // Mock the get_cities method to return a valid city ID
        $controller = Mockery::mock(ComenziController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('get_cities')
                  ->with('B', 'Bucuresti')
                  ->andReturn('1');

        $result = $controller->dpd_generare_awb($order, $orderItems);

        // For netopia payments, COD amount should be 0
        $this->assertEquals(0, $result["service"]["additionalServices"]["cod"]["amount"]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
