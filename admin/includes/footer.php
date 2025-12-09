<footer class="admin-footer">
    <p>&copy; <?php echo date('Y'); ?> NCIP Job Application System. All rights reserved.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.menu-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const header = btn.closest('.header-content');
            if (!header) return;
            const nav = header.querySelector('nav, .admin-nav');
            if (!nav) return;
            const isOpen = nav.classList.toggle('nav-open');
            btn.setAttribute('aria-expanded', isOpen.toString());
        });
    });
});
</script>

