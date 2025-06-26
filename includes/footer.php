<!-- Footer -->
<footer class="main-footer">
    <div class="container">

        <center>
            <strong>All Rights reserved © 2025. James Wekesa.</strong>
        </center>
    </div>
</footer>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>

<!-- Page-specific JS -->
<?php if (isset($page_js)): ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/<?php echo $page_js; ?>"></script>
<?php endif; ?>

<script>
    $(document).ready(function () {
        // Store references to elements
        const $toggler = $('.navbar-toggler');
        const $navbarCollapse = $('#navbarCollapse');
        const $navLinks = $('.navbar-nav a');

        // Proper mobile menu toggle with single click
        $toggler.click(function (e) {
            e.stopPropagation(); // Prevent event bubbling
            $navbarCollapse.collapse('toggle'); // Use Bootstrap's built-in collapse method
            $(this).toggleClass('collapsed');
        });

        // Close menu when clicking outside
        $(document).click(function (e) {
            if (!$(e.target).closest('.navbar').length && $navbarCollapse.hasClass('show')) {
                $navbarCollapse.collapse('hide');
                $toggler.addClass('collapsed');
            }
        });

        // Close menu when clicking a nav link (mobile only)
        $navLinks.click(function () {
            if ($(window).width() < 992) {
                $navbarCollapse.collapse('hide');
                $toggler.addClass('collapsed');
            }
        });

        // Handle Bootstrap collapse events
        $navbarCollapse.on('show.bs.collapse', function () {
            $toggler.removeClass('collapsed');
        });

        $navbarCollapse.on('hide.bs.collapse', function () {
            $toggler.addClass('collapsed');
        });
    });
</script>
</body>

</html>