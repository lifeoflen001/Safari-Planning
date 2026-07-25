import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const appShell = document.querySelector('.app-shell');
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('#sidebarToggle');
    const notificationButton = document.querySelector('#notificationButton');
    const notificationDropdown = document.querySelector('#notificationDropdown');
    const notificationCount = document.querySelector('#notificationCount');
    const markNotificationsRead = document.querySelector('#markNotificationsRead');
    const userMenuToggle = document.querySelector('#userMenuToggle');
    const userDropdown = document.querySelector('#userDropdown');
    const themeToggle = document.querySelector('#themeToggle');
    const fullscreenToggle = document.querySelector('#fullscreenToggle');
    const settingsLayout = document.querySelector('.settings-layout');
    const settingsNavToggle = document.querySelector('#settingsNavToggle');

    document.querySelectorAll('.safari-board-actions .button').forEach((button) => {
        if (!button.title) button.title = button.textContent.trim();
    });
    document.querySelectorAll('.access-actions > a.button-small').forEach((link) => {
        link.title = link.title || 'Manage access';
        link.setAttribute('aria-label', link.getAttribute('aria-label') || 'Manage access');
    });

    const updateLiveClocks = () => {
        document.querySelectorAll('[data-live-clock]').forEach((clock) => {
            const timezone = clock.dataset.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone;
            try {
                clock.textContent = new Intl.DateTimeFormat('en-GB', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                    timeZone: timezone,
                }).format(new Date());
            } catch {
                clock.textContent = new Intl.DateTimeFormat('en-GB', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                }).format(new Date());
            }
        });
    };

    updateLiveClocks();
    window.setInterval(updateLiveClocks, 1000);

    window.showToast = (message) => {
        let container = document.querySelector('#toast-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <div class="toast-check">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </div>
            <span>${message}</span>
        `;
        container.appendChild(toast);

        window.setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            window.setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    const setSidebarState = (collapsed) => {
        if (!appShell) return;

        const isMobile = window.innerWidth <= 820;

        appShell.classList.toggle('sidebar-collapsed', collapsed && !isMobile);
        localStorage.setItem('sidebar-collapsed', collapsed ? 'true' : 'false');

        if (sidebarToggle) {
            sidebarToggle.title = collapsed ? 'Show sidebar' : 'Hide sidebar';
            sidebarToggle.setAttribute('aria-label', collapsed ? 'Show sidebar' : 'Hide sidebar');
            sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarToggle.classList.toggle('active', collapsed);
        }
    };

    const storedCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (storedCollapsed && window.innerWidth > 820) {
        setSidebarState(true);
    }

    const isMobile = window.innerWidth <= 820;

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();

            if (window.innerWidth <= 820) {
                const isOpen = sidebar?.classList.toggle('sidebar-open') ?? false;
                sidebarToggle.classList.toggle('active', isOpen);
                sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            } else {
                setSidebarState(!appShell?.classList.contains('sidebar-collapsed'));
            }
        });
    }

    const setSettingsNavState = (collapsed) => {
        if (!settingsLayout || !settingsNavToggle || window.innerWidth <= 1024) return;

        settingsLayout.classList.toggle('settings-nav-collapsed', collapsed);
        settingsNavToggle.title = collapsed ? 'Expand settings navigation' : 'Collapse settings navigation';
        settingsNavToggle.setAttribute('aria-label', settingsNavToggle.title);
        settingsNavToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        localStorage.setItem('settings-nav-collapsed', collapsed ? 'true' : 'false');
    };

    if (settingsLayout && settingsNavToggle) {
        settingsLayout.querySelectorAll('.settings-nav-item').forEach((link) => {
            if (!link.title) link.title = link.textContent.trim();
            if (!link.getAttribute('aria-label')) link.setAttribute('aria-label', link.title);
        });
        setSettingsNavState(localStorage.getItem('settings-nav-collapsed') === 'true');
        settingsNavToggle.addEventListener('click', () => {
            setSettingsNavState(!settingsLayout.classList.contains('settings-nav-collapsed'));
        });
    }

    document.addEventListener('click', (e) => {
        if (window.innerWidth > 820) return;

        const isClickInsideSidebar = sidebar && sidebar.contains(e.target);
        const isClickOnToggle = sidebarToggle && sidebarToggle.contains(e.target);

        if (!isClickInsideSidebar && !isClickOnToggle) {
            sidebar?.classList.remove('sidebar-open');
            sidebarToggle?.classList.remove('active');
            sidebarToggle?.setAttribute('aria-expanded', 'false');
        }
    });

    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (window.innerWidth > 820) {
                sidebar?.classList.remove('sidebar-open');
                if (sidebarToggle) {
                    sidebarToggle.classList.remove('active');
                }
                const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                if (isCollapsed) {
                    appShell?.classList.add('sidebar-collapsed');
                    if (sidebarToggle) {
                        sidebarToggle.setAttribute('aria-expanded', 'false');
                        sidebarToggle.classList.add('active');
                    }
                } else {
                    appShell?.classList.remove('sidebar-collapsed');
                    if (sidebarToggle) {
                        sidebarToggle.setAttribute('aria-expanded', 'true');
                        sidebarToggle.classList.remove('active');
                    }
                }
            }
        }, 250);
    });

    sidebar?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => sidebar.classList.remove('sidebar-open'));
    });

    const closeMenus = () => {
        if (notificationDropdown && notificationButton) {
            notificationDropdown.hidden = true;
            notificationButton.setAttribute('aria-expanded', 'false');
        }

        if (userDropdown && userMenuToggle) {
            userDropdown.style.display = 'none';
            userMenuToggle.setAttribute('aria-expanded', 'false');
        }
    };

    const notificationCsrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const notificationList = document.querySelector('#notificationList');

    const updateNotificationCount = (count) => {
        if (!notificationCount) return;
        notificationCount.textContent = count > 99 ? '99+' : String(count);
        notificationCount.style.display = count > 0 ? 'grid' : 'none';
    };

    const renderNotifications = (notifications) => {
        if (!notificationList) return;
        notificationList.innerHTML = '';
        if (!notifications.length) {
            const empty = document.createElement('div');
            empty.className = 'notification-empty';
            empty.textContent = 'You are all caught up.';
            notificationList.appendChild(empty);
            return;
        }

        notifications.forEach((notification) => {
            const item = document.createElement('a');
            item.className = `notification-item ${notification.read ? '' : 'is-unread'}`.trim();
            item.href = notification.url || '/planner/bookings';
            item.dataset.notificationId = notification.id;

            const dot = document.createElement('span');
            dot.className = `notification-dot notification-dot-${['alert', 'blue', 'gold'].includes(notification.type) ? notification.type : 'blue'}`;
            const copy = document.createElement('span');
            const title = document.createElement('strong');
            title.textContent = notification.title;
            const body = document.createElement('small');
            body.textContent = notification.body;
            const time = document.createElement('em');
            time.textContent = notification.time;
            copy.append(title, body, time);
            item.append(dot, copy);
            notificationList.appendChild(item);
        });
    };

    const refreshNotifications = async () => {
        try {
            const response = await fetch('/api/notifications', { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const payload = await response.json();
            renderNotifications(payload.notifications || []);
            updateNotificationCount(Number(payload.unread_count || 0));
        } catch {
            // The shell keeps its last known notification state when polling is unavailable.
        }
    };

    const markNotificationRead = async (id = null) => {
        try {
            await fetch('/api/notifications/read', {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': notificationCsrf },
                body: JSON.stringify(id ? { id } : {}),
            });
        } finally {
            refreshNotifications();
        }
    };

    notificationButton?.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = notificationDropdown?.hidden ?? true;
        closeMenus();

        if (willOpen && notificationDropdown) {
            notificationDropdown.hidden = false;
            notificationButton.setAttribute('aria-expanded', 'true');
            refreshNotifications();
        }
    });

    userMenuToggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = userDropdown?.style.display !== 'block';
        closeMenus();

        if (willOpen && userDropdown) {
            userDropdown.style.display = 'block';
            userMenuToggle.setAttribute('aria-expanded', 'true');
        }
    });

    markNotificationsRead?.addEventListener('click', async () => {
        await markNotificationRead();
        window.showToast('Notifications marked as read');
    });

    notificationList?.addEventListener('click', (event) => {
        const item = event.target.closest('.notification-item');
        if (item?.dataset.notificationId) markNotificationRead(item.dataset.notificationId);
    });

    refreshNotifications();
    window.setInterval(refreshNotifications, 15000);

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.notification-wrap') && !event.target.closest('.user-menu-wrap')) {
            closeMenus();
        }
    });

    const updateDarkModeButton = (isDark) => {
        if (!themeToggle) return;

        themeToggle.title = isDark ? 'Use light mode' : 'Use dark mode';
        themeToggle.setAttribute('aria-label', themeToggle.title);
        const path = themeToggle.querySelector('svg path');

        if (path) {
            path.setAttribute('d', isDark
                ? 'M12 3v2m0 14v2M5.64 5.64l1.42 1.42m9.9 9.9 1.42 1.42M3 12h2m14 0h2M5.64 18.36l1.42-1.42m9.9-9.9 1.42-1.42M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z'
                : 'M20.5 14.5A8.5 8.5 0 0 1 9.5 3.6 8.5 8.5 0 1 0 20.5 14.5Z');
        }
    };

    const applySafariTheme = (theme) => {
        const selectedTheme = theme === 'green' ? 'green' : 'beige';
        const isGreen = selectedTheme === 'green';

        document.documentElement.classList.toggle('safari-green-theme', isGreen);
        document.documentElement.dataset.safariTheme = selectedTheme;
        localStorage.setItem('theme', selectedTheme);
        updateDarkModeButton(document.documentElement.classList.contains('dark-theme'));

        const themeSelect = document.querySelector('#themeSelect');
        if (themeSelect) themeSelect.value = selectedTheme;
    };

    window.setSafariTheme = applySafariTheme;

    const applyDarkMode = (isDark) => {
        document.documentElement.classList.toggle('dark-theme', isDark);
        localStorage.setItem('dark-mode', isDark ? 'true' : 'false');
        updateDarkModeButton(isDark);
    };

    const storedTheme = localStorage.getItem('theme') === 'green' ? 'green' : 'beige';
    const storedDarkMode = localStorage.getItem('dark-mode') === 'true' || localStorage.getItem('theme') === 'dark';
    applyDarkMode(storedDarkMode);
    applySafariTheme(storedTheme);

    themeToggle?.addEventListener('click', () => {
        applyDarkMode(!document.documentElement.classList.contains('dark-theme'));
    });

    document.querySelector('#themeSelect')?.addEventListener('change', (event) => {
        applySafariTheme(event.target.value);
        window.showToast(`Switched to ${event.target.value === 'green' ? 'Safari Green' : 'Safari Beige'}`);
    });

    const updateFullscreenButton = () => {
        if (!fullscreenToggle) return;

        const isFullscreen = Boolean(document.fullscreenElement);
        fullscreenToggle.title = isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen';
        fullscreenToggle.setAttribute('aria-label', fullscreenToggle.title);
    };

    fullscreenToggle?.addEventListener('click', async () => {
        try {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
            } else if (document.documentElement.requestFullscreen) {
                await document.documentElement.requestFullscreen();
            } else {
                window.showToast('Fullscreen is not available in this browser');
            }
        } catch {
            window.showToast('Fullscreen is not available in this browser');
        }
    });

    document.addEventListener('fullscreenchange', updateFullscreenButton);

    const globalSearchForm = document.querySelector('#globalSearchForm');
    globalSearchForm?.addEventListener('submit', (event) => {
        const searchInput = globalSearchForm.querySelector('input[name="q"]');
        if (!searchInput?.value.trim()) {
            event.preventDefault();
            searchInput?.focus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            const searchInput = document.querySelector('#globalSearch');
            searchInput?.focus();
            searchInput?.select();
        }

        if (event.key === 'Escape') {
            closeMenus();
            document.querySelectorAll('.modal-backdrop, .modal-overlay').forEach((overlay) => {
                overlay.style.display = 'none';
            });
        }
    });
});

window.openModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.style.display = 'grid';
    modal.setAttribute('aria-hidden', 'false');
    window.setTimeout(() => modal.querySelector('input, select, textarea')?.focus(), 0);
};

window.closeModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
};

window.previewAvatar = (inputId, previewId) => {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview || !input.files?.[0]) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="profile-avatar-img">`;
    };
    reader.readAsDataURL(input.files[0]);
};

const settingsCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const showSettingsFormError = (form, message) => {
    let error = form.querySelector('[role="alert"]');
    if (!error) {
        error = document.createElement('div');
        error.setAttribute('role', 'alert');
        error.className = 'settings-form-error';
        form.appendChild(error);
    }
    error.textContent = message;
    error.hidden = false;
};

const submitProfileForm = async (form) => {
    const submit = form.querySelector('button[type="submit"]');
    const originalLabel = submit?.textContent;
    if (submit) {
        submit.disabled = true;
        submit.textContent = 'Saving…';
    }

    try {
        const response = await fetch(form.action, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': settingsCsrfToken(),
            },
            body: new FormData(form),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Profile could not be saved.');
        form.querySelectorAll('[role="alert"]').forEach((element) => { element.hidden = true; });
        document.querySelectorAll('.profile-cover-copy h2').forEach((element) => { element.textContent = payload.user.name; });
        window.showToast(payload.message || 'Profile updated.');
    } catch (error) {
        showSettingsFormError(form, error.message);
    } finally {
        if (submit) {
            submit.disabled = false;
            submit.textContent = originalLabel;
        }
    }
};

const uploadProfileAvatar = async (form, input) => {
    const file = input?.files?.[0];
    const error = document.getElementById('avatarError');
    const preview = document.getElementById('profileAvatarPreview');
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
        if (error) {
            error.textContent = 'Choose a JPG, PNG or WebP image under 2 MB.';
            error.hidden = false;
        }
        input.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        if (preview) preview.innerHTML = `<img src="${event.target.result}" alt="Profile photo preview" class="profile-avatar-img">`;
    };
    reader.readAsDataURL(file);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': settingsCsrfToken() },
            body: new FormData(form),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Profile photo could not be saved.');
        if (preview && payload.avatar_url) preview.innerHTML = `<img src="${payload.avatar_url}" alt="Profile photo" class="profile-avatar-img">`;
        if (error) { error.textContent = ''; error.hidden = true; }
        window.showToast(payload.message || 'Profile photo updated.');
    } catch (uploadError) {
        showSettingsFormError(form, uploadError.message);
    } finally {
        input.value = '';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('profileForm');
    profileForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        submitProfileForm(profileForm);
    });

    const avatarForm = document.getElementById('avatarForm');
    const avatarInput = document.getElementById('avatarInput');
    if (avatarForm && avatarInput) {
        avatarInput.addEventListener('change', () => uploadProfileAvatar(avatarForm, avatarInput));
        window.handleAvatarUpload = (input) => uploadProfileAvatar(avatarForm, input);
    }
});
