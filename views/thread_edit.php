<?php include 'header.php'; ?>

//views/thread_edit.php

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item"><a href="/forums">Forums</a></li>
        <li class="breadcrumb-item"><a href="/forum/<?php echo $thread['forum_slug']; ?>"><?php echo $thread['forum_name']; ?></a></li>
        <li class="breadcrumb-item"><a href="/thread/<?php echo $thread['id']; ?>"><?php echo $thread['title']; ?></a></li>
        <li class="breadcrumb-item active">Edit Thread</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Edit Thread</h4>
    </div>
    <div class="card-body">
        <form action="/thread/<?php echo $thread['id']; ?>/edit" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="mb-3">
                <label for="title" class="form-label">Thread Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($thread['title']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="content" class="form-label">Thread Content</label>
                <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($thread['content']); ?></textarea>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="/thread/<?php echo $thread['id']; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Thread</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>