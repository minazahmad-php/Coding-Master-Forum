<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><?= config('app.name') ?> - Recent Threads</title>
        <link><?= config('app.url') ?></link>
        <description>Latest discussions from <?= config('app.name') ?></description>
        <language>en-us</language>
        <lastBuildDate><?= date('r') ?></lastBuildDate>
        <atom:link href="<?= config('app.url') ?>/rss" rel="self" type="application/rss+xml"/>
        
        <?php foreach ($recent_threads as $thread): ?>
        <item>
            <title><?= htmlspecialchars($thread['title']) ?></title>
            <link><?= config('app.url') ?>/thread/<?= $thread['id'] ?></link>
            <description><![CDATA[<?= htmlspecialchars(substr($thread['content'], 0, 500)) ?><?= strlen($thread['content']) > 500 ? '...' : '' ?>]]></description>
            <author><?= htmlspecialchars($thread['username']) ?></author>
            <pubDate><?= date('r', strtotime($thread['created_at'])) ?></pubDate>
            <guid><?= config('app.url') ?>/thread/<?= $thread['id'] ?></guid>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>