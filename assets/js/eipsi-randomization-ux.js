/**
 * EIPSI Randomization UX - Modal & Experience
 * 
 * Handles the user experience for Randomized Controlled Trials (RCT)
 * including pre-form modals, badges, and feedback.
 * 
 * @since 1.4.3
 */

document.addEventListener('DOMContentLoaded', function() {
    const rctContainers = document.querySelectorAll('.eipsi-randomization-container');
    
    rctContainers.forEach(container => {
        const showModal = container.dataset.showModal === 'true';
        
        if (showModal) {
            const modalOverlay = container.querySelector('.eipsi-rct-modal-overlay');
            if (modalOverlay) {
                // Show modal with a slight delay for better UX
                setTimeout(() => {
                    modalOverlay.classList.add('active');
                }, 500);
                
                // Handle close button
                const closeBtn = modalOverlay.querySelector('.eipsi-rct-modal-btn');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        modalOverlay.classList.remove('active');
                        // Optional: remove from DOM after transition
                        setTimeout(() => {
                            modalOverlay.style.display = 'none';
                        }, 300);
                    });
                }
            }
        }
    });
});
