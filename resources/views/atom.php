<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title><?= config('app.name') ?> - Recent Threads</title>
    <link href="<?= config('app.url') ?>"/>
    <link href="<?= config('app.url') ?>/atom" rel="self"/>
    <id><?= config('app.url') ?></id>
    <updated><?= date('c') ?></updated>
    <author>
        <name><?= config('app.name') ?></name>
    </author>
    
    <?php foreach ($recent_threads as $thread): ?>
    <entry>
        <title><?= htmlspecialchars($thread['title']) ?></title>
        <link href="<?= config('app.url') ?>/thread/<?= $thread['id'] ?>"/>
        <id><?= config('app.url') ?>/thread/<?= $thread['id'] ?></id>
        <updated><?= date('c', strtotime($thread['created_at'])) ?></updated>
        <summary type="html"><![CDATA[<?= htmlspecialchars(substr($thread['content'], 0, 500)) ?><?= strlen($thread['content']) > 500 ? '...' : '' ?>]]></summary>
        <author>
            <name><?= htmlspecialchars($thread['username']) ?></name>
        </author>
    </entry>
    <?php endforeach; ?>
</feed>