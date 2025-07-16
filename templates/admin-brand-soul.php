<?php
/**
 * Admin Brand Core Questionnaire page template - Final version with a two-column layout,
 * side navigation menu, and a "Typeform-like" user experience.
 * Includes a robust, conflict-free access control lock and corrected JavaScript syntax.
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- Start: Data Fetching and Status Checks ---
$settings = AIOHM_KB_Assistant::get_settings();
$is_tribe_member_connected = !empty($settings['aiohm_app_email']);

$user_id = get_current_user_id();
$brand_soul_answers = get_user_meta($user_id, 'aiohm_brand_soul_answers', true);
if (!is_array($brand_soul_answers)) {
    $brand_soul_answers = [];
}

$brand_soul_questions = [
    '✨ Foundation' => [
        'foundation_1' => "What’s the deeper purpose behind your brand — beyond profit?",
        'foundation_2' => "What life experiences shaped this work you now do?",
        'foundation_3' => "Who were you before this calling emerged?",
        'foundation_4' => "If your brand had a soul story, how would you tell it?",
        'foundation_5' => "What’s one transformation you’ve witnessed that reminds you why you do this?",
    ],
    '🌀 Energy' => [
        'energy_1' => "What 3 words describe the emotional tone of your brand voice?",
        'energy_2' => "How do you want your audience to feel after encountering your message?",
        'energy_3' => "What do you not want to sound like?",
        'energy_4' => "Do you prefer poetic, punchy, playful, or professional language?",
        'energy_5' => "Share a quote, phrase, or piece of content that feels like you.",
    ],
    '🎨 Expression' => [
        'expression_1' => "What are your brand’s primary colors (and any specific hex codes)?",
        'expression_2' => "What font(s) do you use — or wish to use — for headers and body text?",
        'expression_3' => "Is there a visual theme (earthy, cosmic, minimalist, ornate) that matches your brand essence?",
        'expression_4' => "Are there any logos, patterns, or symbols that hold meaning for your brand?",
        'expression_5' => "What offerings are you currently sharing with the world — and how are they priced or exchanged?",
    ],
    '🚀 Direction' => [
        'direction_1' => "What’s your current main offer or project you want support with?",
        'direction_2' => "Who is your dream client? Describe them with emotion and detail.",
        'direction_3' => "What are 3 key goals you have for the next 6 months?",
        'direction_4' => "Where do you feel stuck, overwhelmed, or unsure — and where would you love AI support?",
        'direction_5' => "If this AI assistant could speak your soul fluently, what would you want it to never forget?",
    ],
];

$total_questions = 0;
foreach ($brand_soul_questions as $section) {
    $total_questions += count($section);
}
// --- End: Data Fetching ---
?>

<div class="wrap aiohm-brand-soul-page">
    <h1><?php esc_html_e('Your Brand Core Questionnaire', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php esc_html_e('Answer these questions to define the core of your brand. Your answers will help shape your AI assistant\'s voice and knowledge.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice is-dismissible" style="display:none; margin-top: 10px;" tabindex="-1" role="alert" aria-live="polite"><p></p></div>

    <?php if (!$is_tribe_member_connected) : ?>
        <div class="aiohm-content-locked">
            <div class="lock-content">
                <div class="lock-icon">🔒</div>
                <h2><?php esc_html_e('Unlock Your AI Brand Core', 'aiohm-kb-assistant'); ?></h2>
                <p><?php esc_html_e('This questionnaire is a key feature for AIOHM Tribe members. Please connect your free Tribe account to begin defining your brand\'s soul.', 'aiohm-kb-assistant'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-license')); ?>" class="button button-primary"><?php esc_html_e('Connect Your Account', 'aiohm-kb-assistant'); ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="aiohm-page-layout">
            <div class="aiohm-side-nav">
                <nav>
                    <?php
                    $question_index_for_nav = 0;
                    foreach ($brand_soul_questions as $section_title => $questions) {
                        echo "<div class='nav-section'>";
                        echo "<h4>" . esc_html($section_title) . "</h4>";
                        echo "<ol start='" . esc_attr($question_index_for_nav + 1) . "'>";
                        foreach ($questions as $key => $question_text) {
                            echo "<li><a href='#' class='nav-question-link' data-index='" . esc_attr($question_index_for_nav) . "'>" . esc_html($question_text) . "</a></li>";
                            $question_index_for_nav++;
                        }
                        echo "</ol>";
                        echo "</div>";
                    }
                    ?>
                     <div class="nav-section-final">
                        <a href="#" class='nav-question-link' data-index="<?php echo esc_attr($total_questions); ?>" class="final-actions-link">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Save & Export', 'aiohm-kb-assistant'); ?>
                        </a>
                    </div>
                </nav>
            </div>

            <div class="aiohm-form-container">
                <div class="aiohm-progress-bar">
                    <div class="aiohm-progress-bar-inner"></div>
                    <div class="aiohm-progress-label"></div>
                </div>

                <form id="brand-soul-form">
                    <?php wp_nonce_field('aiohm_brand_soul_nonce', 'aiohm_brand_soul_nonce_field'); ?>

                    <div class="aiohm-questions-wrapper">
                        <?php
                        $question_index = 0;
                        foreach ($brand_soul_questions as $section_title => $questions) {
                            foreach ($questions as $key => $question_text) {
                                $is_active = ($question_index === 0) ? 'active' : '';
                                echo "<div class='aiohm-question-slide " . esc_attr($is_active) . "' data-index='" . esc_attr($question_index) . "'>";
                                echo "<p class='question-text'>" . esc_html($question_text) . "</p>";
                                echo "<textarea name='answers[" . esc_attr($key) . "]' placeholder='Type your answer here...' rows='5'>" . esc_textarea($brand_soul_answers[$key] ?? '') . "</textarea>";
                                echo "</div>";
                                $question_index++;
                            }
                        }
                        
                        echo "<div class='aiohm-question-slide' data-index='" . esc_attr($question_index) . "'>";
                        echo "<h2 class='question-section-title'>All Done!</h2>";
                        echo "<p class='question-text'>You've completed your Brand Soul questionnaire. You can now save your work, add it to your private knowledge base for your AI to use, or download a PDF copy.</p>";
                        echo "<div class='aiohm-final-actions'></div>";
                        echo "</div>";
                        ?>
                    </div>

                    <div class="aiohm-navigation">
                        <button type="button" id="prev-btn" class="button button-secondary" style="display: none;"><?php esc_html_e('Previous', 'aiohm-kb-assistant'); ?></button>
                        <button type="button" id="save-progress-btn" class="button button-secondary"><?php esc_html_e('Save Progress', 'aiohm-kb-assistant'); ?></button>
                        <button type="button" id="next-btn" class="button button-primary"><?php esc_html_e('Next', 'aiohm-kb-assistant'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    :root {
        --ohm-primary: #457d58;
        --ohm-dark: #272727;
        --ohm-light-accent: #cbddd1;
        --ohm-light-bg: #EBEBEB;
        --ohm-dark-accent: #1f5014;
        --ohm-font-primary: 'Montserrat', sans-serif;
        --ohm-font-secondary: 'PT Sans', sans-serif;
    }

    .aiohm-brand-soul-page h1, .aiohm-brand-soul-page h2, .aiohm-brand-soul-page h4 {
        font-family: var(--ohm-font-primary);
        color: var(--ohm-dark-accent);
    }
    .aiohm-brand-soul-page p, .aiohm-brand-soul-page label, .aiohm-brand-soul-page .aiohm-side-nav a {
        font-family: var(--ohm-font-secondary);
        color: var(--ohm-dark);
    }
    
    .aiohm-content-locked {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 10vh;
        text-align: center;
        margin-top: 20px;
        background-color: #fdfdfd;
        border: 1px dashed var(--ohm-light-accent);
        border-radius: 8px;
        min-height: 400px;
    }
    .aiohm-content-locked .lock-content { 
        background: #ffffff; 
        padding: 40px; 
        border-radius: 8px; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        max-width: 500px;
    }
    .aiohm-content-locked .lock-icon { font-size: 3em; color: var(--ohm-primary); margin-bottom: 15px; }
    .aiohm-content-locked .button-primary {
        background-color: var(--ohm-primary);
        border-color: var(--ohm-dark-accent);
    }

    .aiohm-page-layout { display: flex; gap: 30px; margin-top: 20px; }
    .aiohm-side-nav { flex: 0 0 300px; background-color: #fdfdfd; padding: 20px; border: 1px solid var(--ohm-light-bg); border-radius: 8px; height: fit-content; }
    .aiohm-side-nav .nav-section { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid var(--ohm-light-accent); }
    .aiohm-side-nav .nav-section:last-of-type { border-bottom: none; }
    .aiohm-side-nav .nav-section-final { margin-top: 20px; }
    .aiohm-side-nav .final-actions-link { display: flex; align-items: center; gap: 8px; font-weight: bold; font-size: 1.1em; color: var(--ohm-primary); text-decoration: none; padding: 10px; border-radius: 4px; transition: background-color 0.3s; }
    .aiohm-side-nav .final-actions-link:hover, .aiohm-side-nav .final-actions-link.active { background-color: var(--ohm-light-accent); }
    .aiohm-side-nav h4 { margin-top: 0; margin-bottom: 10px; font-size: 1.1em; }
    .aiohm-side-nav ol { margin: 0; padding-left: 20px; }
    .aiohm-side-nav li { margin-bottom: 8px; }
    .aiohm-side-nav a { text-decoration: none; color: var(--ohm-dark); font-size: 13px; transition: color 0.3s; display: block; line-height: 1.4; }
    .aiohm-side-nav a:hover { color: var(--ohm-primary); }
    .aiohm-side-nav a.active { font-weight: bold; color: var(--ohm-primary); }
    .aiohm-form-container { flex: 1; background: #fff; padding: 30px 40px; border: 1px solid var(--ohm-light-bg); border-radius: 8px; }
    .aiohm-progress-bar { width: 100%; background-color: var(--ohm-light-bg); border-radius: 5px; height: 10px; margin-bottom: 20px; position: relative; }
    .aiohm-progress-bar-inner { height: 100%; width: 0%; background-color: var(--ohm-primary); border-radius: 5px; transition: width 0.4s ease-in-out; }
    .aiohm-progress-label { text-align: right; font-family: var(--ohm-font-secondary); font-size: 12px; color: var(--ohm-dark); margin-top: 5px; }
    .aiohm-questions-wrapper { position: relative; min-height: 250px; overflow: hidden; margin-bottom: 20px; }
    .aiohm-question-slide { position: absolute; width: 100%; height: 100%; opacity: 0; transition: opacity 0.4s ease-in-out, transform 0.4s ease-in-out; visibility: hidden; transform: translateY(10px); }
    .aiohm-question-slide.active { opacity: 1; visibility: visible; transform: translateY(0); }
    .question-text { font-size: 1.5em; line-height: 1.4; margin-bottom: 20px; color: var(--ohm-dark-accent); }
    .aiohm-question-slide textarea { width: 100%; min-height: 150px; padding: 15px; font-family: var(--ohm-font-secondary); font-size: 1.1em; border: 2px solid var(--ohm-light-bg); border-radius: 4px; transition: border-color 0.3s; }
    .aiohm-question-slide textarea:focus { border-color: var(--ohm-primary); outline: none; box-shadow: 0 0 0 2px var(--ohm-light-accent); }
    .aiohm-navigation { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding-top: 20px; border-top: 1px solid var(--ohm-light-bg); }
    .aiohm-navigation #prev-btn { margin-right: auto; }
    .aiohm-navigation #save-progress-btn { order: 2; }
    .aiohm-navigation #next-btn { order: 3; }
    .aiohm-navigation .button { font-size: 1.1em; padding: 8px 24px; height: auto; }
    .aiohm-final-actions { margin-top: 30px; display: flex; flex-wrap: wrap; gap: 15px; }
    @media (max-width: 960px) { .aiohm-page-layout { flex-direction: column; } .aiohm-side-nav { flex: 0 0 auto; } }
</style>

<script>
    // Self-invoking function to avoid polluting the global scope
    (function($) {
        // Only run the script if the main layout exists (i.e., user is connected)
        if ($('.aiohm-page-layout').length === 0) {
            return;
        }

        let currentQuestionIndex = 0;
        const slides = $('.aiohm-question-slide');
        const navLinks = $('.nav-question-link'); // Use a specific class for navigation links
        const totalQuestions = slides.length - 1;

        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const progressBarInner = document.querySelector('.aiohm-progress-bar-inner');
        const progressLabel = document.querySelector('.aiohm-progress-label');

        function updateView() {
            slides.removeClass('active');
            $(slides[currentQuestionIndex]).addClass('active').find('textarea').focus();

            navLinks.removeClass('active');
            navLinks.filter(`[data-index=${currentQuestionIndex}]`).addClass('active');

            const progressPercentage = (currentQuestionIndex / totalQuestions) * 100;
            progressBarInner.style.width = progressPercentage + '%';
            
            progressLabel.textContent = currentQuestionIndex < totalQuestions 
                ? `Question ${currentQuestionIndex + 1} of ${totalQuestions}` 
                : 'Completed!';

            prevBtn.style.display = currentQuestionIndex > 0 ? 'inline-block' : 'none';
            nextBtn.style.display = currentQuestionIndex < totalQuestions ? 'inline-block' : 'none';

            if (currentQuestionIndex === totalQuestions) {
                const finalActionsHtml = `
                    <button type="button" id="save-brand-soul" class="button button-primary"><?php esc_html_e('Save My Answers', 'aiohm-kb-assistant'); ?></button>
                    <button type="button" id="add-to-kb" class="button button-secondary"><?php esc_html_e('Add to My Knowledge Base', 'aiohm-kb-assistant'); ?></button>
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'download_brand_soul_pdf', 'nonce' => wp_create_nonce('download_brand_soul_pdf')])); ?>" id="download-pdf" class="button button-secondary" target="_blank"><?php esc_html_e('Download PDF', 'aiohm-kb-assistant'); ?></a>
                `;
                $('.aiohm-final-actions').html(finalActionsHtml);
            } else {
                 $('.aiohm-final-actions').empty();
            }
        }

        // --- Event Listeners ---
        nextBtn.addEventListener('click', () => {
            if (currentQuestionIndex < totalQuestions) {
                currentQuestionIndex++;
                updateView();
            }
        });

        prevBtn.addEventListener('click', () => {
            if (currentQuestionIndex > 0) {
                currentQuestionIndex--;
                updateView();
            }
        });

        // Add a single delegated event listener to the navigation container
        $('.aiohm-side-nav').on('click', '.nav-question-link', function(e) {
            e.preventDefault();
            currentQuestionIndex = parseInt($(this).data('index'), 10);
            updateView();
        });

        // Delegated event handlers for final action buttons
        $('.aiohm-form-container').on('click', '#save-brand-soul', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');
            $.post(ajaxurl, {
                action: 'aiohm_save_brand_soul',
                nonce: $('#aiohm_brand_soul_nonce_field').val(),
                data: $('#brand-soul-form').serialize()
            }).done(response => {
                showAdminNotice(response.success ? 'Your answers have been saved.' : 'Error: ' + (response.data.message || 'Could not save.'), response.success ? 'success' : 'error');
            }).fail(() => showAdminNotice('An unexpected server error occurred.', 'error')).always(() => $btn.prop('disabled', false).text('Save My Answers'));
        });

        // Save progress button for each question
        $('.aiohm-form-container').on('click', '#save-progress-btn', function() {
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');
            $.post(ajaxurl, {
                action: 'aiohm_save_brand_soul',
                nonce: $('#aiohm_brand_soul_nonce_field').val(),
                data: $('#brand-soul-form').serialize()
            }).done(response => {
                showAdminNotice(response.success ? 'Progress saved successfully!' : 'Error: ' + (response.data.message || 'Could not save.'), response.success ? 'success' : 'error');
            }).fail(() => showAdminNotice('An unexpected server error occurred.', 'error')).always(() => $btn.prop('disabled', false).text(originalText));
        });

        $('.aiohm-form-container').on('click', '#add-to-kb', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).text('Adding...');
            $.post(ajaxurl, {
                action: 'aiohm_add_brand_soul_to_kb',
                nonce: $('#aiohm_brand_soul_nonce_field').val(),
                data: $('#brand-soul-form').serialize()
            }).done(response => {
                showAdminNotice(response.success ? 'Your Brand Soul has been added to your knowledge base.' : 'Error: ' + (response.data.message || 'Could not add to KB.'), response.success ? 'success' : 'error');
            }).fail(() => showAdminNotice('An unexpected server error occurred.', 'error')).always(() => $btn.prop('disabled', false).text('Add to My Knowledge Base'));
        });
    
        // Enhanced admin notice function with accessibility features
        function showAdminNotice(message, type = 'success', persistent = false) {
            let $noticeDiv = $('#aiohm-admin-notice');
            
            // Create notice div if it doesn't exist
            if ($noticeDiv.length === 0) {
                $('<div id="aiohm-admin-notice" class="notice is-dismissible" style="margin-top: 10px;" tabindex="-1" role="alert" aria-live="polite"><p></p></div>').insertAfter('h1');
                $noticeDiv = $('#aiohm-admin-notice');
            }
            
            // Clear existing classes and add new type
            $noticeDiv.removeClass('notice-success notice-error notice-warning notice-info').addClass('notice-' + type);
            
            // Set message content
            $noticeDiv.find('p').html(message);
            
            // Show notice with fade in effect
            $noticeDiv.fadeIn(300, function() {
                // Auto-focus for accessibility after fade in completes
                $noticeDiv.focus();
                
                // Announce to screen readers
                if (type === 'error') {
                    $noticeDiv.attr('aria-live', 'assertive');
                } else {
                    $noticeDiv.attr('aria-live', 'polite');
                }
            });
            
            // Handle dismiss button
            $noticeDiv.off('click.notice-dismiss').on('click.notice-dismiss', '.notice-dismiss', function() {
                $noticeDiv.fadeOut(300);
                // Return focus to the previously focused element or main content
                $('h1').focus();
            });
            
            // Auto-hide after timeout (unless persistent)
            if (!persistent) {
                setTimeout(() => {
                    if ($noticeDiv.is(':visible')) {
                        $noticeDiv.fadeOut(300, function() {
                            // Return focus to main content when auto-hiding
                            $('h1').focus();
                        });
                    }
                }, 7000); // Increased to 7 seconds for better UX
            }
        }

        // Initial view setup
        updateView();

    })(jQuery);
</script>