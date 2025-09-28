<?php include 'header.php'; ?>

//views/post_edit.php

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item"><a href="/forums">Forums</a></li>
        <li class="breadcrumb-item"><a href="/forum/<?php echo $post['forum_slug']; ?>"><?php echo $post['forum_name']; ?></a></li>
        <li class="breadcrumb-item"><a href="/thread/<?php echo $post['thread_id']; ?>"><?php echo $post['thread_title']; ?></a></li>
        <li class="breadcrumb-item active">Edit Post</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Edit Post</h4>
    </div>
    <div class="card-body">
        <form action="/post/<?php echo $post['id']; ?>/edit" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="mb-3">
                <label for="content" class="form-label">Post Content</label>
                <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="/thread/<?php echo $post['thread_id']; ?>#post-<?php echo $post['id']; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Post</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>