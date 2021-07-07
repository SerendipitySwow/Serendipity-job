<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

return [
    [
        //签名密钥
        'signatureSecret' => env('SIGNATURE_SECRET', ''),
        //签名key
        'signatureApiKey' => env('SIGNATURE_API_KEY', ''),
        //签名有效期限秒,默认1分钟
        'timestampValidity' => 60,
    ],
];
