<?php

namespace Database\Factories;

use App\Models\DeviceRetrievalReport2;
use App\Models\Device;
use App\Models\User;
use App\Models\Route;
use App\Models\AllocationPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceRetrievalReport2Factory extends Factory
{
    protected $model = DeviceRetrievalReport2::class;

    public function definition()
    {
        $retrievalDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $returnedAt = $this->faker->boolean(70) ? $this->faker->dateTimeBetween($retrievalDate, 'now') : null;
        $status = $returnedAt ? 'RETURNED' : 'RETRIEVED';

        return [
            'device_id' => Device::factory(),
            'device_full_id' => function (array $attributes) {
                return 'DEV-' . $this->faker->unique()->numberBetween(1000, 9999);
            },
            'boe' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'vehicle_number' => $this->faker->regexify('[A-Z]{2}[0-9]{4}'),
            'regime' => $this->faker->randomElement(['Direct', 'Transit']),
            'destination' => $this->faker->city,
            'allocation_point_id' => AllocationPoint::factory(),
            'retrieval_status' => $status,
            'action_type' => $status,
            'retrieved_by' => User::factory(),
            'returned_by' => $returnedAt ? User::factory() : null,
            'retrieval_date' => $retrievalDate,
            'returned_at' => $returnedAt,
            'overstay_days' => $this->faker->numberBetween(0, 10),
            'overstay_amount' => function (array $attributes) {
                return $attributes['overstay_days'] * 100; // Example rate of 100 per day
            },
            'route_id' => Route::factory(),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at']);
            },
        ];
    }

    /**
     * Configure the model to be in "RETRIEVED" state
     */
    public function retrieved()
    {
        return $this->state(function (array $attributes) {
            return [
                'retrieval_status' => 'RETRIEVED',
                'action_type' => 'RETRIEVED',
                'retrieval_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'returned_at' => null,
                'returned_by' => null,
            ];
        });
    }

    /**
     * Configure the model to be in "RETURNED" state
     */
    public function returned()
    {
        return $this->state(function (array $attributes) {
            $retrievalDate = $this->faker->dateTimeBetween('-30 days', '-2 days');
            return [
                'retrieval_status' => 'RETURNED',
                'action_type' => 'RETURNED',
                'retrieval_date' => $retrievalDate,
                'returned_at' => $this->faker->dateTimeBetween($retrievalDate, 'now'),
                'returned_by' => User::factory(),
            ];
        });
    }
}