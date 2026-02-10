<?php

$apiUrl = 'https://frontend.cfx-services.net/api/servers/single/zrvmg4';
$dbFile = __DIR__ . '/log.db';
$maxEntries = 2016; // ~7 days at 5-min intervals

// Fetch data from API
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    error_log("Failed to fetch FiveM data. HTTP: $httpCode");
    exit(1);
}

$json = json_decode($response, true);
if (!isset($json['Data'])) {
    error_log("Invalid API response structure");
    exit(1);
}

$data = $json['Data'];
$players = $data['players'] ?? [];

// Initialize SQLite database
$db = new SQLite3($dbFile);
$db->busyTimeout(5000);
$db->exec('PRAGMA journal_mode=WAL');

// Create tables if they don't exist
$db->exec('
    CREATE TABLE IF NOT EXISTS entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp TEXT NOT NULL,
        clients INTEGER NOT NULL,
        max_clients INTEGER NOT NULL
    )
');

$db->exec('
    CREATE TABLE IF NOT EXISTS players (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        entry_id INTEGER NOT NULL,
        player_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        ping INTEGER NOT NULL,
        FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE
    )
');

// Create index for faster lookups
$db->exec('CREATE INDEX IF NOT EXISTS idx_players_entry_id ON players(entry_id)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_entries_timestamp ON entries(timestamp)');

// Insert new entry
$timestamp = date('c');
$clients = $data['clients'] ?? 0;
$maxClients = $data['sv_maxclients'] ?? 0;

$stmt = $db->prepare('INSERT INTO entries (timestamp, clients, max_clients) VALUES (:ts, :clients, :max)');
$stmt->bindValue(':ts', $timestamp, SQLITE3_TEXT);
$stmt->bindValue(':clients', $clients, SQLITE3_INTEGER);
$stmt->bindValue(':max', $maxClients, SQLITE3_INTEGER);
$stmt->execute();

$entryId = $db->lastInsertRowID();

// Insert players
$stmt = $db->prepare('INSERT INTO players (entry_id, player_id, name, ping) VALUES (:eid, :pid, :name, :ping)');
foreach ($players as $p) {
    $stmt->bindValue(':eid', $entryId, SQLITE3_INTEGER);
    $stmt->bindValue(':pid', $p['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':name', $p['name'], SQLITE3_TEXT);
    $stmt->bindValue(':ping', $p['ping'], SQLITE3_INTEGER);
    $stmt->execute();
    $stmt->reset();
}

// Trim old entries beyond maxEntries (keep newest)
$totalEntries = $db->querySingle('SELECT COUNT(*) FROM entries');
if ($totalEntries > $maxEntries) {
    $deleteCount = $totalEntries - $maxEntries;
    $db->exec("DELETE FROM entries WHERE id IN (SELECT id FROM entries ORDER BY id ASC LIMIT $deleteCount)");
    // Clean up orphaned players
    $db->exec('DELETE FROM players WHERE entry_id NOT IN (SELECT id FROM entries)');
}

$db->close();

echo "Logged " . count($players) . " players at " . $timestamp . "\n";
