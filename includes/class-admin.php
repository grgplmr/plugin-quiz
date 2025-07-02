<?php
/**
 * Interface d'administration pour BIAQuiz
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIAQuiz_Admin {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_filter('plugin_action_links_' . BIAQUIZ_PLUGIN_BASENAME, array(__CLASS__, 'plugin_action_links'));
    }
    
    /**
     * Ajouter les menus d'administration
     */
    public static function add_admin_menu() {
        // Menu principal déjà créé par le Custom Post Type
        
        // Sous-menu Tableau de bord
        add_submenu_page(
            'edit.php?post_type=biaquiz',
            __('Tableau de Bord', 'biaquiz-core'),
            __('Tableau de Bord', 'biaquiz-core'),
            'manage_options',
            'biaquiz-dashboard',
            array(__CLASS__, 'dashboard_page')
        );
        
        // Sous-menu Paramètres
        add_submenu_page(
            'edit.php?post_type=biaquiz',
            __('Paramètres', 'biaquiz-core'),
            __('Paramètres', 'biaquiz-core'),
            'manage_options',
            'biaquiz-settings',
            array(__CLASS__, 'settings_page')
        );
    }
    
    /**
     * Enregistrer les scripts et styles
     */
    public static function enqueue_scripts($hook) {
        // Scripts pour les pages du plugin
        if (strpos($hook, 'biaquiz') !== false) {
            wp_enqueue_script(
                'biaquiz-admin',
                BIAQUIZ_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-color-picker'),
                BIAQUIZ_VERSION,
                true
            );
            
            wp_enqueue_style(
                'biaquiz-admin',
                BIAQUIZ_PLUGIN_URL . 'assets/css/admin.css',
                array('wp-color-picker'),
                BIAQUIZ_VERSION
            );
            
            wp_localize_script('biaquiz-admin', 'biaquiz_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('biaquiz_admin'),
                'strings' => array(
                    'confirm_delete' => __('Êtes-vous sûr de vouloir supprimer cet élément ?', 'biaquiz-core'),
                    'loading' => __('Chargement...', 'biaquiz-core')
                )
            ));
        }
    }
    
    /**
     * Enregistrer les paramètres
     */
    public static function register_settings() {
        register_setting('biaquiz_settings', 'biaquiz_options');
        
        add_settings_section(
            'biaquiz_general',
            __('Paramètres Généraux', 'biaquiz-core'),
            array(__CLASS__, 'general_section_callback'),
            'biaquiz_settings'
        );
        
        add_settings_field(
            'quiz_per_page',
            __('Quiz par page', 'biaquiz-core'),
            array(__CLASS__, 'quiz_per_page_callback'),
            'biaquiz_settings',
            'biaquiz_general'
        );
        
        add_settings_field(
            'show_explanations',
            __('Afficher les explications', 'biaquiz-core'),
            array(__CLASS__, 'show_explanations_callback'),
            'biaquiz_settings',
            'biaquiz_general'
        );
        
        add_settings_field(
            'allow_retake',
            __('Autoriser la reprise', 'biaquiz-core'),
            array(__CLASS__, 'allow_retake_callback'),
            'biaquiz_settings',
            'biaquiz_general'
        );
    }
    
    /**
     * Page du tableau de bord
     */
    public static function dashboard_page() {
        // Statistiques
        $total_quizzes = wp_count_posts('biaquiz');
        $published_quizzes = $total_quizzes->publish ?? 0;
        $draft_quizzes = $total_quizzes->draft ?? 0;
        
        $categories = get_terms(array(
            'taxonomy' => 'quiz_category',
            'hide_empty' => false
        ));
        
        // Statistiques par catégorie
        $category_stats = array();
        foreach ($categories as $category) {
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
                'fields' => 'ids'
            ));
            
            $category_stats[] = array(
                'name' => $category->name,
                'count' => count($quiz_count),
                'color' => get_term_meta($category->term_id, 'color', true) ?: '#0073aa'
            );
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Tableau de Bord BIAQuiz', 'biaquiz-core'); ?></h1>
            
            <div class="biaquiz-dashboard">
                <!-- Statistiques générales -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Statistiques Générales', 'biaquiz-core'); ?></h2>
                    <div class="inside">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $published_quizzes; ?></div>
                                <div class="stat-label"><?php _e('Quiz Publiés', 'biaquiz-core'); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $draft_quizzes; ?></div>
                                <div class="stat-label"><?php _e('Brouillons', 'biaquiz-core'); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo count($categories); ?></div>
                                <div class="stat-label"><?php _e('Catégories', 'biaquiz-core'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistiques par catégorie -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Quiz par Catégorie', 'biaquiz-core'); ?></h2>
                    <div class="inside">
                        <div class="category-stats">
                            <?php foreach ($category_stats as $stat): ?>
                                <div class="category-stat-item">
                                    <div class="category-color" style="background-color: <?php echo esc_attr($stat['color']); ?>"></div>
                                    <div class="category-name"><?php echo esc_html($stat['name']); ?></div>
                                    <div class="category-count"><?php echo $stat['count']; ?> quiz</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Actions Rapides', 'biaquiz-core'); ?></h2>
                    <div class="inside">
                        <div class="quick-actions">
                            <a href="<?php echo admin_url('post-new.php?post_type=biaquiz'); ?>" class="button button-primary">
                                <?php _e('Créer un Quiz', 'biaquiz-core'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=biaquiz&page=biaquiz-import-export'); ?>" class="button">
                                <?php _e('Import/Export', 'biaquiz-core'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit-tags.php?taxonomy=quiz_category&post_type=biaquiz'); ?>" class="button">
                                <?php _e('Gérer les Catégories', 'biaquiz-core'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .biaquiz-dashboard .postbox {
            margin-bottom: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
        }
        .category-stats {
            margin: 20px 0;
        }
        .category-stat-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .category-stat-item:last-child {
            border-bottom: none;
        }
        .category-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .category-name {
            flex: 1;
            font-weight: 500;
        }
        .category-count {
            color: #666;
            font-size: 0.9em;
        }
        .quick-actions {
            margin: 20px 0;
        }
        .quick-actions .button {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Page des paramètres
     */
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Paramètres BIAQuiz', 'biaquiz-core'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('biaquiz_settings');
                do_settings_sections('biaquiz_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Callback pour la section générale
     */
    public static function general_section_callback() {
        echo '<p>' . __('Configurez les paramètres généraux du plugin BIAQuiz.', 'biaquiz-core') . '</p>';
    }
    
    /**
     * Callback pour quiz par page
     */
    public static function quiz_per_page_callback() {
        $options = get_option('biaquiz_options', array());
        $value = $options['quiz_per_page'] ?? 10;
        ?>
        <input type="number" name="biaquiz_options[quiz_per_page]" value="<?php echo esc_attr($value); ?>" min="1" max="50" />
        <p class="description"><?php _e('Nombre de quiz affichés par page sur le frontend.', 'biaquiz-core'); ?></p>
        <?php
    }
    
    /**
     * Callback pour afficher les explications
     */
    public static function show_explanations_callback() {
        $options = get_option('biaquiz_options', array());
        $value = $options['show_explanations'] ?? 1;
        ?>
        <label>
            <input type="checkbox" name="biaquiz_options[show_explanations]" value="1" <?php checked($value, 1); ?> />
            <?php _e('Afficher les explications après chaque question', 'biaquiz-core'); ?>
        </label>
        <?php
    }
    
    /**
     * Callback pour autoriser la reprise
     */
    public static function allow_retake_callback() {
        $options = get_option('biaquiz_options', array());
        $value = $options['allow_retake'] ?? 1;
        ?>
        <label>
            <input type="checkbox" name="biaquiz_options[allow_retake]" value="1" <?php checked($value, 1); ?> />
            <?php _e('Permettre aux utilisateurs de reprendre un quiz', 'biaquiz-core'); ?>
        </label>
        <?php
    }
    
    /**
     * Liens d'action du plugin
     */
    public static function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('edit.php?post_type=biaquiz&page=biaquiz-settings') . '">' . __('Paramètres', 'biaquiz-core') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

