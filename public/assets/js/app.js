document.addEventListener('DOMContentLoaded', function() {
    const appShell = document.querySelector('.app-shell');
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileCloseBtn = document.getElementById('mobileCloseBtn');
    const isMobile = window.innerWidth <= 820;

    // ============================================
    // SIDEBAR TOGGLE (Desktop & Mobile)
    // ============================================
    
    // Restore sidebar state from localStorage
    function restoreSidebarState() {
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed && window.innerWidth > 820) {
            appShell.classList.add('sidebar-collapsed');
            if (sidebarToggle) {
                sidebarToggle.setAttribute('aria-expanded', 'false');
                sidebarToggle.classList.add('active');
            }
        }
    }
    restoreSidebarState();

    // Toggle sidebar (desktop - collapse/expand)
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            
            if (window.innerWidth <= 820) {
                // Mobile: Open sidebar
                sidebar.classList.add('sidebar-open');
                this.style.display = 'none';
                if (mobileCloseBtn) {
                    mobileCloseBtn.style.display = 'inline-flex';
                }
                this.setAttribute('aria-expanded', 'true');
            } else {
                // Desktop: Collapse/Expand
                appShell.classList.toggle('sidebar-collapsed');
                const isCollapsed = appShell.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed);
                this.setAttribute('aria-expanded', !isCollapsed);
                this.classList.toggle('active', isCollapsed);
                
                // Update title
                this.title = isCollapsed ? 'Show sidebar' : 'Hide sidebar';
            }
        });
    }

    // Mobile close button (X)
    if (mobileCloseBtn) {
        mobileCloseBtn.addEventListener('click', function() {
            sidebar.classList.remove('sidebar-open');
            this.style.display = 'none';
            if (sidebarToggle) {
                sidebarToggle.style.display = 'inline-flex';
                sidebarToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Close mobile sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 820) {
            const isClickInsideSidebar = sidebar && sidebar.contains(e.target);
            const isClickOnToggle = sidebarToggle && sidebarToggle.contains(e.target);
            const isClickOnClose = mobileCloseBtn && mobileCloseBtn.contains(e.target);
            
            if (!isClickInsideSidebar && !isClickOnToggle && !isClickOnClose) {
                sidebar.classList.remove('sidebar-open');
                if (mobileCloseBtn) {
                    mobileCloseBtn.style.display = 'none';
                }
                if (sidebarToggle) {
                    sidebarToggle.style.display = 'inline-flex';
                }
            }
        }
    });

    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const width = window.innerWidth;
            
            if (width > 820) {
                // Desktop: Close mobile menu if open
                sidebar.classList.remove('sidebar-open');
                if (mobileCloseBtn) {
                    mobileCloseBtn.style.display = 'none';
                }
                if (sidebarToggle) {
                    sidebarToggle.style.display = 'inline-flex';
                }
                
                // Restore desktop state
                const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                if (isCollapsed) {
                    appShell.classList.add('sidebar-collapsed');
                    if (sidebarToggle) {
                        sidebarToggle.setAttribute('aria-expanded', 'false');
                        sidebarToggle.classList.add('active');
                    }
                } else {
                    appShell.classList.remove('sidebar-collapsed');
                    if (sidebarToggle) {
                        sidebarToggle.setAttribute('aria-expanded', 'true');
                        sidebarToggle.classList.remove('active');
                    }
                }
            }
        }, 250);
    });

    // ============================================
    // USER DROPDOWN
    // ============================================
    
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = userDropdown.style.display === 'block';
            userDropdown.style.display = isOpen ? 'none' : 'block';
            this.setAttribute('aria-expanded', !isOpen);
        });
        
        document.addEventListener('click', function() {
            userDropdown.style.display = 'none';
            if (userMenuToggle) {
                userMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });
        
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // ============================================
    // NOTIFICATIONS
    // ============================================
    
    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationButton && notificationDropdown) {
        notificationButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = !notificationDropdown.hidden;
            notificationDropdown.hidden = isOpen;
            this.setAttribute('aria-expanded', !isOpen);
        });
        
        document.addEventListener('click', function() {
            notificationDropdown.hidden = true;
            if (notificationButton) {
                notificationButton.setAttribute('aria-expanded', 'false');
            }
        });
        
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // ============================================
    // THEME TOGGLE
    // ============================================
    
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark-theme');
            const isDark = document.documentElement.classList.contains('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            
            // Update icon
            const svg = this.querySelector('svg path');
            if (isDark) {
                svg.setAttribute('d', 'M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6 7 7m10 10 1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4M15.5 12a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z');
                this.title = 'Use light theme';
            } else {
                svg.setAttribute('d', 'M20.5 14.5A8.5 8.5 0 0 1 9.5 3.6 8.5 8.5 0 1 0 20.5 14.5Z');
                this.title = 'Use dark theme';
            }
        });
        
        // Restore theme
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-theme');
            const svg = themeToggle.querySelector('svg path');
            svg.setAttribute('d', 'M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6 7 7m10 10 1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4M15.5 12a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z');
            themeToggle.title = 'Use light theme';
        }
    }

    // ============================================
    // FULLSCREEN TOGGLE
    // ============================================
    
    const fullscreenToggle = document.getElementById('fullscreenToggle');
    if (fullscreenToggle) {
        fullscreenToggle.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
                this.title = 'Exit fullscreen';
            } else {
                document.exitFullscreen().catch(() => {});
                this.title = 'Enter fullscreen';
            }
        });
        
        document.addEventListener('fullscreenchange', function() {
            if (fullscreenToggle) {
                fullscreenToggle.title = document.fullscreenElement ? 'Exit fullscreen' : 'Enter fullscreen';
            }
        });
    }

    // ============================================
    // KEYBOARD SHORTCUTS
    // ============================================
    
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K = focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape = close dropdowns
        if (e.key === 'Escape') {
            if (userDropdown) userDropdown.style.display = 'none';
            if (notificationDropdown) notificationDropdown.hidden = true;
            
            // Close mobile sidebar
            if (window.innerWidth <= 820 && sidebar) {
                sidebar.classList.remove('sidebar-open');
                if (mobileCloseBtn) mobileCloseBtn.style.display = 'none';
                if (sidebarToggle) sidebarToggle.style.display = 'inline-flex';
            }
        }
    });

    // ============================================
    // GLOBAL SEARCH
    // ============================================
    
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && this.value.trim()) {
                showToast(`Searching for "${this.value.trim()}"`);
            }
        });
    }

    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    
    window.showToast = function(message) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:1000;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.style.cssText = 'background:#1a1d23;color:#fff;padding:12px 20px;border-radius:8px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;box-shadow:0 4px 16px rgba(0,0,0,0.15);animation:slideUp 0.3s ease;';
        toast.innerHTML = `
            <div style="width:20px;height:20px;border-radius:50%;background:#4c8972;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </div>
            ${message}
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    console.log('Safari Planner initialized successfully');
});

window.openModal = function(id) {
    var modal = document.getElementById(id);
    if (modal) modal.style.display = 'grid';
};

window.closeModal = function(id) {
    var modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
};

window.handleAvatarUpload = function(input) {
    var preview = document.getElementById('profileAvatarPreview');
    var errorEl = document.getElementById('avatarError');
    var form = document.getElementById('avatarForm');
    
    if (errorEl) {
        errorEl.style.display = 'none';
        errorEl.textContent = '';
    }
    
    if (!input || !input.files || !input.files[0]) {
        return;
    }

    var file = input.files[0];
    
    if (!file.type.startsWith('image/')) {
        if (errorEl) {
            errorEl.textContent = 'Please select a valid image file.';
            errorEl.style.display = 'block';
        }
        return;
    }
    
    try {
        var reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="profile-avatar-img">';
            }
            if (form) {
                form.submit();
            }
        };
        reader.onerror = function() {
            if (errorEl) {
                errorEl.textContent = 'Could not read this image. Please try another file or format.';
                errorEl.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    } catch (e) {
        if (errorEl) {
            errorEl.textContent = 'This model does not support image input. Please try a different image.';
            errorEl.style.display = 'block';
        }
    }
};

window.previewAvatar = function(inputId, previewId) {
    var input = document.getElementById(inputId);
    var preview = document.getElementById(previewId);
    if (!input || !preview || !input.files || !input.files[0]) return;

    try {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="profile-avatar-img">';
        };
        reader.readAsDataURL(input.files[0]);
    } catch (e) {
        console.error('Could not preview image:', e);
    }
};