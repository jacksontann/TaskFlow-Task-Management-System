/**
 * TaskFlow - Main JavaScript
 * Handles sidebar toggle, confirmations, timer, and utility functions.
 */

// =============================================
// SIDEBAR TOGGLE (mobile)
// =============================================
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }

    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function (msg) {
        setTimeout(function () {
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-10px)';
            setTimeout(function () { msg.remove(); }, 300);
        }, 5000);
    });
});

// =============================================
// CONFIRM DELETE
// =============================================
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this? This action cannot be undone.');
}

// =============================================
// TIMER FUNCTIONALITY
// =============================================
let timerInterval = null;
let timerSeconds = 0;
let timerRunning = false;

/**
 * Start the timer - updates display every second
 */
function startTimer() {
    if (timerRunning) return;
    timerRunning = true;

    const display = document.getElementById('timerDisplay');
    const startBtn = document.getElementById('timerStart');
    const stopBtn = document.getElementById('timerStop');

    if (startBtn) startBtn.disabled = true;
    if (stopBtn) stopBtn.disabled = false;

    timerInterval = setInterval(function () {
        timerSeconds++;
        if (display) {
            display.textContent = formatTimer(timerSeconds);
        }
    }, 1000);
}

/**
 * Stop the timer
 */
function stopTimer() {
    timerRunning = false;
    clearInterval(timerInterval);

    const startBtn = document.getElementById('timerStart');
    const stopBtn = document.getElementById('timerStop');

    if (startBtn) startBtn.disabled = false;
    if (stopBtn) stopBtn.disabled = true;

    return timerSeconds;
}

/**
 * Reset the timer
 */
function resetTimer() {
    stopTimer();
    timerSeconds = 0;
    const display = document.getElementById('timerDisplay');
    if (display) display.textContent = '00:00:00';
}

/**
 * Format seconds into HH:MM:SS
 */
function formatTimer(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return [hours, minutes, seconds]
        .map(function (v) { return v.toString().padStart(2, '0'); })
        .join(':');
}

// =============================================
// CHART.JS HELPERS
// =============================================

/**
 * Create a common Chart.js config with dark theme
 */
function createChartConfig(type, labels, datasets, options) {
    // Default dark theme colors
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#a0a0b0',
                    font: { family: 'Inter', size: 12 }
                }
            }
        },
        scales: {}
    };

    // Add axis colors for bar/line charts
    if (type === 'bar' || type === 'line') {
        defaultOptions.scales = {
            x: {
                ticks: { color: '#a0a0b0', font: { family: 'Inter' } },
                grid: { color: 'rgba(42, 42, 64, 0.5)' }
            },
            y: {
                ticks: { color: '#a0a0b0', font: { family: 'Inter' } },
                grid: { color: 'rgba(42, 42, 64, 0.5)' },
                beginAtZero: true
            }
        };
    }

    // Merge custom options
    const merged = Object.assign({}, defaultOptions, options || {});

    return {
        type: type,
        data: { labels: labels, datasets: datasets },
        options: merged
    };
}

// Accent colors for charts
const chartColors = {
    purple: 'rgba(108, 99, 255, 0.8)',
    purpleLight: 'rgba(108, 99, 255, 0.2)',
    green: 'rgba(0, 200, 150, 0.8)',
    greenLight: 'rgba(0, 200, 150, 0.2)',
    orange: 'rgba(255, 179, 71, 0.8)',
    orangeLight: 'rgba(255, 179, 71, 0.2)',
    red: 'rgba(255, 107, 107, 0.8)',
    redLight: 'rgba(255, 107, 107, 0.2)',
    blue: 'rgba(78, 205, 196, 0.8)',
    blueLight: 'rgba(78, 205, 196, 0.2)',
    pink: 'rgba(168, 85, 247, 0.8)',
    pinkLight: 'rgba(168, 85, 247, 0.2)',
};
