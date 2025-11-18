document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar .nav');
    const mobileMenuContent = document.getElementById('mobileMenuContent');
    const role = sidebar?.getAttribute('data-role');
    
    if (!sidebar || !mobileMenuContent || !role) return;
    
    const menuStructure = {
        admin: {
            'Users': ['users.php', 'students.php', 'teachers.php', 'parents.php'],
            'Academic': ['classes.php', 'subjects.php', 'class_promotion.php'],
            'Finance': ['payments.php', 'fee_settings.php'],
            'Communication': ['announcements.php'],
            'System': []
        },
        teacher: {
            'Teaching': ['attendance.php', 'grades.php', 'class_reports.php'],
            'Communication': ['announcements.php'],
            'My Account': [],
            'Help': [],
            'Settings': []
        },
        parent: {
            'Fees': ['fee_balance.php', 'submit_payment.php'],
            'Children': [],
            'Reports': [],
            'Help': [],
            'Settings': []
        },
        accountant: {
            'Payments': ['payments.php', 'verify_payments.php'],
            'Salaries': ['salaries.php'],
            'Reports': [],
            'Help': [],
            'Settings': []
        },
        librarian: {
            'Library': ['books.php', 'library_records.php'],
            'Reports': [],
            'Help': [],
            'Settings': []
        },
        student: {
            'Academics': ['my_grades.php', 'announcements.php'],
            'My Info': [],
            'Help': [],
            'Settings': []
        }
    };
    
    const allItems = Array.from(sidebar.querySelectorAll('.nav-item:not(.mobile-nav-item)'));
    const itemMap = {};
    
    allItems.forEach(item => {
        const link = item.querySelector('.nav-link');
        if (link) {
            const href = link.getAttribute('href');
            const filename = href.split('/').pop();
            itemMap[filename] = {
                icon: link.querySelector('i')?.outerHTML || '',
                text: link.textContent.trim(),
                href: href,
                active: link.classList.contains('active')
            };
        }
    });
    
    const categories = menuStructure[role] || {};
    let menuHTML = '<div class="accordion accordion-flush" id="mobileMenuAccordion">';
    
    Object.keys(categories).forEach((category, index) => {
        const files = categories[category];
        const categoryItems = files.map(f => itemMap[f]).filter(Boolean);
        
        if (categoryItems.length > 0) {
            menuHTML += `
                <div class="accordion-item" style="border: none; margin-bottom: 0.5rem;">
                    <h2 class="accordion-header">
                        <button class="accordion-button ${index !== 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border-radius: 0.75rem; font-weight: 600; padding: 1rem;">
                            ${category}
                        </button>
                    </h2>
                    <div id="collapse${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#mobileMenuAccordion">
                        <div class="accordion-body" style="padding: 0.5rem 0;">
            `;
            
            categoryItems.forEach(item => {
                menuHTML += `
                    <a href="${item.href}" class="d-block p-3 ${item.active ? 'bg-primary text-white' : ''}" style="border-radius: 0.5rem; margin-bottom: 0.25rem; text-decoration: none; color: inherit; transition: all 0.2s;">
                        ${item.icon}
                        <span class="ms-2">${item.text}</span>
                    </a>
                `;
            });
            
            menuHTML += `
                        </div>
                    </div>
                </div>
            `;
        }
    });
    
    menuHTML += '</div>';
    mobileMenuContent.innerHTML = menuHTML;
});
