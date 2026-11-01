<?php

namespace App\Services\Integrations;

class FakeIntegrationTestProvider implements IntegrationTestProvider
{
    public function test(array $config, array $secrets): array
    {
        if (isset($config['fail']) && $config['fail'] === true) {
            return [
                'success' => false,
                'message' => 'Connection test failed',
            ];
        }

        return [
            'success' => true,
            'message' => 'Connection test successful',
        ];
    }
}