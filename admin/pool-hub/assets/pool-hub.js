/**
 * Pool Hub JavaScript
 * 
 * @package EIPSI_Forms
 * @since 2.5.3
 */

/* eslint-disable no-alert */

(function($) {
    'use strict';

    console.log('[POOL-HUB-JS] Script cargado - Inicializando...');

    // === State ===
    const state = {
        currentPoolId: null,
        isLoading: false
    };

    // === Modal Functions ===
    function openModal($modal) {
        console.log('[POOL-HUB] openModal() llamado');
        if ($modal && $modal.length) {
            $modal.fadeIn(200);
            $('body').addClass('eipsi-modal-open');
        }
    }

    function closeModal($modal) {
        console.log('[POOL-HUB] closeModal() llamado');
        if ($modal && $modal.length) {
            $modal.fadeOut(200);
            $('body').removeClass('eipsi-modal-open');
        }
    }

    // === Global function for onclick handlers ===
    window.openEipsiPoolModal = function() {
        console.log('[POOL-HUB] openEipsiPoolModal() llamado desde window');
        const $modal = $('#eipsi-create-pool-modal');
        if ($modal.length) {
            openModal($modal);
        } else {
            console.error('[POOL-HUB] Modal #eipsi-create-pool-modal no encontrado');
        }
    };

    window.closeEipsiPoolModal = function() {
        console.log('[POOL-HUB] closeEipsiPoolModal() llamado');
        const $modal = $('#eipsi-create-pool-modal');
        closeModal($modal);
    };

    // === DOM Ready ===
    $(document).ready(function() {
        console.log('[POOL-HUB-JS] DOM Ready - Inicializando handlers...');
        console.log('[POOL-HUB-JS] eipsiPoolHub data:', typeof eipsiPoolHub !== 'undefined' ? eipsiPoolHub : 'NO DEFINIDO');

        // Close modal on X click
        $('.eipsi-modal-close').on('click', function() {
            closeEipsiPoolModal();
        });

        // Close modal on outside click
        $('.eipsi-modal').on('click', function(e) {
            if ($(e.target).hasClass('eipsi-modal')) {
                closeEipsiPoolModal();
            }
        });

        // Form submission
        $('#eipsi-create-pool-form').on('submit', function(e) {
            console.log('[POOL-HUB] Form submit - validando...');
            // Add any client-side validation here
        });

        console.log('[POOL-HUB-JS] Inicialización completa');
    });

})(jQuery);
