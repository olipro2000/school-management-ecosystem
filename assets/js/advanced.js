// Advanced Features & Functionality

class AdvancedSMS {
    constructor() {
        this.init();
        this.createParticles();
        this.initAdvancedFeatures();
    }

    init() {
        // Initialize advanced components
        this.initDarkMode();
        this.initRealTimeUpdates();
        this.initAdvancedCharts();
        this.initVoiceCommands();
        this.initKeyboardShortcuts();
    }

    // Particle Background System
    createParticles() {
        const particleContainer = document.createElement('div');
        particleContainer.className = 'particle-bg';
        document.body.appendChild(particleContainer);

        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.width = Math.random() * 4 + 2 + 'px';
            particle.style.height = particle.style.width;
            particle.style.animationDelay = Math.random() * 6 + 's';
            particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
            particleContainer.appendChild(particle);
        }
    }

    // Advanced Notification System
    showAdvancedNotification(title, message, type = 'info', actions = []) {
        const notification = document.createElement('div');
        notification.className = `notification-toast ${type}`;
        
        notification.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="notification-icon me-3">
                    <i class="fas fa-${this.getNotificationIcon(type)} fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${title}</h6>
                    <p class="mb-2 small">${message}</p>
                    <div class="notification-actions">
                        ${actions.map(action => `
                            <button class="btn btn-sm btn-outline-primary me-2" onclick="${action.callback}">
                                ${action.label}
                            </button>
                        `).join('')}
                    </div>
                </div>
                <button class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 5000);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };
        return icons[type] || 'bell';
    }

    // Real-time Updates
    initRealTimeUpdates() {
        // Real-time updates handled by SSE in header.php
    }

    // Advanced Charts
    initAdvancedCharts() {
        this.createAttendanceChart();
        this.createPaymentChart();
        this.createPerformanceChart();
    }

    createAttendanceChart() {
        const canvas = document.getElementById('attendanceChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(102, 126, 234, 0.8)');
        gradient.addColorStop(1, 'rgba(118, 75, 162, 0.1)');

        // Simulate chart data
        this.drawAdvancedChart(ctx, gradient);
    }

    drawAdvancedChart(ctx, gradient) {
        const data = [65, 78, 82, 75, 88, 92, 85, 90];
        const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        
        ctx.fillStyle = gradient;
        ctx.strokeStyle = '#667eea';
        ctx.lineWidth = 3;
        
        // Draw animated chart
        this.animateChart(ctx, data, labels);
    }

    animateChart(ctx, data, labels) {
        let progress = 0;
        const animate = () => {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            
            const currentData = data.map(value => value * progress);
            this.drawChartBars(ctx, currentData, labels);
            
            progress += 0.02;
            if (progress <= 1) {
                requestAnimationFrame(animate);
            }
        };
        animate();
    }

    drawChartBars(ctx, data, labels) {
        const barWidth = ctx.canvas.width / data.length;
        const maxValue = Math.max(...data);
        
        data.forEach((value, index) => {
            const barHeight = (value / maxValue) * (ctx.canvas.height - 50);
            const x = index * barWidth + 10;
            const y = ctx.canvas.height - barHeight - 30;
            
            // Draw bar with gradient
            const gradient = ctx.createLinearGradient(0, y, 0, y + barHeight);
            gradient.addColorStop(0, '#667eea');
            gradient.addColorStop(1, '#764ba2');
            
            ctx.fillStyle = gradient;
            ctx.fillRect(x, y, barWidth - 20, barHeight);
            
            // Draw label
            ctx.fillStyle = '#333';
            ctx.font = '12px Inter';
            ctx.textAlign = 'center';
            ctx.fillText(labels[index], x + (barWidth - 20) / 2, ctx.canvas.height - 10);
        });
    }

    // Voice Commands
    initVoiceCommands() {
        if ('webkitSpeechRecognition' in window) {
            const recognition = new webkitSpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-US';

            const voiceBtn = document.createElement('button');
            voiceBtn.className = 'fab voice-command';
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            voiceBtn.style.bottom = '100px';
            voiceBtn.onclick = () => this.startVoiceCommand(recognition);
            
            document.body.appendChild(voiceBtn);
        }
    }

    startVoiceCommand(recognition) {
        recognition.start();
        this.showAdvancedNotification('Voice Command', 'Listening... Say a command', 'info');
        
        recognition.onresult = (event) => {
            const command = event.results[0][0].transcript.toLowerCase();
            this.processVoiceCommand(command);
        };
    }

    processVoiceCommand(command) {
        const commands = {
            'show dashboard': () => window.location.href = 'dashboard.php',
            'show students': () => window.location.href = 'views/students.php',
            'show payments': () => window.location.href = 'views/payments.php',
            'logout': () => window.location.href = 'controllers/auth.php?logout=1'
        };

        const matchedCommand = Object.keys(commands).find(cmd => command.includes(cmd));
        if (matchedCommand) {
            commands[matchedCommand]();
            this.showAdvancedNotification('Voice Command', `Executing: ${matchedCommand}`, 'success');
        } else {
            this.showAdvancedNotification('Voice Command', 'Command not recognized', 'error');
        }
    }

    // Keyboard Shortcuts
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'd':
                        e.preventDefault();
                        window.location.href = 'dashboard.php';
                        break;
                    case 's':
                        e.preventDefault();
                        window.location.href = 'views/students.php';
                        break;
                    case 'p':
                        e.preventDefault();
                        window.location.href = 'views/payments.php';
                        break;
                    case 'k':
                        e.preventDefault();
                        this.showKeyboardShortcuts();
                        break;
                }
            }
        });
    }

    showKeyboardShortcuts() {
        const modal = document.createElement('div');
        modal.className = 'modal fade advanced-modal';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Keyboard Shortcuts</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6"><kbd>Ctrl + D</kbd></div>
                            <div class="col-6">Dashboard</div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6"><kbd>Ctrl + S</kbd></div>
                            <div class="col-6">Students</div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6"><kbd>Ctrl + P</kbd></div>
                            <div class="col-6">Payments</div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6"><kbd>Ctrl + K</kbd></div>
                            <div class="col-6">Show Shortcuts</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        new bootstrap.Modal(modal).show();
    }

    // Dark Mode
    initDarkMode() {
        const toggle = document.createElement('div');
        toggle.className = 'dark-mode-toggle';
        toggle.style.position = 'fixed';
        toggle.style.top = '80px';
        toggle.style.right = '20px';
        toggle.style.zIndex = '1001';
        toggle.innerHTML = '<i class="fas fa-moon"></i>';
        
        // Load saved theme
        const savedTheme = localStorage.getItem('darkMode');
        if (savedTheme === 'true') {
            document.body.classList.add('dark-mode');
            toggle.classList.add('active');
            toggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        
        toggle.onclick = () => this.toggleDarkMode(toggle);
        document.body.appendChild(toggle);
    }

    toggleDarkMode(toggle) {
        document.body.classList.toggle('dark-mode');
        toggle.classList.toggle('active');
        
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark);
        
        // Update toggle icon
        toggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        
        this.showAdvancedNotification(
            'Theme Changed', 
            `Switched to ${isDark ? 'dark' : 'light'} mode`, 
            'success'
        );
    }

    // Advanced Search with AI-like suggestions
    initAdvancedSearch() {
        const searchInputs = document.querySelectorAll('.search-input');
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.showSearchSuggestions(e.target);
            });
        });
    }

    showSearchSuggestions(input) {
        const query = input.value.toLowerCase();
        if (query.length < 2) return;

        const suggestions = this.getSearchSuggestions(query);
        this.displaySuggestions(input, suggestions);
    }

    getSearchSuggestions(query) {
        const data = [
            'John Doe - Student',
            'Math Class - Grade 10',
            'Payment Receipt #1234',
            'Library Book: Physics',
            'Teacher: Sarah Johnson'
        ];
        
        return data.filter(item => 
            item.toLowerCase().includes(query)
        ).slice(0, 5);
    }

    displaySuggestions(input, suggestions) {
        let dropdown = input.parentNode.querySelector('.search-suggestions');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'search-suggestions';
            dropdown.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                z-index: 1000;
                max-height: 200px;
                overflow-y: auto;
            `;
            input.parentNode.appendChild(dropdown);
        }

        dropdown.innerHTML = suggestions.map(suggestion => `
            <div class="suggestion-item p-2 border-bottom" style="cursor: pointer;">
                ${suggestion}
            </div>
        `).join('');

        dropdown.querySelectorAll('.suggestion-item').forEach(item => {
            item.onclick = () => {
                input.value = item.textContent.trim();
                dropdown.remove();
            };
        });
    }

    // Number animation utility
    animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        
        const updateNumber = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(start + (end - start) * this.easeOutQuart(progress));
            
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        };
        
        requestAnimationFrame(updateNumber);
    }

    easeOutQuart(t) {
        return 1 - (--t) * t * t * t;
    }

    // Progress Ring Animation
    createProgressRing(element, percentage) {
        const radius = 45;
        const circumference = 2 * Math.PI * radius;
        const offset = circumference - (percentage / 100) * circumference;
        
        element.style.strokeDasharray = circumference;
        element.style.strokeDashoffset = offset;
    }

    // Advanced Table Features
    initAdvancedTable(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        // Add sorting
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.onclick = () => this.sortTable(table, index);
        });

        // Add row selection
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.onclick = () => row.classList.toggle('selected');
        });
    }

    sortTable(table, columnIndex) {
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const isAscending = table.dataset.sortOrder !== 'asc';
        
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            if (isAscending) {
                return aValue.localeCompare(bValue, undefined, { numeric: true });
            } else {
                return bValue.localeCompare(aValue, undefined, { numeric: true });
            }
        });

        const tbody = table.querySelector('tbody');
        rows.forEach(row => tbody.appendChild(row));
        
        table.dataset.sortOrder = isAscending ? 'asc' : 'desc';
    }
}

// Initialize Advanced Features
document.addEventListener('DOMContentLoaded', () => {
    window.advancedSMS = new AdvancedSMS();
});

// Export for global use
window.AdvancedSMS = AdvancedSMS;