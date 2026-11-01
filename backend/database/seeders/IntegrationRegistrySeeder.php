<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IntegrationServiceType;
use App\Models\IntegrationProvider;

class IntegrationRegistrySeeder extends Seeder
{
    public function run(): void
    {
        IntegrationServiceType::upsert([
            ['key' => 'payment', 'name' => 'Payment Gateway', 'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'merchant_id' => ['type' => 'string'],
                    'api_key' => ['type' => 'string'],
                    'environment' => ['type' => 'string', 'enum' => ['sandbox', 'production']],
                ],
                'required' => ['merchant_id', 'api_key', 'environment'],
            ])],
            ['key' => 'sms', 'name' => 'SMS Provider', 'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'account_sid' => ['type' => 'string'],
                    'auth_token' => ['type' => 'string'],
                    'from_number' => ['type' => 'string'],
                ],
                'required' => ['account_sid', 'auth_token', 'from_number'],
            ])],
            ['key' => 'email', 'name' => 'Email Provider', 'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'api_key' => ['type' => 'string'],
                    'domain' => ['type' => 'string'],
                    'from_email' => ['type' => 'string'],
                ],
                'required' => ['api_key', 'domain', 'from_email'],
            ])],
        ], ['key'], ['name', 'config_schema']);

        IntegrationProvider::upsert([
            ['key' => 'stripe', 'service_type' => 'payment', 'name' => 'Stripe'],
            ['key' => 'paypal', 'service_type' => 'payment', 'name' => 'PayPal'],
            ['key' => 'twilio', 'service_type' => 'sms', 'name' => 'Twilio'],
            ['key' => 'vonage', 'service_type' => 'sms', 'name' => 'Vonage'],
            ['key' => 'sendgrid', 'service_type' => 'email', 'name' => 'SendGrid'],
            ['key' => 'mailgun', 'service_type' => 'email', 'name' => 'Mailgun'],
        ], ['key'], ['service_type', 'name']);
    }
}