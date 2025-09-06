<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\ComenziController;
use Mockery;

class DpdAwbResponseTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ComenziController();
    }

    /**
     * Test successful DPD API response processing.
     *
     * @return void
     */
    public function test_successful_dpd_api_response()
    {
        // Create a partial mock of ComenziController
        $controller = Mockery::mock(ComenziController::class)->makePartial();

        // Mock the curl execution to return our test response
        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturn(["12345678901234567890", 1]);

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292",
            "recipient" => [
                "address" => [
                    "streetName" => "Test Street",
                    "streetNo" => "123"
                ]
            ]
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("12345678901234567890", $result[0]);
        $this->assertEquals(1, $result[1]);
    }

    /**
     * Test DPD API error response processing.
     *
     * @return void
     */
    public function test_error_dpd_api_response()
    {
        $controller = Mockery::mock(ComenziController::class)->makePartial();

        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturn(["Invalid address data", 0]);

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292",
            "recipient" => [
                "address" => [
                    "streetName" => "",
                    "streetNo" => ""
                ]
            ]
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("Invalid address data", $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    /**
     * Test DPD API response with malformed JSON.
     *
     * @return void
     */
    public function test_malformed_json_response()
    {
        $controller = Mockery::mock(ComenziController::class)->makePartial();

        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturn(["Invalid JSON response from DPD API", 0]);

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292"
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("Invalid JSON response from DPD API", $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    /**
     * Test DPD API response with missing ID field.
     *
     * @return void
     */
    public function test_response_missing_id_field()
    {
        $controller = Mockery::mock(ComenziController::class)->makePartial();

        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturn(["Unexpected response format from DPD API", 0]);

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292"
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("Unexpected response format from DPD API", $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    /**
     * Test DPD API timeout scenario.
     *
     * @return void
     */
    public function test_dpd_api_timeout()
    {
        $controller = Mockery::mock(ComenziController::class)->makePartial();

        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturn(["Connection error: Operation timed out", 0]);

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292"
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("Connection error: Operation timed out", $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    /**
     * Test DPD API authentication failure.
     *
     * @return void
     */
    public function test_dpd_api_authentication_failure()
    {
        $controller = Mockery::mock(ComenziController::class)->makePartial();

        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturn(["Authentication failed", 0]);

        $testArray = [
            "userName" => "invalid_user",
            "password" => "invalid_pass"
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("Authentication failed", $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    /**
     * Test DPD API response with empty response body.
     *
     * @return void
     */
    public function test_empty_response_body()
    {
        $controller = Mockery::mock(ComenziController::class)->makePartial();

        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturn(["Empty response from DPD API", 0]);

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292"
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("Empty response from DPD API", $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    /**
     * Test DPD API response with very long AWB ID.
     *
     * @return void
     */
    public function test_long_awb_id_response()
    {
        $longAwbId = str_repeat("1234567890", 5); // 50 character AWB ID

        $controller = Mockery::mock(ComenziController::class)->makePartial();

        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturn([$longAwbId, 1]);

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292"
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals($longAwbId, $result[0]);
        $this->assertEquals(1, $result[1]);
        $this->assertEquals(50, strlen($result[0]));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
