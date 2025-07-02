<?php
/**
 * Frontend pour BIAQuiz
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIAQuiz_Frontend {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_filter('the_content', array(__CLASS__, 'quiz_content'));
        add_action('init', array(__CLASS__, 'add_rewrite_rules'));
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_action('template_redirect', array(__CLASS__, 'template_redirect'));
        add_filter('template_include', array(__CLASS__, 'template_include'));
        add_shortcode('biaquiz_list', array(__CLASS__, 'quiz_list_shortcode'));
        add_shortcode('biaquiz_categories', array(__CLASS__, 'categories_shortcode'));
    }
    
    /**
     * Enregistrer les scripts et styles
     */
    public static function enqueue_scripts() {
        if (is_singular('biaquiz') || self::is_quiz_page()) {
            wp_enqueue_script(
                'biaquiz-frontend',
                BIAQUIZ_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                BIAQUIZ_VERSION,
                true
            );
            
            wp_enqueue_style(
                'biaquiz-frontend',
                BIAQUIZ_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                BIAQUIZ_VERSION
            );
            
            wp_localize_script('biaquiz-frontend', 'biaquiz_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('biaquiz_frontend'),
                'strings' => array(
                    'loading' => __('Chargement...', 'biaquiz-core'),
                    'submit_quiz' => __('Valider le Quiz', 'biaquiz-core'),
                    'next_question' => __('Question Suivante', 'biaquiz-core'),
                    'previous_question' => __('Question Précédente', 'biaquiz-core'),
                    'retry_incorrect' => __('Reprendre les Questions Incorrectes', 'biaquiz-core'),
                    'start_over' => __('Recommencer', 'biaquiz-core'),
                    'time_up' => __('Temps écoulé !', 'biaquiz-core'),
                    'confirm_submit' => __('Êtes-vous sûr de vouloir soumettre vos réponses ?', 'biaquiz-core')
                )
            ));
        }
    }
    
    /**
     * Modifier le contenu des quiz
     */
    public static function quiz_content($content) {
        if (is_singular('biaquiz') && in_the_loop() && is_main_query()) {
            global $post;
            
            $quiz_content = self::render_quiz($post->ID);
            return $content . $quiz_content;
        }
        
        return $content;
    }
    
    /**
     * Rendu d'un quiz
     */
    public static function render_quiz($quiz_id) {
        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== 'biaquiz') {
            return '';
        }
        
        // Vérifier si le quiz est actif
        $active = get_post_meta($quiz_id, '_biaquiz_active', true);
        if ($active !== '1') {
            return '<div class="biaquiz-notice">' . __('Ce quiz n\'est pas disponible actuellement.', 'biaquiz-core') . '</div>';
        }
        
        $questions = get_post_meta($quiz_id, '_biaquiz_questions', true);
        if (!is_array($questions) || empty($questions)) {
            return '<div class="biaquiz-notice">' . __('Ce quiz ne contient aucune question.', 'biaquiz-core') . '</div>';
        }
        
        $difficulty = get_post_meta($quiz_id, '_biaquiz_difficulty', true);
        $quiz_number = get_post_meta($quiz_id, '_biaquiz_number', true);
        
        // Obtenir la catégorie
        $categories = get_the_terms($quiz_id, 'quiz_category');
        $category = $categories && !is_wp_error($categories) ? $categories[0] : null;
        
        ob_start();
        ?>
        <div class="biaquiz-container" data-quiz-id="<?php echo esc_attr($quiz_id); ?>">
            <div class="biaquiz-header">
                <?php if ($category): ?>
                    <div class="biaquiz-category">
                        <span class="category-name"><?php echo esc_html($category->name); ?></span>
                        <?php if ($quiz_number): ?>
                            <span class="quiz-number"><?php printf(__('Quiz #%d', 'biaquiz-core'), $quiz_number); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <h2 class="biaquiz-title"><?php echo esc_html($quiz->post_title); ?></h2>
                
                <?php if ($quiz->post_excerpt): ?>
                    <div class="biaquiz-description">
                        <?php echo wp_kses_post($quiz->post_excerpt); ?>
                    </div>
                <?php endif; ?>
                
                <div class="biaquiz-meta">
                    <span class="difficulty difficulty-<?php echo esc_attr($difficulty); ?>">
                        <?php
                        $difficulties = array(
                            'facile' => __('Facile', 'biaquiz-core'),
                            'moyen' => __('Moyen', 'biaquiz-core'),
                            'difficile' => __('Difficile', 'biaquiz-core')
                        );
                        echo esc_html($difficulties[$difficulty] ?? $difficulty);
                        ?>
                    </span>
                    <span class="question-count">
                        <?php printf(_n('%d question', '%d questions', count($questions), 'biaquiz-core'), count($questions)); ?>
                    </span>
                </div>
            </div>
            
            <div class="biaquiz-content">
                <div class="biaquiz-start-screen">
                    <div class="start-instructions">
                        <h3><?php _e('Instructions', 'biaquiz-core'); ?></h3>
                        <ul>
                            <li><?php _e('Lisez attentivement chaque question', 'biaquiz-core'); ?></li>
                            <li><?php _e('Sélectionnez la meilleure réponse parmi les choix proposés', 'biaquiz-core'); ?></li>
                            <li><?php _e('Vous pouvez naviguer entre les questions', 'biaquiz-core'); ?></li>
                            <li><?php _e('Les questions incorrectes devront être reprises jusqu\'à obtenir 20/20', 'biaquiz-core'); ?></li>
                        </ul>
                    </div>
                    
                    <button class="biaquiz-start-btn button button-primary">
                        <?php _e('Commencer le Quiz', 'biaquiz-core'); ?>
                    </button>
                </div>
                
                <div class="biaquiz-quiz-screen" style="display: none;">
                    <div class="biaquiz-progress">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-text">
                            <span class="current-question">1</span> / <span class="total-questions"><?php echo count($questions); ?></span>
                        </div>
                        <div class="quiz-timer">
                            <span class="timer-text"><?php _e('Temps:', 'biaquiz-core'); ?></span>
                            <span class="timer-value">00:00</span>
                        </div>
                    </div>
                    
                    <div class="biaquiz-questions">
                        <!-- Questions chargées dynamiquement -->
                    </div>
                    
                    <div class="biaquiz-navigation">
                        <button class="biaquiz-prev-btn button" disabled>
                            <?php _e('Précédent', 'biaquiz-core'); ?>
                        </button>
                        <button class="biaquiz-next-btn button">
                            <?php _e('Suivant', 'biaquiz-core'); ?>
                        </button>
                        <button class="biaquiz-submit-btn button button-primary" style="display: none;">
                            <?php _e('Terminer le Quiz', 'biaquiz-core'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="biaquiz-results-screen" style="display: none;">
                    <div class="results-header">
                        <h3><?php _e('Résultats du Quiz', 'biaquiz-core'); ?></h3>
                        <div class="score-display">
                            <div class="score-circle">
                                <span class="score-text"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="results-details">
                        <div class="result-stats">
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Score:', 'biaquiz-core'); ?></span>
                                <span class="stat-value score-value"></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Pourcentage:', 'biaquiz-core'); ?></span>
                                <span class="stat-value percentage-value"></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Temps:', 'biaquiz-core'); ?></span>
                                <span class="stat-value time-value"></span>
                            </div>
                        </div>
                        
                        <div class="results-message"></div>
                        
                        <div class="results-actions">
                            <button class="biaquiz-retry-btn button" style="display: none;">
                                <?php _e('Reprendre les Questions Incorrectes', 'biaquiz-core'); ?>
                            </button>
                            <button class="biaquiz-restart-btn button">
                                <?php _e('Recommencer le Quiz', 'biaquiz-core'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="results-review">
                        <h4><?php _e('Révision des Réponses', 'biaquiz-core'); ?></h4>
                        <div class="review-questions">
                            <!-- Révision chargée dynamiquement -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour lister les quiz
     */
    public static function quiz_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => 10,
            'difficulty' => '',
            'show_description' => 'yes'
        ), $atts);
        
        $args = array(
            'post_type' => 'biaquiz',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(
                array(
                    'key' => '_biaquiz_active',
                    'value' => '1'
                )
            )
        );
        
        if ($atts['category']) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'quiz_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }
        
        if ($atts['difficulty']) {
            $args['meta_query'][] = array(
                'key' => '_biaquiz_difficulty',
                'value' => $atts['difficulty']
            );
        }
        
        $quizzes = get_posts($args);
        
        if (empty($quizzes)) {
            return '<p>' . __('Aucun quiz disponible.', 'biaquiz-core') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="biaquiz-list">
            <?php foreach ($quizzes as $quiz): ?>
                <?php
                $difficulty = get_post_meta($quiz->ID, '_biaquiz_difficulty', true);
                $quiz_number = get_post_meta($quiz->ID, '_biaquiz_number', true);
                $questions = get_post_meta($quiz->ID, '_biaquiz_questions', true);
                $question_count = is_array($questions) ? count($questions) : 0;
                
                $categories = get_the_terms($quiz->ID, 'quiz_category');
                $category = $categories && !is_wp_error($categories) ? $categories[0] : null;
                ?>
                <div class="biaquiz-item">
                    <div class="quiz-header">
                        <?php if ($category): ?>
                            <span class="quiz-category"><?php echo esc_html($category->name); ?></span>
                        <?php endif; ?>
                        <?php if ($quiz_number): ?>
                            <span class="quiz-number"><?php printf(__('Quiz #%d', 'biaquiz-core'), $quiz_number); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="quiz-title">
                        <a href="<?php echo get_permalink($quiz->ID); ?>">
                            <?php echo esc_html($quiz->post_title); ?>
                        </a>
                    </h3>
                    
                    <?php if ($atts['show_description'] === 'yes' && $quiz->post_excerpt): ?>
                        <div class="quiz-description">
                            <?php echo wp_kses_post($quiz->post_excerpt); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="quiz-meta">
                        <span class="difficulty difficulty-<?php echo esc_attr($difficulty); ?>">
                            <?php
                            $difficulties = array(
                                'facile' => __('Facile', 'biaquiz-core'),
                                'moyen' => __('Moyen', 'biaquiz-core'),
                                'difficile' => __('Difficile', 'biaquiz-core')
                            );
                            echo esc_html($difficulties[$difficulty] ?? $difficulty);
                            ?>
                        </span>
                        <span class="question-count">
                            <?php printf(_n('%d question', '%d questions', $question_count, 'biaquiz-core'), $question_count); ?>
                        </span>
                    </div>
                    
                    <div class="quiz-actions">
                        <a href="<?php echo get_permalink($quiz->ID); ?>" class="button button-primary">
                            <?php _e('Commencer', 'biaquiz-core'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour afficher les catégories
     */
    public static function categories_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_count' => 'yes',
            'show_description' => 'yes'
        ), $atts);
        
        $categories = BIAQuiz_Taxonomies::get_ordered_categories();
        
        if (empty($categories)) {
            return '<p>' . __('Aucune catégorie disponible.', 'biaquiz-core') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="biaquiz-categories">
            <?php foreach ($categories as $category): ?>
                <?php
                $meta = BIAQuiz_Taxonomies::get_category_meta($category->term_id);
                $quiz_count = get_posts(array(
                    'post_type' => 'biaquiz',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'quiz_category',
                            'field' => 'term_id',
                            'terms' => $category->term_id
                        )
                    ),
                    'meta_query' => array(
                        array(
                            'key' => '_biaquiz_active',
                            'value' => '1'
                        )
                    ),
                    'fields' => 'ids'
                ));
                ?>
                <div class="biaquiz-category-item">
                    <div class="category-header" style="border-left-color: <?php echo esc_attr($meta['color'] ?: '#0073aa'); ?>">
                        <?php if ($meta['icon']): ?>
                            <span class="category-icon dashicons <?php echo esc_attr($meta['icon']); ?>"></span>
                        <?php endif; ?>
                        <h3 class="category-name">
                            <a href="<?php echo get_term_link($category); ?>">
                                <?php echo esc_html($category->name); ?>
                            </a>
                        </h3>
                        <?php if ($atts['show_count'] === 'yes'): ?>
                            <span class="category-count">
                                <?php printf(_n('%d quiz', '%d quiz', count($quiz_count), 'biaquiz-core'), count($quiz_count)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($atts['show_description'] === 'yes'): ?>
                        <?php if ($category->description): ?>
                            <div class="category-description">
                                <?php echo wp_kses_post($category->description); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($meta['description_long']): ?>
                            <div class="category-description-long">
                                <?php echo wp_kses_post($meta['description_long']); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="category-actions">
                        <a href="<?php echo get_term_link($category); ?>" class="button">
                            <?php _e('Voir les Quiz', 'biaquiz-core'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Ajouter les règles de réécriture
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^quiz/([^/]+)/?$',
            'index.php?quiz_category=$matches[1]',
            'top'
        );
    }
    
    /**
     * Ajouter les variables de requête
     */
    public static function add_query_vars($vars) {
        $vars[] = 'quiz_category';
        return $vars;
    }
    
    /**
     * Inclure les templates du plugin
     */
    public static function template_include($template) {
        // Template pour les catégories de quiz
        if (is_tax('quiz_category')) {
            $plugin_template = self::get_category_template();
            if ($plugin_template && file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Template pour les quiz individuels
        if (is_singular('biaquiz')) {
            $plugin_template = BIAQUIZ_PLUGIN_DIR . 'templates/single-biaquiz.php';
            if (file_exists($plugin_template)) {
                // Vérifier si le thème a son propre template
                $theme_template = locate_template(array('single-biaquiz.php', 'single.php'));
                if (!$theme_template) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Redirection de template
     */
    public static function template_redirect() {
        // Cette fonction est maintenant principalement gérée par template_include
        // Mais on garde pour la compatibilité
        return;
    }
    
    /**
     * Obtenir le template de catégorie
     */
    private static function get_category_template() {
        $templates = array(
            'taxonomy-quiz_category.php',
            'taxonomy.php',
            'archive.php',
            'index.php'
        );
        
        // Chercher dans le thème d'abord
        $template = locate_template($templates);
        
        // Si pas trouvé, utiliser le template du plugin
        if (!$template) {
            $plugin_template = BIAQUIZ_PLUGIN_DIR . 'templates/taxonomy-quiz_category.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Obtenir le template de quiz
     */
    private static function get_quiz_template() {
        $templates = array(
            'single-biaquiz.php',
            'single.php',
            'index.php'
        );
        
        return locate_template($templates);
    }
    
    /**
     * Vérifier si on est sur une page de quiz
     */
    private static function is_quiz_page() {
        return is_singular('biaquiz') || is_tax('quiz_category') || get_query_var('quiz_category');
    }
}

