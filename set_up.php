<?php

$db = new SQLite3('db.sqlite');
$db->enableExceptions(true);
createTable($db);
fillTable($db);
$db->close();



function createTable(SQLite3 $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
            id INTEGER PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            expiresAt INT NOT NULL DEFAULT 0,
            notifiedAt INT NOT NULL DEFAULT 0,
            confirmed BOOLEAN NOT NULL,
            checked BOOLEAN NOT NULL DEFAULT 0,
            valid BOOLEAN NOT NULL DEFAULT 0
         );
    ");
}

function fillTable(SQLite3 $db, int $rows = 1000000): void
{
    $db->exec("DELETE FROM subscriptions;");

    $values = [];

    for ($i = 0; $i <= $rows; $i ++) {
        $username = randName();
        $email = randEmail();
        $expiresAt = randExpiresAt();
        $confirmed = randConfirmed();
        $values[] = sprintf("('%s', '%s', %d, %d)", $username, $email, $expiresAt, $confirmed);
    }

    $query = "INSERT INTO subscriptions (username, email, expiresAt, confirmed) VALUES" . implode(',', $values);

    $db->exec($query);
}


function randEmail(): string
{
    $domains = ["gmail.com", "yahoo.com", "hotmail.com", "aol.com"];

    return sprintf("%s.%d@%s", randName(), random_int(0, 1000), $domains[array_rand($domains)]);
}

function randName(): string
{
    $names = ["Olivia", "Liam", "Emma", "Noah", "Charlotte", "Oliver"];

    return $names[array_rand($names)];
}

function randExpiresAt(): int
{
    return rand(0, 6.6) <= 1 ? random_int(time() - 10000, time() + 10000000) : 0;
}

function randConfirmed(): int
{
    return rand(0, 5) <= 1 ? 1 : 0;
}
