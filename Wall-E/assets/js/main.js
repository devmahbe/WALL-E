// Add active class to current navigation link
document.addEventListener('DOMContentLoaded', () => {
    // Initialize loading bar
    const loadingBar = document.getElementById('loadingBar');
    
    // Show loading bar during page transitions
    window.addEventListener('beforeunload', () => {
        loadingBar.style.display = 'block';
    });

    // Add active class to current navigation link
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href').endsWith(currentPath)) {
            link.classList.add('active');
            link.classList.add('animate__animated');
            link.classList.add('animate__pulse');
        }
    });

    // Add animation to cards
    const cards = document.querySelectorAll('.card');
    const animateCard = (card) => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    card.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(card);
                }
            });
        }, { threshold: 0.1 });
        
        observer.observe(card);
    };

    cards.forEach(animateCard);

    // Add hover effect to reading items
    const readingItems = document.querySelectorAll('.reading-item');
    readingItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.classList.add('animate__animated', 'animate__pulse');
        });
        
        item.addEventListener('mouseleave', () => {
            item.classList.remove('animate__animated', 'animate__pulse');
        });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Auto-refresh with loading animation for data pages
    if (currentPath.includes('view_data.php') || 
        currentPath.includes('environment_status.php') || 
        currentPath.includes('graphs.php')) {
        
        const refreshData = () => {
            loadingBar.style.display = 'block';
            setTimeout(() => {
                window.location.reload();
            }, 500);
        };

        setInterval(refreshData, 30000);
    }

    // Confirm deletions with custom modal
    document.querySelectorAll('.delete-action').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const confirmDelete = confirm('Are you sure you want to delete this entry?');
            if (confirmDelete) {
                loadingBar.style.display = 'block';
                window.location.href = link.href;
            }
        });
    });

    // Ripple Effect for Buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn')) {
            const button = e.target;
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            button.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        }
    });

    // Tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        const tooltip = document.createElement('div');
        tooltip.classList.add('tooltip');
        document.body.appendChild(tooltip);

        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', e => {
                const text = element.getAttribute('data-tooltip');
                tooltip.textContent = text;
                tooltip.classList.add('show');

                const rect = element.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();
                
                let left = rect.left + (rect.width - tooltipRect.width) / 2;
                let top = rect.top - tooltipRect.height - 10;

                // Keep tooltip within viewport
                if (left < 10) left = 10;
                if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = window.innerWidth - tooltipRect.width - 10;
                }
                if (top < 10) {
                    top = rect.bottom + 10;
                }

                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
            });

            element.addEventListener('mouseleave', () => {
                tooltip.classList.remove('show');
            });
        });
    });

    // Loading Bar Animation
    function showLoadingBar() {
        const loadingBar = document.createElement('div');
        loadingBar.classList.add('loading');
        loadingBar.style.height = '3px';
        loadingBar.style.width = '100%';
        loadingBar.style.position = 'fixed';
        loadingBar.style.top = '0';
        loadingBar.style.left = '0';
        loadingBar.style.zIndex = '9999';
        document.body.appendChild(loadingBar);
        return loadingBar;
    }

    function hideLoadingBar(loadingBar) {
        loadingBar.style.opacity = '0';
        setTimeout(() => {
            loadingBar.remove();
        }, 300);
    }

    // Form Submission Loading State
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const loadingBar = showLoadingBar();
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    const originalText = submitButton.textContent;
                    submitButton.textContent = 'Processing...';
                    submitButton.disabled = true;

                    // Simulate form submission delay (remove in production)
                    setTimeout(() => {
                        hideLoadingBar(loadingBar);
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                    }, 2000);
                }
            });
        });
    });
}); 