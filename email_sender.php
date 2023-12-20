<?php

$db = new SQLite3('db.sqlite');
$db->enableExceptions(true);

$emails = selectEmails($db);
sendNotifications($db, $emails);

$db->close();



function selectEmails(SQLite3 $db): SQLite3Result
{
    $query = "
    SELECT
        id, email, username, checked
    FROM
        subscriptions
    WHERE
        (valid = 1 OR checked = 0)
        AND
        (
            (
                expiresAt > strftime('%s', DATE('NOW', '+1 days'))
                AND expiresAt < strftime('%s', DATE('NOW', '+2 days'))
                AND notifiedAt < strftime('%s', DATE('NOW'))
            )
            OR
            (
                expiresAt > strftime('%s', DATE('NOW', '+3 days'))
                AND expiresAt < strftime('%s', DATE('NOW', '+4 days'))
                AND notifiedAt < strftime('%s', DATE('NOW'))
            )
        )
    ORDER BY expiresAt
    LIMIT 500000
    ";

    return $db->query($query);
}

function sendNotifications(SQLite3 $db, SQLite3Result $emails): void
{
    $processes = [];
    $allRowsDerived = false;

    while (!$allRowsDerived || thereIsUnfinishedProcesses($processes)) {

        if (processesLimitHasBeenReached($processes) || $allRowsDerived) {
            updateCheckedEmails($db, $processes);
            sleep(5);
            continue;
        }

        if ($row = $emails->fetchArray(SQLITE3_ASSOC)) {
            createChildProcessToCheckEmailAndSendNotification($row, $processes);
        } else {
            $allRowsDerived = true;
        }
    }
}

function processesLimitHasBeenReached(array $processes): bool
{
    $maxProcesses = 10000;

    return count($processes) >= $maxProcesses;
}

function thereIsUnfinishedProcesses(array $processes): bool
{
    return !empty($processes);
}

function createChildProcessToCheckEmailAndSendNotification(array $row, array &$processes): void
{
    $pid = pcntl_fork();

    if ($pid > 0) {
        $processes[$pid] = $row['id'];
    } elseif ($pid === 0) {
        checkEmailAndSendNotification($row);
    } else {
        exit(0);
    }
}

function checkEmailAndSendNotification(array $row): void
{
    $isValid = $row['checked'] ? true : check_email($row['email']);

    if ($isValid) {
        send_email("our@email.com", $row['email'], sprintf("%s, your subscription is expiring soon", $row['username']));
    }

    exit($isValid ? 1 : 0);
}

function updateCheckedEmails(SQLite3 $db, array &$processes)
{
    $notifiedEmailIds = [];
    $invalidEmailIds = [];

    while ($pid = pcntl_wait($status, WNOHANG)) {

        if ($pid === -1) {
            break;
        }

        if (isset($processes[$pid])) {
            if ($status) {
                $notifiedEmailIds[] = (int)$processes[$pid];
            } else {
                $invalidEmailIds[] = (int)$processes[$pid];
            }
        }

        unset($processes[$pid]);
    }

    updateNotifiedEmails($db, $notifiedEmailIds);
    updateInvalidEmails($db, $invalidEmailIds);

    print count($notifiedEmailIds) . " more emails notified and " . count($invalidEmailIds) . " updated as invalid. ";
    print count($processes) . " emails are under validation for now \n";
}

function updateNotifiedEmails(SQLite3 $db, array $notifiedEmailIds)
{
    if ($notifiedEmailIds) {
        $db->exec(
            sprintf(
                "UPDATE subscriptions SET checked = 1, valid = 1, notifiedAt = %d WHERE id IN (%s)",
                time(),
                implode(',', $notifiedEmailIds)
            )
        );
    }
}


function updateInvalidEmails(SQLite3 $db, array $invalidEmailIds)
{
    if ($invalidEmailIds) {
        $db->exec(
            sprintf(
                "UPDATE subscriptions SET checked = 1, valid = 0 WHERE id IN (%s)",
                implode(',', $invalidEmailIds)
            )
        );
    }
}


function check_email(string $email): int
{
    sleep(random_int(1, 60));

    return !random_int(0, 1);
}


function send_email(string $from, string $to, string $text): void
{
    sleep(random_int(1, 10));
}
