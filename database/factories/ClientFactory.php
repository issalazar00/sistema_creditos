<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
	/**
	 * The name of the factory's corresponding model.
	 *
	 * @var string
	 */
	protected $model = Client::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition()
	{
		return [
			'name' => $this->faker->firstName(),
			'last_name' => $this->faker->lastName(),
			'tipo_documento' => $this->faker->boolean(),
			'document_number' => $this->faker->randomNumber(),
			'fecha_nacimiento' => $this->faker->date(),
			'email' => $this->faker->email(),
			'genero' => $this->faker->randomElement($array = ['f', 'm']),
			'celular1' => $this->faker->phoneNumber(),
			'celular2' => $this->faker->phoneNumber(),
			'direccion' => $this->faker->address(),
			'estado_civil' => $this->faker->numberBetween(1, 3),
			'lugar_trabajo' => $this->faker->address(),
			'cargo' => $this->faker->streetAddress(),
			'independiente' => $this->faker->boolean(),
			'foto' => $this->faker->image()
		];
	}
}
