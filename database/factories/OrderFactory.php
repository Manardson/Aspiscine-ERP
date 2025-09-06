<?php

namespace Database\Factories;

use App\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'state' => 'B',
            'city' => 'Bucuresti',
            'livrare_address_1' => $this->faker->streetName,
            'livrare_address_2' => $this->faker->buildingNumber,
            'livrare_first_name' => $this->faker->firstName,
            'livrare_last_name' => $this->faker->lastName,
            'phone' => $this->faker->phoneNumber,
            'payment_method' => 'cash',
            'total' => $this->faker->randomFloat(2, 10, 500),
            'greutate' => $this->faker->randomFloat(2, 0.1, 10),
            'cif' => '',
            'company' => '',
            'nr_colete' => 1,
            'status' => 0,
        ];
    }
}
