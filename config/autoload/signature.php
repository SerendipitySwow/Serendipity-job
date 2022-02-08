<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

return [
    //签名密钥
    'signatureSecret' => env('SIGNATURE_SECRET', ''),
    //签名key
    'signatureAppKey' => env('SIGNATURE_APP_KEY', ''),
    //签名有效期限秒,默认30天
    'timestampValidity' => 3600 * 24 * 60 * 30,
];
