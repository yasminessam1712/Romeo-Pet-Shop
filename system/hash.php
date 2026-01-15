<?php
$users = [
    'adam hakimi'
];

foreach ($users as $u) {
    $hash = password_hash('1234', PASSWORD_DEFAULT);
    echo "User: $u | Hash: $hash\n";
}
?>
