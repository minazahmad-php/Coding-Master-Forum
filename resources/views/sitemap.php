<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= config('app.url') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <url>
        <loc><?= config('app.url') ?>/forums</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc><?= config('app.url') ?>/members</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    
    <url>
        <loc><?= config('app.url') ?>/statistics</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
    
    <?php foreach ($forums as $forum): ?>
    <url>
        <loc><?= config('app.url') ?>/forum/<?= $forum['id'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($forum['updated_at'])) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
    
    <?php foreach ($threads as $thread): ?>
    <url>
        <loc><?= config('app.url') ?>/thread/<?= $thread['id'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($thread['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>
</urlset>