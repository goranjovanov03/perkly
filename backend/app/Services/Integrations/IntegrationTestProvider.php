<?php

namespace App\Services\Integrations;

interface IntegrationTestProvider
{
    public function test(array $config, array $secrets): array;
}