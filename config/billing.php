<?php

return [
    'stripe' => [
        'secret_key'        => env('STRIPE_SECRET_KEY'),
        'webhook_secret'    => env('STRIPE_WEBHOOK_SECRET'),
        'price_cloud'       => env('STRIPE_PRICE_CLOUD_MONTHLY'),
        'price_self_hosted' => env('STRIPE_PRICE_SELF_HOSTED'),
    ],

    'razorpay' => [
        'key_id'                   => env('RAZORPAY_KEY_ID'),
        'key_secret'               => env('RAZORPAY_KEY_SECRET'),
        'amount_cloud_inr'         => (int) env('RAZORPAY_AMOUNT_CLOUD_INR', 399900),       // paise: ₹3,999
        'amount_self_hosted_inr'   => (int) env('RAZORPAY_AMOUNT_SELF_HOSTED_INR', 599900), // paise: ₹5,999/month
    ],

    'plans' => [
        'cloud_enterprise' => [
            'label'      => 'Cloud Enterprise',
            'price_usd'  => '$49',
            'price_inr'  => '₹3,999',
            'period'     => '/month',
            'billing'    => 'monthly',
            'features'   => [
                'Unlimited jobs & candidates',
                'Full AI resume analysis',
                'Resource allocation module',
                'Signal intelligence',
                'All integrations',
                'Priority support',
            ],
        ],
        'self_hosted' => [
            'label'      => 'Self-Hosted Enterprise',
            'price_usd'  => '$79',
            'price_inr'  => '₹5,999',
            'period'     => '/month',
            'billing'    => 'monthly',
            'features'   => [
                'Everything in Cloud Enterprise',
                'Deploy on AWS, GCP, or Azure',
                'Full source code access',
                'Monthly updates included',
                'Data stays on your infrastructure',
                'Custom SLA available',
                'Priority support',
            ],
        ],
    ],
];
