<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();

$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$lockFile = $storageDir . '/installed.lock';
$dbFile = $storageDir . '/forum.sqlite';

if (file_exists($lockFile)) {
    die("<h2>🚫 Forum already installed. Delete storage/installed.lock to reinstall.</h2>");
}

// Connect SQLite
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Enable foreign key support in SQLite
    $pdo->exec('PRAGMA foreign_keys = ON;');
} catch(PDOException $e) {
    die("DB Connection failed: ".$e->getMessage());
}

// Enterprise tables and supporting indexes/triggers
$statements = [
    // Users Table
    "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'user' CHECK(role IN ('user', 'moderator', 'admin')),
        full_name TEXT,
        bio TEXT,
        avatar TEXT DEFAULT 'default-avatar.png',
        cover TEXT,
        location TEXT,
        website TEXT,
        birthday DATE,
        gender TEXT,
        status TEXT DEFAULT 'active' CHECK(status IN ('active', 'banned', 'pending')),
        reputation INTEGER DEFAULT 0,
        posts_count INTEGER DEFAULT 0,
        threads_count INTEGER DEFAULT 0,
        last_login DATETIME,
        login_ip TEXT,
        email_verified INTEGER DEFAULT 0,
        two_factor_secret TEXT,
        reset_token TEXT,
        reset_expires DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE INDEX IF NOT EXISTS idx_users_username ON users (username)",
    "CREATE INDEX IF NOT EXISTS idx_users_email ON users (email)",

    // Forums Table
    "CREATE TABLE IF NOT EXISTS forums (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        icon TEXT,
        slug TEXT UNIQUE NOT NULL,
        threads_count INTEGER DEFAULT 0,
        posts_count INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE INDEX IF NOT EXISTS idx_forums_slug ON forums (slug)",

    // Threads Table
    "CREATE TABLE IF NOT EXISTS threads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        forum_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        content TEXT,
        views INTEGER DEFAULT 0,
        replies_count INTEGER DEFAULT 0,
        is_locked INTEGER DEFAULT 0,
        is_pinned INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_threads_forum_id ON threads (forum_id)",
    "CREATE INDEX IF NOT EXISTS idx_threads_user_id ON threads (user_id)",

    // Posts Table
    "CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        thread_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        is_edited INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_posts_thread_id ON posts (thread_id)",
    "CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts (user_id)",
    
    // Tags and Thread-Tags Tables (New Feature)
    "CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS thread_tags (
        thread_id INTEGER NOT NULL,
        tag_id INTEGER NOT NULL,
        PRIMARY KEY (thread_id, tag_id),
        FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )",

    // Messages Table
    "CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_id INTEGER NOT NULL,
        receiver_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE INDEX IF NOT EXISTS idx_messages_sender_receiver ON messages (sender_id, receiver_id)",

    // Notifications Table
    "CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        message TEXT NOT NULL,
        link TEXT,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Settings Table
    "CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        value TEXT
    )",

    // Reactions (Likes/Dislikes) Table
    "CREATE TABLE IF NOT EXISTS reactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        target_type TEXT NOT NULL,
        target_id INTEGER NOT NULL,
        reaction_type TEXT NOT NULL CHECK(reaction_type IN ('like', 'dislike')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (user_id, target_type, target_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE INDEX IF NOT EXISTS idx_reactions_target ON reactions (target_type, target_id)",

    // Badges & User_Badges Tables
    "CREATE TABLE IF NOT EXISTS badges (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        description TEXT,
        icon TEXT
    )",
    "CREATE TABLE IF NOT EXISTS user_badges (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        badge_id INTEGER NOT NULL,
        granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
    )",

    // Other tables...
    "CREATE TABLE IF NOT EXISTS attachments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        post_id INTEGER,
        thread_id INTEGER,
        file_path TEXT NOT NULL,
        file_type TEXT,
        file_size INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        reporter_id INTEGER NOT NULL,
        target_type TEXT NOT NULL,
        target_id INTEGER NOT NULL,
        reason TEXT NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        target_type TEXT,
        target_id INTEGER,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",
    "CREATE TABLE IF NOT EXISTS polls (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        thread_id INTEGER UNIQUE NOT NULL,
        question TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS poll_options (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        poll_id INTEGER NOT NULL,
        option_text TEXT NOT NULL,
        votes INTEGER DEFAULT 0,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS poll_votes (
        poll_id INTEGER NOT NULL,
        option_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (poll_id, user_id),
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
        FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS friendships (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        requester_id INTEGER NOT NULL,
        receiver_id INTEGER NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS sessions (
        id TEXT PRIMARY KEY,
        user_id INTEGER NOT NULL,
        ip_address TEXT,
        user_agent TEXT,
        last_active DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS api_keys (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        api_key TEXT UNIQUE NOT NULL,
        permissions TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Triggers to auto-update 'updated_at' timestamps
    "CREATE TRIGGER IF NOT EXISTS update_users_updated_at
         AFTER UPDATE ON users FOR EACH ROW
         BEGIN
             UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
         END;",
    "CREATE TRIGGER IF NOT EXISTS update_threads_updated_at
         AFTER UPDATE ON threads FOR EACH ROW
         BEGIN
             UPDATE threads SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
         END;",
    "CREATE TRIGGER IF NOT EXISTS update_posts_updated_at
         AFTER UPDATE ON posts FOR EACH ROW
         BEGIN
             UPDATE posts SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
         END;"
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $email && $password && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Create tables with live feedback
            $results = [];
            foreach ($statements as $sql) {
                // Extract table/index/trigger name for logging
                preg_match('/CREATE (?:TABLE|INDEX|TRIGGER)(?: IF NOT EXISTS)?\s+([^\s(]+)/i', $sql, $matches);
                $name = $matches[1] ?? 'statement';
                
                try {
                    $pdo->exec($sql);
                    $results[] = "✅ {$name} created/exists.";
                } catch(Exception $e) {
                    $results[] = "❌ Error creating {$name}: ".$e->getMessage();
                }
            }

            // Insert admin
            $hash = password_hash($password, PASSWORD_BCRYPT);
            // CORRECTED: Parameter count now matches column count (4)
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, 'admin']);

            // Save a default setting
            $pdo->prepare("INSERT INTO settings (name, value) VALUES ('site_name', 'Coding Master')")->execute();

            // Lock install
            file_put_contents($lockFile, "Installed on ".date("Y-m-d H:i:s"));

            echo json_encode(["success"=>true, "messages"=>$results]);
            exit;
        } catch (Exception $e) {
            echo json_encode(["success"=>false, "messages"=>["An error occurred: " . $e->getMessage()]]);
            exit;
        }
    } else {
        $errors = [];
        if (empty($username)) $errors[] = "Admin Username is required.";
        if (empty($email)) $errors[] = "Admin Email is required.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Admin Email is not valid.";
        if (empty($password)) $errors[] = "Admin Password is required.";
        
        echo json_encode(["success"=>false, "messages"=>$errors]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Coding Master Installer</title>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f0f2f5; color: #1c1e21; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
.installer-box { background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.1); padding: 20px; width: 100%; max-width: 450px; }
h2 { text-align: center; color: #1877f2; }
form { display: flex; flex-direction: column; gap: 15px; }
label { font-weight: bold; }
input { padding: 12px; font-size: 16px; border: 1px solid #dddfe2; border-radius: 6px; }
input:focus { border-color: #1877f2; outline: none; }
button { padding: 12px; font-size: 16px; background: #1877f2; color: #fff; border: none; cursor: pointer; border-radius: 6px; font-weight: bold; }
button:hover { background: #166fe5; }
#status { margin-top: 20px; padding: 10px; border: 1px solid #ccc; border-radius: 6px; max-height: 250px; overflow-y: auto; font-family: monospace; font-size: 0.9em; background: #f7f7f7; }
#status a { color: #1877f2; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>
<div class="installer-box">
    <h2>Coding Master Forum Installer</h2>
    <form id="installForm">
        <label for="username">Admin Username</label>
        <input type="text" id="username" name="username" required>
        <label for="email">Admin Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Admin Password</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Install Forum</button>
    </form>
    <div id="status"></div>
</div>

<script>
document.getElementById('installForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const statusDiv = document.getElementById('status');
    const button = this.querySelector('button');
    statusDiv.innerHTML = 'Installing...<br>';
    button.disabled = true;
    button.textContent = 'Installing...';

    const formData = new FormData(this);
    fetch('install.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            data.messages.forEach(m => statusDiv.innerHTML += m + '<br>');
            statusDiv.innerHTML += '<br><b>✅ Installation successful!</b> <a href="index.php">Go to Forum</a>';
            // Hide form on success
            document.getElementById('installForm').style.display = 'none';
        } else {
            statusDiv.innerHTML = '<span style="color:red;"><b>Installation failed:</b></span><br>';
            data.messages.forEach(m => statusDiv.innerHTML += '❌ ' + m + '<br>');
        }
    }).catch(err => {
        statusDiv.innerHTML = 'An unexpected error occurred: ' + err;
    }).finally(() => {
        button.disabled = false;
        button.textContent = 'Install Forum';
    });
});
</script>
</body>
</html>