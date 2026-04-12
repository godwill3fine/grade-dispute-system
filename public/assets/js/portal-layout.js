const wrapper = document.querySelector(".wrapper");
const toggleBtn = document.getElementById("toggle-sidebar");
const themeToggle = document.getElementById("theme-toggle");
const themeToggleIcon = themeToggle?.querySelector("[data-theme-icon]");
const mobileMenu = document.getElementById("mobile-menu");
const overlay = document.querySelector(".overlay");
const DESKTOP_BREAKPOINT = 1100;
const SIDEBAR_STATE_KEY = "portal-sidebar-collapsed";

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
    return window.innerWidth > DESKTOP_BREAKPOINT;
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
        const isCollapsed = !wrapper.classList.contains("collapsed");

        localStorage.setItem(SIDEBAR_STATE_KEY, String(isCollapsed));
        applySidebarState(isCollapsed);
    });
}

if (mobileMenu && wrapper) {
    mobileMenu.addEventListener("click", () => {
        wrapper.classList.toggle("mobile-open");
    });
}

if (overlay && wrapper) {
    overlay.addEventListener("click", () => {
        wrapper.classList.remove("mobile-open");
    });
}

window.addEventListener("resize", () => {
    applySidebarState(readSavedSidebarState());
});
