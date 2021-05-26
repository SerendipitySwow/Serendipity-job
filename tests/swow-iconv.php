<?php
declare(strict_types = 1);

$text = "This is the Euro symbol '€'.";

echo 'Original : ', $text, PHP_EOL;
echo 'TRANSLIT : ', iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text), PHP_EOL;
exit;
