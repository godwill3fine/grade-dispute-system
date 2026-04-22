const wrapper = document.querySelector(".wrapper");
const toggleBtn = document.getElementById("toggle-sidebar");
const themeToggle = document.getElementById("theme-toggle");
const themeToggleIcon = themeToggle?.querySelector("[data-theme-icon]");
const mobileMenu = document.getElementById("mobile-menu");
const overlay = document.querySelector(".overlay");
const DESKTOP_BREAKPOINT = 1100;
const SIDEBAR_STATE_KEY = "portal-sidebar-collapsed";

function isDesktopViewport() {
    return window.innerWidth > DESKTOP_BREAKPOINT;
}

function setMobileSidebarOpen(isOpen) {
    if (!wrapper) {
        return;
    }

    wrapper.classList.toggle("mobile-open", isOpen);
}

function applyTheme(isDarkMode) {
    document.body.classList.toggle("dark-theme", isDarkMode);

    if (themeToggleIcon) {
        themeToggleIcon.textContent = isDarkMode ? "light_mode" : "dark_mode";
    }
}

function readSavedTheme() {
    return localStorage.getItem("theme") === "dark";
}

function shouldCollapseSidebar() {
    return isDesktopViewport();
}

function readSavedSidebarState() {
    return localStorage.getItem(SIDEBAR_STATE_KEY) === "true";
}

function applySidebarState(isCollapsed) {
    if (!wrapper) {
        return;
    }

    wrapper.classList.toggle("collapsed", shouldCollapseSidebar() && isCollapsed);
}

applyTheme(readSavedTheme());
applySidebarState(readSavedSidebarState());

if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        const isDarkMode = !document.body.classList.contains("dark-theme");

        applyTheme(isDarkMode);
        localStorage.setItem("theme", isDarkMode ? "dark" : "light");
    });
}

if (toggleBtn && wrapper) {
    toggleBtn.addEventListener("click", () => {
        if (!isDesktopViewport()) {
            setMobileSidebarOpen(!wrapper.classList.contains("mobile-open"));
            return;
        }

        const isCollapsed = !wrapper.classList.contains("collapsed");

        localStorage.setItem(SIDEBAR_STATE_KEY, String(isCollapsed));
        applySidebarState(isCollapsed);
    });
}

if (mobileMenu && wrapper) {
    mobileMenu.addEventListener("click", () => {
        setMobileSidebarOpen(!wrapper.classList.contains("mobile-open"));
    });
}

if (overlay && wrapper) {
    overlay.addEventListener("click", () => {
        setMobileSidebarOpen(false);
    });
}

const settingsDropdownWrappers = document.querySelectorAll(".settings-dropdown-wrapper");

function closeSettingsMenus() {
    settingsDropdownWrappers.forEach((wrapperEl) => {
        wrapperEl.querySelector(".settings-dropdown-menu")?.classList.remove("show");
        wrapperEl.querySelector(".settings-trigger")?.setAttribute("aria-expanded", "false");
    });
}

if (settingsDropdownWrappers.length > 0) {
    settingsDropdownWrappers.forEach((wrapperEl) => {
        const settingsIcon = wrapperEl.querySelector(".settings-trigger");
        const settingsDropdown = wrapperEl.querySelector(".settings-dropdown-menu");

        if (!settingsIcon || !settingsDropdown) {
            return;
        }

        settingsIcon.addEventListener("click", (event) => {
            event.stopPropagation();

            const willOpen = !settingsDropdown.classList.contains("show");

            closeSettingsMenus();

            if (willOpen) {
                settingsDropdown.classList.add("show");
                settingsIcon.setAttribute("aria-expanded", "true");
            }
        });
    });

    document.addEventListener("click", (event) => {
        settingsDropdownWrappers.forEach((wrapperEl) => {
            if (!wrapperEl.contains(event.target)) {
                wrapperEl.querySelector(".settings-dropdown-menu")?.classList.remove("show");
                wrapperEl.querySelector(".settings-trigger")?.setAttribute("aria-expanded", "false");
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeSettingsMenus();
        }
    });
}

window.addEventListener("resize", () => {
    applySidebarState(readSavedSidebarState());

    if (isDesktopViewport()) {
        setMobileSidebarOpen(false);
    }
});
