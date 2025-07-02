<?php
/**
 * Template pour l'affichage des catégories de quiz
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="biaquiz-category-page">
    <div class="container">
        <?php
        $term = get_queried_object();
        if ($term && !is_wp_error($term)):
            
            // Obtenir les métadonnées de la catégorie
            $meta = BIAQuiz_Taxonomies::get_category_meta($term->term_id);
            
            // Obtenir les quiz de cette catégorie
            $quiz_args = array(
                'post_type' => 'biaquiz',
                'post_status' => 'publish',
                'posts_per_page' => get_option('biaquiz_options', array())['quiz_per_page'] ?? 10,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'quiz_category',
                        'field' => 'term_id',
                        'terms' => $term->term_id
                    )
                ),
                'meta_query' => array(
                    array(
                        'key' => '_biaquiz_active',
                        'value' => '1'
                    )
                ),
                'orderby' => 'meta_value_num',
                'meta_key' => '_biaquiz_number',
                'order' => 'ASC'
            );
            
            $quizzes = new WP_Query($quiz_args);
        ?>
        
        <!-- En-tête de la catégorie -->
        <header class="category-header" style="border-left-color: <?php echo esc_attr($meta['color'] ?: '#0073aa'); ?>">
            <div class="category-header-content">
                <?php if ($meta['icon']): ?>
                    <span class="category-icon dashicons <?php echo esc_attr($meta['icon']); ?>"></span>
                <?php endif; ?>
                
                <div class="category-info">
                    <h1 class="category-title"><?php echo esc_html($term->name); ?></h1>
                    
                    <?php if ($term->description): ?>
                        <div class="category-description">
                            <?php echo wp_kses_post($term->description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($meta['description_long']): ?>
                        <div class="category-description-long">
                            <?php echo wp_kses_post($meta['description_long']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="category-stats">
                        <span class="quiz-count">
                            <?php printf(_n('%d quiz disponible', '%d quiz disponibles', $quizzes->found_posts, 'biaquiz-core'), $quizzes->found_posts); ?>
                        </span>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Liste des quiz -->
        <main class="category-content">
            <?php if ($quizzes->have_posts()): ?>
                <div class="biaquiz-list">
                    <?php while ($quizzes->have_posts()): $quizzes->the_post(); ?>
                        <?php
                        $quiz_id = get_the_ID();
                        $difficulty = get_post_meta($quiz_id, '_biaquiz_difficulty', true);
                        $quiz_number = get_post_meta($quiz_id, '_biaquiz_number', true);
                        $questions = get_post_meta($quiz_id, '_biaquiz_questions', true);
                        $question_count = is_array($questions) ? count($questions) : 0;
                        ?>
                        
                        <article class="biaquiz-item">
                            <div class="quiz-header">
                                <span class="quiz-category" style="background-color: <?php echo esc_attr($meta['color'] ?: '#0073aa'); ?>20; color: <?php echo esc_attr($meta['color'] ?: '#0073aa'); ?>">
                                    <?php echo esc_html($term->name); ?>
                                </span>
                                <?php if ($quiz_number): ?>
                                    <span class="quiz-number">
                                        <?php printf(__('Quiz #%d', 'biaquiz-core'), $quiz_number); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <h2 class="quiz-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            
                            <?php if (get_the_excerpt()): ?>
                                <div class="quiz-description">
                                    <?php the_excerpt(); ?>
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
                                <a href="<?php the_permalink(); ?>" class="button button-primary">
                                    <?php _e('Commencer le Quiz', 'biaquiz-core'); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($quizzes->max_num_pages > 1): ?>
                    <nav class="quiz-pagination">
                        <?php
                        echo paginate_links(array(
                            'total' => $quizzes->max_num_pages,
                            'current' => max(1, get_query_var('paged')),
                            'format' => '?paged=%#%',
                            'show_all' => false,
                            'end_size' => 1,
                            'mid_size' => 2,
                            'prev_next' => true,
                            'prev_text' => __('« Précédent', 'biaquiz-core'),
                            'next_text' => __('Suivant »', 'biaquiz-core'),
                            'type' => 'list'
                        ));
                        ?>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-quizzes">
                    <h3><?php _e('Aucun quiz disponible', 'biaquiz-core'); ?></h3>
                    <p><?php _e('Il n\'y a actuellement aucun quiz disponible dans cette catégorie.', 'biaquiz-core'); ?></p>
                    <a href="<?php echo home_url(); ?>" class="button">
                        <?php _e('Retour à l\'accueil', 'biaquiz-core'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </main>
        
        <?php else: ?>
            <div class="category-not-found">
                <h1><?php _e('Catégorie non trouvée', 'biaquiz-core'); ?></h1>
                <p><?php _e('La catégorie demandée n\'existe pas ou n\'est plus disponible.', 'biaquiz-core'); ?></p>
                <a href="<?php echo home_url(); ?>" class="button">
                    <?php _e('Retour à l\'accueil', 'biaquiz-core'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Styles pour la page de catégorie */
