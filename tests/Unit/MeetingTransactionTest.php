<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

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

        $this->json(env('APP_URL') . 'api/v1/auth/u/login', $data, ['Accept' => 'application/json'])
                ->assertStatus(200)
                ->assertJson($data);
    }
}
