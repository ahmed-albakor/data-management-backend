<?php

return [

    'paths' => ['api/*', 'storage/*', 'public/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // يمكنك تقييدها بنطاق معين أو السماح للجميع باستخدام '*'

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => false,

    'max_age' => 0,

    'supports_credentials' => false,

];
