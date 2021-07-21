<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

return [
    //签名密钥
    'signatureSecret' => env('SIGNATURE_SECRET', ''),
    //签名key
    'signatureAppKey' => env('SIGNATURE_APP_KEY', ''),
    //签名有效期限秒,默认30天
    'timestampValidity' => 3600 * 24 * 60 *30,
];
