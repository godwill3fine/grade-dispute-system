const canvas = document.getElementById("particle-canvas");
const themeToggleBtn = document.getElementById("theme-toggle");
const ctx = canvas ? canvas.getContext("2d") : null;
const particles = [];

function isDarkModeEnabled() {
    return document.body.classList.contains("dark-theme");
}

function applyTheme(isDarkMode) {
    document.body.classList.toggle("dark-theme", isDarkMode);

    if (themeToggleBtn) {
        themeToggleBtn.textContent = isDarkMode ? "light_mode" : "dark_mode";
    }
}

function getSavedTheme() {
    return localStorage.getItem("theme") === "dark";
}

function getParticleColor(opacity) {
    if (isDarkModeEnabled()) {
        return `rgba(148, 163, 184, ${opacity})`;
    }

    return `rgba(31, 60, 136, ${opacity})`;
}

applyTheme(getSavedTheme());

if (themeToggleBtn) {
    themeToggleBtn.addEventListener("click", () => {
        const isDarkMode = !isDarkModeEnabled();

        applyTheme(isDarkMode);
        localStorage.setItem("theme", isDarkMode ? "dark" : "light");
    });
}

function resizeCanvas() {
    if (!canvas) {
        return;
    }

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}

class Particle {
    constructor() {
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.vx = (Math.random() - 0.5) * 0.8;
        this.vy = (Math.random() - 0.5) * 0.8;
        this.radius = Math.random() * 2 + 1;
    }

    update() {
        if (this.x < 0 || this.x > canvas.width) {
            this.vx *= -1;
        }

        if (this.y < 0 || this.y > canvas.height) {
            this.vy *= -1;
        }

        this.x += this.vx;
        this.y += this.vy;
    }

    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        ctx.fillStyle = getParticleColor(0.3);
        ctx.fill();
    }
}

function createParticles() {
    particles.length = 0;

    for (let index = 0; index < 80; index += 1) {
        particles.push(new Particle());
    }
}

function animate() {
    if (!canvas || !ctx) {
        return;
    }

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    for (let firstIndex = 0; firstIndex < particles.length; firstIndex += 1) {
        particles[firstIndex].update();
        particles[firstIndex].draw();

        for (let secondIndex = firstIndex; secondIndex < particles.length; secondIndex += 1) {
            const deltaX = particles[firstIndex].x - particles[secondIndex].x;
            const deltaY = particles[firstIndex].y - particles[secondIndex].y;
            const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

            if (distance < 140) {
                const opacity = 0.2 - (distance / 140) * 0.2;

                ctx.beginPath();
                ctx.strokeStyle = getParticleColor(opacity);
                ctx.lineWidth = 1;
                ctx.moveTo(particles[firstIndex].x, particles[firstIndex].y);
                ctx.lineTo(particles[secondIndex].x, particles[secondIndex].y);
                ctx.stroke();
            }
        }
    }

    requestAnimationFrame(animate);
}

if (canvas && ctx) {
    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();
    createParticles();
    animate();
}
