    <!-- Minimal Admin Footer -->
    <footer class="admin-footer-minimal">
        <div class="footer-content">
            <div class="footer-left">
                <p class="footer-copyright">&copy; 2026 Alertara LGU System – Quezon City</p>
                <p class="footer-internal-use">For internal use only</p>
            </div>
            <div class="footer-right">
                <p class="footer-user-info" id="footerUserInfo">Logged in as: <span id="footerUserName">Loading...</span></p>
            </div>
        </div>
    </footer>

    <style>
        .admin-footer-minimal {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 12px 0;
            margin-top: 32px;
            font-size: 12px;
            color: #64748b;
            width: 100%;
            box-sizing: border-box;
        }
        
        .footer-content {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            box-sizing: border-box;
        }
        
        .footer-left {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .footer-copyright {
            margin: 0;
            font-size: 12px;
            color: #64748b;
        }
        
        .footer-internal-use {
            margin: 0;
            font-size: 11px;
            color: #94a3b8;
            font-style: italic;
        }
        
        .footer-right {
            display: flex;
            align-items: center;
        }
        
        .footer-user-info {
            margin: 0;
            font-size: 12px;
            color: #64748b;
        }
        
        .footer-user-info span {
            color: #475569;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                text-align: center;
                padding: 0 16px;
            }
            
            .footer-left,
            .footer-right {
                width: 100%;
                text-align: center;
            }
        }
    </style>

    <script>
        // Load user info for footer (similar to admin header)
        (function() {
            function updateFooterUserInfo() {
                try {
                    const userInfo = localStorage.getItem('currentUser');
                    const footerUserName = document.getElementById('footerUserName');
                    
                    if (userInfo && footerUserName) {
                        try {
                            const user = JSON.parse(userInfo);
                            const displayName = user.name || user.username || 'User';
                            const role = user.role ? ` (${user.role})` : '';
                            footerUserName.textContent = displayName + role;
                        } catch (e) {
                            footerUserName.textContent = 'User';
                        }
                    } else if (footerUserName) {
                        footerUserName.textContent = 'User';
                    }
                } catch (e) {
                    console.error('Error updating footer user info:', e);
                }
            }
            
            // Update on page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', updateFooterUserInfo);
            } else {
                updateFooterUserInfo();
            }
            
            // Update when user info changes (listen for storage events)
            window.addEventListener('storage', function(e) {
                if (e.key === 'currentUser') {
                    updateFooterUserInfo();
                }
            });
            
            // Also update after a short delay to catch async user loading
            setTimeout(updateFooterUserInfo, 500);
            setTimeout(updateFooterUserInfo, 1500);
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Mobile Navigation Toggle
        const mobileMenuToggle = document.querySelectorAll('.mobile-menu-toggle');
        const mobileNavClose = document.querySelector('.mobile-nav-close');
        const mobileNav = document.querySelector('.mobile-nav');
        const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');

        // Only set up mobile nav if elements exist (they won't exist on login page)
        if (mobileNav && mobileNavOverlay) {
            mobileMenuToggle.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    mobileNav.classList.toggle('active');
                    mobileNavOverlay.classList.toggle('active');
                    document.body.style.overflow = mobileNav.classList.contains('active') ? 'hidden' : '';
                });
            });

            // Close button functionality
            if (mobileNavClose) {
                mobileNavClose.addEventListener('click', () => {
                    mobileNav.classList.remove('active');
                    mobileNavOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            mobileNavOverlay.addEventListener('click', () => {
                mobileNav.classList.remove('active');
                mobileNavOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        // Theme Toggle
        const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
        const htmlElement = document.documentElement;

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'system';
        htmlElement.setAttribute('data-theme', savedTheme);
        updateThemeButtons(savedTheme);

        themeToggleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const theme = btn.getAttribute('data-theme');
                htmlElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                updateThemeButtons(theme);
                
                // Apply system theme if selected
                if (theme === 'system') {
                    applySystemTheme();
                }
            });
        });

        function updateThemeButtons(theme) {
            themeToggleBtns.forEach(btn => {
                if (btn.getAttribute('data-theme') === theme) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        function applySystemTheme() {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            htmlElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'system') {
                applySystemTheme();
            }
        });

        // Apply system theme on load if needed
        if (savedTheme === 'system') {
            applySystemTheme();
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (!href || href === '#') return;
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Close mobile menu if open
                    if (mobileNav && mobileNav.classList.contains('active')) {
                        mobileNav.classList.remove('active');
                        if (mobileNavOverlay) {
                            mobileNavOverlay.classList.remove('active');
                        }
                        document.body.style.overflow = '';
                    }
                }
            });
        });

        // Global logout helper available on all pages
        (function () {
            try {
                if (typeof window.logout !== 'function') {
                    const basePath = '<?php echo $basePath; ?>';
                    window.logout = function () {
                        try {
                            localStorage.removeItem('jwtToken');
                            localStorage.removeItem('currentUser');
                        } catch (e) {
                            console.error('Error clearing localStorage:', e);
                        }
                        window.location.href = basePath + '/index.php';
                    };
                }
            } catch (e) {}
        })();

        // ===================================
        // BUTTON ENHANCEMENTS - STATES & COLORS
        // ===================================
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.btn');

            buttons.forEach((btn) => {
                const label = (btn.textContent || '').trim().toLowerCase();
                const classes = btn.classList;

                // Skip if button already has a semantic variant
                const hasSemanticVariant = [
                    'btn-primary',
                    'btn-secondary',
                    'btn-success',
                    'btn-danger',
                    'btn-warning',
                    'btn-info'
                ].some((cls) => classes.contains(cls));

                if (!hasSemanticVariant) {
                    if (/delete|remove|trash|destroy|discard/.test(label)) {
                        classes.add('btn-danger');
                    } else if (/save|submit|register|create|add|update|confirm/.test(label)) {
                        classes.add('btn-success');
                    } else if (/cancel|close|back/.test(label)) {
                        classes.add('btn-secondary');
                    } else if (/warning|caution/.test(label)) {
                        classes.add('btn-warning');
                    } else if (/info|details|more info/.test(label)) {
                        classes.add('btn-info');
                    }
                }

                // Normalize disabled state for anchor-like buttons
                const explicitlyDisabled =
                    btn.hasAttribute('disabled') ||
                    classes.contains('disabled') ||
                    btn.getAttribute('aria-disabled') === 'true';

                if (explicitlyDisabled) {
                    if (!btn.hasAttribute('disabled') && btn.tagName !== 'BUTTON') {
                        btn.setAttribute('aria-disabled', 'true');
                        classes.add('is-disabled');
                        btn.tabIndex = -1;
                    }
                }
            });
        });
    </script>

    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
