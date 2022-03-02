<?php

namespace Tests\Feature;

use App\Models\Programmes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProgrammeControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function it_store()
    {
        $user = User::factory()->make();
        $programme = Programmes::factory()->make();

        
    }
}
