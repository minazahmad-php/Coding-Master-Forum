<?php include 'header.php'; ?>

//views/search.php

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Search Results</h2>
    <a href="/" class="btn btn-outline-primary">Back to Home</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="/search" method="get">
            <div class="input-group">
                <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search forums, threads, users..." required>
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($threads) || !empty($users)): ?>
<div class="row">
    <?php if (!empty($threads)): ?>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Threads (<?php echo count($threads); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($threads as $thread): ?>
                    <a href="/thread/<?php echo $thread['id']; ?>" class="list-group-item list-group-item-action">
                        <h6 class="mb-1"><?php echo $thread['title']; ?></h6>
                        <small class="text-muted">
                            By <?php echo $thread['username']; ?> in <?php echo $thread['forum_name']; ?>
                            • <?php echo format_date($thread['created_at']); ?>
                        </small>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($users)): ?>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Users (<?php echo count($users); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($users as $user): ?>
                    <a href="/user/<?php echo $user['username']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo get_gravatar($user['email'], 40); ?>" class="rounded-circle me-3" alt="Avatar">
                            <div>
                                <h6 class="mb-0"><?php echo $user['username']; ?></h6>
                                <small class="text-muted">Joined <?php echo format_date($user['created_at']); ?></small>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h4>No results found</h4>
        <p class="text-muted">Try different search terms or browse the forums.</p>
        <a href="/forums" class="btn btn-primary">Browse Forums</a>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>