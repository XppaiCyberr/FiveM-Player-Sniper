<?php
/**
 * API endpoint that reads from SQLite and returns the same JSON format
 * that the HTML pages expect (compatible with the old log.json structure).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dbFile = __DIR__ . '/log.db';

if (!file_exists($dbFile)) {
    echo json_encode([]);
    exit;
}

$db = new SQLite3($dbFile, SQLITE3_OPEN_READONLY);
$db->busyTimeout(5000);

// Get all entries ordered by timestamp
$entries = $db->query('SELECT id, timestamp, clients, max_clients FROM entries ORDER BY id ASC');

$result = [];
while ($entry = $entries->fetchArray(SQLITE3_ASSOC)) {
    // Get players for this entry
    $stmt = $db->prepare('SELECT name, ping FROM players WHERE entry_id = :eid');
    $stmt->bindValue(':eid', $entry['id'], SQLITE3_INTEGER);
    $playersResult = $stmt->execute();

    $players = [];
    while ($player = $playersResult->fetchArray(SQLITE3_ASSOC)) {
        $players[] = [
            'name' => $player['name'],
            'ping' => $player['ping'],
        ];
    }

    $result[] = [
        'timestamp' => $entry['timestamp'],
        'clients' => $entry['clients'],
        'maxClients' => $entry['max_clients'],
        'players' => $players,
    ];
}

$db->close();

echo json_encode($result, JSON_UNESCAPED_UNICODE);
