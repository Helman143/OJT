<footer class="admin-footer">
    <p>&copy; <?php echo date('Y'); ?> NCIP Job Application System. All rights reserved.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menu toggle
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
    
    // Dark mode toggle
    const darkModeToggle = document.querySelector('.dark-mode-toggle');
    const darkModeIcon = document.querySelector('.dark-mode-icon');
    
    // Restore dark mode state from localStorage
    const savedDarkMode = localStorage.getItem('darkMode');
    if (savedDarkMode === 'true') {
        document.documentElement.classList.add('dark-mode');
        if (darkModeIcon) {
            darkModeIcon.textContent = '☀️';
        }
    }
    
    if (darkModeToggle && darkModeIcon) {
        darkModeToggle.addEventListener('click', function() {
            const isDark = document.documentElement.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', isDark);
            
            // Update icon
            if (isDark) {
                darkModeIcon.textContent = '☀️';
            } else {
                darkModeIcon.textContent = '🌙';
            }
        });
    }
    
    // Profile dropdown
    const profileDropdown = document.querySelector('.profile-dropdown');
    const profileTrigger = document.querySelector('.profile-trigger');
    
    if (profileTrigger) {
        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
            const isOpen = profileDropdown.classList.contains('active');
            profileTrigger.setAttribute('aria-expanded', isOpen.toString());
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
                profileTrigger.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // Animate stat cards on load
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(function(card, index) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(function() {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animate activity items
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach(function(item, index) {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        setTimeout(function() {
            item.style.transition = 'all 0.4s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, index * 100);
    });
});
</script>

