<?php

namespace Tests\Unit;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;

class MeetingTransactionTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_user_login_post()
    {   
        $data = [
            'email' => 'mentor@example.com',
            'password' => '12345678',
        ];

        $this->post(env('APP_URL') . 'api/v1/auth/u/login', $data)
                ->assertStatus(200)
                ->assertJson($data);
    }
}