.biaquiz-category-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.category-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 40px;
    border-left: 6px solid;
}

.category-header-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.category-icon {
    font-size: 48px;
    opacity: 0.9;
}

.category-info {
    flex: 1;
}

.category-title {
    font-size: 2.5em;
    margin: 0 0 15px 0;
    font-weight: 700;
}

.category-description {
    font-size: 1.1em;
    margin: 15px 0;
    opacity: 0.95;
    line-height: 1.6;
}

.category-description-long {
    font-size: 1em;
    margin: 15px 0;
    opacity: 0.9;
    line-height: 1.6;
}

.category-stats {
    margin-top: 20px;
}

.quiz-count {
    background: rgba(255,255,255,0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
}

.biaquiz-list {
    display: grid;
    gap: 25px;
    margin-bottom: 40px;
}

.biaquiz-item {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #e9ecef;
}

.biaquiz-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.quiz-header {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    font-size: 14px;
}

.quiz-category,
.quiz-number {
    padding: 6px 12px;
    border-radius: 15px;
    font-weight: 500;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.quiz-number {
    background: #f8f9fa;
    color: #666;
}

.quiz-title {
    margin: 0 0 20px 0;
    font-size: 1.4em;
    font-weight: 600;
    line-height: 1.3;
}

.quiz-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.2s ease;
}

.quiz-title a:hover {
    color: #0073aa;
}

.quiz-description {
    color: #666;
    line-height: 1.6;
    margin: 20px 0;
}

.quiz-meta {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    font-size: 14px;
    align-items: center;
}

.difficulty {
    padding: 6px 12px;
    border-radius: 15px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 11px;
}

.difficulty-facile {
    background: #d4edda;
    color: #155724;
}

.difficulty-moyen {
    background: #fff3cd;
    color: #856404;
}

.difficulty-difficile {
    background: #f8d7da;
    color: #721c24;
}

.question-count {
    color: #666;
    font-weight: 500;
}

.quiz-actions {
    margin-top: 25px;
}

.button {
    display: inline-block;
    padding: 12px 24px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.button:hover {
    background: #005a87;
    transform: translateY(-1px);
}

.button-primary {
    background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
    box-shadow: 0 2px 8px rgba(0,115,170,0.3);
}

.button-primary:hover {
    box-shadow: 0 4px 12px rgba(0,115,170,0.4);
}

.no-quizzes,
.category-not-found {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 12px;
    color: #666;
}

.no-quizzes h3,
.category-not-found h1 {
    color: #333;
    margin-bottom: 15px;
}

.quiz-pagination {
    margin-top: 40px;
    text-align: center;
}

.quiz-pagination ul {
    display: inline-flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 5px;
}

.quiz-pagination li {
    margin: 0;
}

.quiz-pagination a,
.quiz-pagination span {
    display: block;
    padding: 10px 15px;
    background: white;
    border: 1px solid #ddd;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.quiz-pagination a:hover {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.quiz-pagination .current {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

/* Responsive */
@media (max-width: 768px) {
    .biaquiz-category-page {
        padding: 15px;
    }
    
    .category-header {
        padding: 25px;
    }
    
    .category-header-content {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .category-title {
        font-size: 2em;
    }
    
    .biaquiz-item {
        padding: 20px;
    }
    
    .quiz-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .quiz-pagination ul {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php get_footer(); ?>

