<?php
/**
 * Template pour l'affichage d'un quiz individuel
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="biaquiz-single-page">
    <?php while (have_posts()): the_post(); ?>
        <?php
        $quiz_id = get_the_ID();
        
        // Vérifier si le quiz est actif
        $active = get_post_meta($quiz_id, '_biaquiz_active', true);
        if ($active !== '1') {
            ?>
            <div class="biaquiz-notice error">
                <h2><?php _e('Quiz non disponible', 'biaquiz-core'); ?></h2>
                <p><?php _e('Ce quiz n\'est pas disponible actuellement.', 'biaquiz-core'); ?></p>
                <a href="<?php echo home_url(); ?>" class="button">
                    <?php _e('Retour à l\'accueil', 'biaquiz-core'); ?>
                </a>
            </div>
            <?php
            get_footer();
            return;
        }
        
        // Obtenir les données du quiz
        $questions = get_post_meta($quiz_id, '_biaquiz_questions', true);
        if (!is_array($questions) || empty($questions)) {
            ?>
            <div class="biaquiz-notice error">
                <h2><?php _e('Quiz vide', 'biaquiz-core'); ?></h2>
                <p><?php _e('Ce quiz ne contient aucune question.', 'biaquiz-core'); ?></p>
                <a href="<?php echo home_url(); ?>" class="button">
                    <?php _e('Retour à l\'accueil', 'biaquiz-core'); ?>
                </a>
            </div>
            <?php
            get_footer();
            return;
        }
        
        // Afficher le quiz avec la classe Frontend
        echo BIAQuiz_Frontend::render_quiz($quiz_id);
        ?>
    <?php endwhile; ?>
</div>

<style>
/* Styles pour la page de quiz individuel */
.biaquiz-single-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.biaquiz-notice {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border-radius: 12px;
    margin: 40px 0;
}

.biaquiz-notice.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.biaquiz-notice h2 {
    color: #333;
    margin-bottom: 15px;
}

.biaquiz-notice .button {
    display: inline-block;
    padding: 12px 24px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
    margin-top: 20px;
}

.biaquiz-notice .button:hover {
    background: #005a87;
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .biaquiz-single-page {
        padding: 15px;
    }
    
    .biaquiz-notice {
        padding: 30px 15px;
        margin: 20px 0;
    }
}
</style>

<?php get_footer(); ?>

