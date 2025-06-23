/**
 * Naval-style Header Scroll Behavior
 * Hide navigation when scrolling down, show when scrolling up
 */

document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.site-header');
    let lastScrollTop = 0;
    const scrollThreshold = 50; // Start hiding after 50px scroll
    
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Remove all scroll classes first
        header.classList.remove('scrolled-down', 'scrolled-up');
        
        if (scrollTop <= scrollThreshold) {
            // At top - show everything
            return;
        }
        
        if (scrollTop > lastScrollTop) {
            // Scrolling down - hide navigation, show only logo
            header.classList.add('scrolled-down');
        } else {
            // Scrolling up - show navigation again
            header.classList.add('scrolled-up');
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
