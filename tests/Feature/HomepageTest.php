<?php

namespace tests\Feature;

use PHPUnit\Framework\TestCase;

class HomepageTest extends TestCase
{
    public function testHomepageReturns200(): void
    {
        $url = getenv('HOMEPAGE_URL') ?: 'http://127.0.0.1:8000/';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $this->assertSame(200, $code, $error);
        $this->assertIsString($response);
        $this->assertStringContainsString('<h1>Welcome</h1>', $response);
        $this->assertStringNotContainsString('ERROR 404', $response);
    }
}
