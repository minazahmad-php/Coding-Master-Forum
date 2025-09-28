<?php include '../header.php'; ?>

//views/user/profile.php

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <img src="<?php echo get_gravatar($user['email'], 150); ?>" class="rounded-circle mb-3" alt="Avatar">
                <h3><?php echo $user['username']; ?></h3>
                
                <?php if ($user['full_name']): ?>
                <p class="text-muted"><?php echo $user['full_name']; ?></p>
                <?php endif; ?>
                
                <div class="d-flex justify-content-around mb-3">
                    <div>
                        <h5><?php echo $user['threads_count']; ?></h5>
                        <small>Threads</small>
                    </div>
                    <div>
                        <h5><?php echo $user['posts_count']; ?></h5>
                        <small>Posts</small>
                    </div>
                    <div>
                        <h5><?php echo $user['reputation']; ?></h5>
                        <small>Reputation</small>
                    </div>
                </div>
                
                <div class="text-start">
                    <?php if ($user['location']): ?>
                    <p><i class="fas fa-map-marker-alt me-2"></i> <?php echo $user['location']; ?></p>
                    <?php endif; ?>
                    
                    <?php if ($user['website']): ?>
                    <p><i class="fas fa-globe me-2"></i> <a href="<?php echo $user['website']; ?>" target="_blank">Website</a></p>
                    <?php endif; ?>
                    
                    <?php if ($user['birthday']): ?>
                    <p><i class="fas fa-birthday-cake me-2"></i> <?php echo format_date($user['birthday'], 'F j, Y'); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($user['gender']): ?>
                    <p><i class="fas fa-user me-2"></i> <?php echo ucfirst($user['gender']); ?></p>
                    <?php endif; ?>
                    
                    <p><i class="fas fa-calendar me-2"></i> Joined <?php echo format_date($user['created_at'], 'F Y'); ?></p>
                    
                    <?php if ($user['last_login']): ?>
                    <p><i class="fas fa-clock me-2"></i> Last active <?php echo format_date($user['last_login']); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if (Auth::getUser() && Auth::getUser()['id'] == $user['id']): ?>
                <a href="/user/profile/edit" class="btn btn-primary">Edit Profile</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php if ($user['bio']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">About</h5>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Activity feed will be displayed here.</p>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>