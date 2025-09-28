</div>

//views/footer.php

<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><?php echo SITE_NAME; ?></h5>
                <p>A modern forum built with PHP and SQLite.</p>
            </div>
            <div class="col-md-3">
                <h5>Links</h5>
                <ul class="list-unstyled">
                    <li><a href="/" class="text-light">Home</a></li>
                    <li><a href="/forums" class="text-light">Forums</a></li>
                    <li><a href="/search" class="text-light">Search</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>User</h5>
                <ul class="list-unstyled">
                    <?php if (Auth::isLoggedIn()): ?>
                    <li><a href="/user/profile" class="text-light">Profile</a></li>
                    <li><a href="/messages" class="text-light">Messages</a></li>
                    <li><a href="/logout" class="text-light">Logout</a></li>
                    <?php else: ?>
                    <li><a href="/login" class="text-light">Login</a></li>
                    <li><a href="/register" class="text-light">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <hr class="bg-light">
        <div class="text-center">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/my_forum/public/js/app.js"></script>
</body>
</html>