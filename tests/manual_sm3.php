<?php

require __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\Digests\SM3Digest;

$inputs = [
    "abc",
    "abcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcd"
];

echo "Running SM3 Manual Test...\n\n";

foreach ($inputs as $input) {
    $digest = new SM3Digest();
    $digest->updateBytes($input, 0, strlen($input));
    $output = str_repeat("\x00", 32);
    $digest->doFinal($output, 0);
    echo "Input: '$input'\n";
    echo "Hash:  " . bin2hex($output) . "\n";
    echo "------------------------------------------------\n";
}

