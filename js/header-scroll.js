/**
 * Header Scroll Behavior
 * Adds scrolled class to header for compact styling
 */

document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.site-header');
    let lastScrollTop = 0;
    
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Add scrolled class when user scrolls down more than 50px
        if (scrollTop > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop;
    }
    
    // Throttle scroll events for better performance
    let ticking = false;
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(handleScroll);
            ticking = true;
            setTimeout(() => { ticking = false; }, 16); // ~60fps
        }
    }
    
    window.addEventListener('scroll', requestTick, { passive: true });
});
