<?php

return [
    'secret' => env('JWT_SECRET'),
    'ttl' => 60 * 24, // 24 horas
    'refresh_ttl' => 20160, // 2 semanas
    'algo' => 'HS256',
    'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],
];