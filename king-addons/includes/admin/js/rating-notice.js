/**
 * King Addons - Rating Notice JavaScript
 */
(function($) {
    'use strict';

    const ratingTexts = {
        1: '😞 Poor',
        2: '😐 Fair', 
        3: '🙂 Good',
        4: '😊 Very Good',
        5: '🤩 Excellent!'
    };

    let selectedRating = 0;

    $(document).ready(function() {
        const $notice = $('.king-addons-rating-notice');
        
        if (!$notice.length) {
            return;
        }

        const $stars = $notice.find('.king-addons-star');
        const $ratingText = $notice.find('.king-addons-rating-text');
        const $feedbackForm = $notice.find('.king-addons-feedback-form');

        // Star hover effect
        $stars.on('mouseenter', function() {
            const starValue = $(this).data('star');
            highlightStars(starValue, 'hover');
            $ratingText.text(ratingTexts[starValue]);
        });

        $stars.on('mouseleave', function() {
            highlightStars(selectedRating, 'active');
            $ratingText.text(selectedRating ? ratingTexts[selectedRating] : '');
        });

        // Star click
        $stars.on('click', function() {
            const $this = $(this);
            selectedRating = $this.data('star');
            
            // Add pop animation
            $stars.removeClass('pop');
            $stars.each(function() {
                if ($(this).data('star') <= selectedRating) {
                    $(this).addClass('pop');
                }
            });
            
            highlightStars(selectedRating, 'active');
            $ratingText.text(ratingTexts[selectedRating]);

            if (selectedRating === 5) {
                // 5 stars - redirect to WordPress.org
                setTimeout(function() {
                    window.open(KingAddonsRating.wpOrgUrl, '_blank');
                    markAsRated();
                    $notice.slideUp(300);
                }, 400);
            } else {
                // Less than 5 - show feedback form
                setTimeout(function() {
                    $notice.addClass('show-feedback');
                    $feedbackForm.slideDown(300);
                    $feedbackForm.find('textarea').focus();
                }, 300);
            }
        });

        /**
         * Highlight stars up to a given count
         */
        function highlightStars(count, className) {
            $stars.removeClass('active hover');
            $stars.each(function() {
                if ($(this).data('star') <= count) {
                    $(this).addClass(className);
                }
            });
        }

        // Submit feedback
        $notice.on('click', '.king-addons-submit-feedback', function() {
            const $btn = $(this);
            const feedback = $notice.find('.king-addons-feedback-text').val();
            
            $btn.prop('disabled', true).text('Sending...');
            
            $.post(KingAddonsRating.ajaxurl, {
                action: 'king_addons_rating_feedback',
                nonce: KingAddonsRating.nonce,
                rating: selectedRating,
                feedback: feedback
            }, function() {
                $btn.text('Thank you! ✓');
                setTimeout(function() {
                    $notice.slideUp(300);
                }, 1000);
            }).fail(function() {
                $btn.prop('disabled', false).text('Send Feedback');
                alert('Failed to send feedback. Please try again.');
            });
        });

        // Skip feedback
        $notice.on('click', '.king-addons-skip-feedback', function() {
            markAsRated();
            $notice.slideUp(300);
        });

        // Maybe later
        $notice.on('click', '.king-addons-maybe-later', function(e) {
            e.preventDefault();
            $.post(KingAddonsRating.ajaxurl, {
                action: 'king_addons_rating_later',
                nonce: KingAddonsRating.nonce
            });
            $notice.slideUp(300);
        });

        // Already rated
        $notice.on('click', '.king-addons-already-rated', function(e) {
            e.preventDefault();
            markAsRated();
            $notice.slideUp(300);
        });

        // Dismiss button (X button) - acts like "Maybe Later"
        $notice.on('click', '.notice-dismiss, .king-addons-notice-dismiss-btn', function(e) {
            e.preventDefault();
            $.post(KingAddonsRating.ajaxurl, {
                action: 'king_addons_rating_later',
                nonce: KingAddonsRating.nonce
            });
            $notice.slideUp(300);
        });

        /**
         * Mark as rated via AJAX
         */
        function markAsRated() {
            $.post(KingAddonsRating.ajaxurl, {
                action: 'king_addons_rating_rated',
                nonce: KingAddonsRating.nonce
            });
        }
    });
})(jQuery);
