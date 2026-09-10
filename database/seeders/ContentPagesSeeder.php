<?php

namespace Database\Seeders;

use App\Models\ContentPage;
use Illuminate\Database\Seeder;

class ContentPagesSeeder extends Seeder
{
    public function run(): void
    {
        // Privacy Policy
        ContentPage::create([
            'slug' => 'privacy-policy',
            'title' => 'Privacy Policy',
            'introduction' => 'we value your privacy and are committed to protecting your personal information. This policy explains how we collect, use, and safeguard your data.',
            'sections' => [
                [
                    'title' => 'Information We Collect',
                    'content' => [
                        'We collect information you provide directly when using our services.',
                        'We also collect automatic information such as IP address and browser type.',
                    ],
                    'items' => [
                        'Account Information (Name, Email)',
                        'Company Information',
                        'Invoice and Payment Data',
                        'Usage and Activity Logs',
                    ],
                ],
                [
                    'title' => 'How We Use Information',
                    'content' => [
                        'We use your information to provide and improve our services.',
                        'We will not sell or share your data with third parties.',
                    ],
                ],
                [
                    'title' => 'Data Protection Measures',
                    'content' => [
                        'We use advanced encryption technologies (SSL/TLS).',
                        'We implement strict security procedures.',
                    ],
                    'items' => [
                        'Data Encryption',
                        'Two-Factor Authentication',
                        'Regular Backups',
                        'Continuous Security Monitoring',
                    ],
                ],
            ],
            'version' => '1.0',
            'effective_date' => '2024-01-15',
            'is_active' => true,
        ]);

        // Terms of Service
        ContentPage::create([
            'slug' => 'terms-of-service',
            'title' => 'Terms of Service',
            'introduction' => 'By using the invoicing system, you agree to be bound by these terms and conditions.',
            'sections' => [
                [
                    'title' => 'Definitions',
                    'content' => [
                        '"The System" refers to the invoicing management platform.',
                        '"The User" refers to any person who uses the system.',
                    ],
                ],
                [
                    'title' => 'Use of Service',
                    'content' => [
                        'You must be at least 18 years old to use the service.',
                        'You are responsible for maintaining the confidentiality of your account information.',
                    ],
                    'sub_sections' => [
                        [
                            'title' => 'Usage Restrictions',
                            'content' => 'Unauthorized access or insertion of malicious software is prohibited.',
                        ],
                    ],
                ],
            ],
            'version' => '1.0',
            'effective_date' => '2024-01-01',
            'acceptance_text' => 'By using the system, you agree to these terms and conditions.',
            'is_active' => true,
        ]);
    }
}
