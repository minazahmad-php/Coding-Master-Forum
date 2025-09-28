<?php include 'header.php'; ?>

//views/thread_create.php

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item"><a href="/forums">Forums</a></li>
        <li class="breadcrumb-item"><a href="/forum/<?php echo $forum['slug']; ?>"><?php echo $forum['name']; ?></a></li>
        <li class="breadcrumb-item active">Create Thread</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Create New Thread</h4>
    </div>
    <div class="card-body">
        <form action="/forum/<?php echo $forum['slug']; ?>/create-thread" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="mb-3">
                <label for="title" class="form-label">Thread Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            
            <div class="mb-3">
                <label for="content" class="form-label">Thread Content</label>
                <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="/forum/<?php echo $forum['slug']; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Thread</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>