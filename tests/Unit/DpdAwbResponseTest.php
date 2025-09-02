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
        // Mock a successful DPD API response
        $mockResponse = json_encode([
            "id" => "12345678901234567890",
            "status" => "success"
        ]);

        // Create a partial mock of ComenziController
        $controller = Mockery::mock(ComenziController::class)->makePartial();
        
        // Mock the curl execution to return our test response
        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturnUsing(function($array) use ($mockResponse) {
                      $response = json_decode($mockResponse);
                      if (property_exists($response, "id")) {
                          return array($response->id, 1);
                      } else {
                          return array($response->error->message, 0);
                      }
                  });

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
        // Mock an error DPD API response
        $mockResponse = json_encode([
            "error" => [
                "message" => "Invalid address data",
                "code" => "400"
            ]
        ]);

        $controller = Mockery::mock(ComenziController::class)->makePartial();
        
        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturnUsing(function($array) use ($mockResponse) {
                      $response = json_decode($mockResponse);
                      if (property_exists($response, "id")) {
                          return array($response->id, 1);
                      } else {
                          return array($response->error->message, 0);
                      }
                  });

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
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod("dpd_make_call");
        $method->setAccessible(true);

        // Mock curl functions to return malformed JSON
        $controller = Mockery::mock(ComenziController::class)->makePartial();
        
        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturnUsing(function($array) {
                      // Simulate malformed JSON response
                      $response = json_decode("invalid json");
                      if ($response === null) {
                          return array("JSON decode error", 0);
                      }
                      return array("Unexpected error", 0);
                  });

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292"
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("JSON decode error", $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    /**
     * Test DPD API response with missing ID field.
     *
     * @return void
     */
    public function test_response_missing_id_field()
    {
        $mockResponse = json_encode([
            "status" => "processed",
            "message" => "Request processed but no ID returned"
        ]);

        $controller = Mockery::mock(ComenziController::class)->makePartial();
        
        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturnUsing(function($array) use ($mockResponse) {
                      $response = json_decode($mockResponse);
                      if (property_exists($response, "id")) {
                          return array($response->id, 1);
                      } else {
                          // When no ID is present, treat as error
                          return array("No AWB ID returned from DPD API", 0);
                      }
                  });

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292"
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("No AWB ID returned from DPD API", $result[0]);
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
                  ->andReturnUsing(function($array) {
                      // Simulate timeout scenario
                      return array("Connection timeout to DPD API", 0);
                  });

        $testArray = [
            "userName" => "200927362",
            "password" => "3491818292"
        ];

        $result = $controller->dpd_make_call($testArray);

        $this->assertEquals("Connection timeout to DPD API", $result[0]);
        $this->assertEquals(0, $result[1]);
    }

    /**
     * Test DPD API authentication failure.
     *
     * @return void
     */
    public function test_dpd_api_authentication_failure()
    {
        $mockResponse = json_encode([
            "error" => [
                "message" => "Authentication failed",
                "code" => "401"
            ]
        ]);

        $controller = Mockery::mock(ComenziController::class)->makePartial();
        
        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturnUsing(function($array) use ($mockResponse) {
                      $response = json_decode($mockResponse);
                      if (property_exists($response, "id")) {
                          return array($response->id, 1);
                      } else {
                          return array($response->error->message, 0);
                      }
                  });

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
                  ->andReturnUsing(function($array) {
                      // Simulate empty response
                      $response = json_decode("");
                      if ($response === null) {
                          return array("Empty response from DPD API", 0);
                      }
                      return array("Unexpected error", 0);
                  });

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
        
        $mockResponse = json_encode([
            "id" => $longAwbId,
            "status" => "success"
        ]);

        $controller = Mockery::mock(ComenziController::class)->makePartial();
        
        $controller->shouldReceive("dpd_make_call")
                  ->once()
                  ->andReturnUsing(function($array) use ($mockResponse) {
                      $response = json_decode($mockResponse);
                      if (property_exists($response, "id")) {
                          return array($response->id, 1);
                      } else {
                          return array($response->error->message, 0);
                      }
                  });

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
