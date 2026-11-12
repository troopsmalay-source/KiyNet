<?php

namespace Tests\Unit;

use App\Models\Langganan;
use App\Models\Router;
use Tests\TestCase;

class EncryptedCredentialTest extends TestCase
{
    public function test_router_and_subscription_credentials_are_encrypted_at_rest(): void
    {
        $router = new Router();
        $router->password_encrypted = 'router-secret';

        $langganan = new Langganan();
        $langganan->pppoe_password_encrypted = 'pppoe-secret';

        $this->assertNotSame('router-secret', $router->getAttributes()['password_encrypted']);
        $this->assertSame('router-secret', $router->password_encrypted);
        $this->assertNotSame('pppoe-secret', $langganan->getAttributes()['pppoe_password_encrypted']);
        $this->assertSame('pppoe-secret', $langganan->pppoe_password_encrypted);
    }
}
