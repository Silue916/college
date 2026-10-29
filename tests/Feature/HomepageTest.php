<?php

namespace tests\Feature;

use PHPUnit\Framework\TestCase;

class HomepageTest extends TestCase
{
    public function testHomepageReturns200(): void
    {
        $url = 'http://127.0.0.1:8000/';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 0) {
            $this->markTestSkipped('Serveur local non disponible');
        }
        $this->assertSame(200, $code);
    }
}
