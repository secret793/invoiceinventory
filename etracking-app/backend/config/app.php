<?php
return [
    'env'        => getenv('APP_ENV')    ?: 'production',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'change_this_secret_in_production_32chars',
    'jwt_ttl'    => (int)(getenv('JWT_TTL') ?: 86400 * 7),
    'app_url'    => getenv('APP_URL')    ?: 'http://localhost',
    'cors_origins' => ['*'],
];
