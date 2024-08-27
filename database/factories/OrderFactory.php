<?php

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'processing', 'completed', 'declined']);

        return [
            'customer_id' => Customer::all()->random()->id,
            'status' => $status,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'completed_at' => $status === 'completed' ? $this->faker->dateTimeBetween('-1 year', 'now') : null,
        ];
    }

    public function configure(): OrderFactory|Factory
    {
        return $this->afterCreating(function (Order $order) {
            CartItem::factory()->count(3)->create(['order_id' => $order->id]);
        });
    }
}
