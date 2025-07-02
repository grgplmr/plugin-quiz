<?php
/**
 * Gestion des Taxonomies pour BIAQuiz
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIAQuiz_Taxonomies {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_taxonomies'));
        add_action('quiz_category_add_form_fields', array(__CLASS__, 'add_category_fields'));
        add_action('quiz_category_edit_form_fields', array(__CLASS__, 'edit_category_fields'));
        add_action('created_quiz_category', array(__CLASS__, 'save_category_fields'));
        add_action('edited_quiz_category', array(__CLASS__, 'save_category_fields'));
        add_filter('manage_edit-quiz_category_columns', array(__CLASS__, 'category_columns'));
        add_filter('manage_quiz_category_custom_column', array(__CLASS__, 'category_column_content'), 10, 3);
    }
    
    /**
     * Enregistrer les taxonomies
     */
    public static function register_taxonomies() {
        // Taxonomie Catégorie de Quiz
        $labels = array(
            'name'                       => __('Catégories de Quiz', 'biaquiz-core'),
            'singular_name'              => __('Catégorie de Quiz', 'biaquiz-core'),
            'menu_name'                  => __('Catégories', 'biaquiz-core'),
            'all_items'                  => __('Toutes les Catégories', 'biaquiz-core'),
            'parent_item'                => __('Catégorie Parente', 'biaquiz-core'),
            'parent_item_colon'          => __('Catégorie Parente:', 'biaquiz-core'),
            'new_item_name'              => __('Nom de la Nouvelle Catégorie', 'biaquiz-core'),
            'add_new_item'               => __('Ajouter une Nouvelle Catégorie', 'biaquiz-core'),
            'edit_item'                  => __('Modifier la Catégorie', 'biaquiz-core'),
            'update_item'                => __('Mettre à jour la Catégorie', 'biaquiz-core'),
            'view_item'                  => __('Voir la Catégorie', 'biaquiz-core'),
            'separate_items_with_commas' => __('Séparer les catégories par des virgules', 'biaquiz-core'),
            'add_or_remove_items'        => __('Ajouter ou supprimer des catégories', 'biaquiz-core'),
            'choose_from_most_used'      => __('Choisir parmi les plus utilisées', 'biaquiz-core'),
            'popular_items'              => __('Catégories Populaires', 'biaquiz-core'),
            'search_items'               => __('Rechercher des Catégories', 'biaquiz-core'),
            'not_found'                  => __('Aucune catégorie trouvée', 'biaquiz-core'),
            'no_terms'                   => __('Aucune catégorie', 'biaquiz-core'),
            'items_list'                 => __('Liste des catégories', 'biaquiz-core'),
            'items_list_navigation'      => __('Navigation de la liste des catégories', 'biaquiz-core'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'quiz-categorie'),
        );
        
        register_taxonomy('quiz_category', array('biaquiz'), $args);
    }
    
    /**
     * Ajouter des champs personnalisés lors de la création d'une catégorie
     */
    public static function add_category_fields() {
        ?>
        <div class="form-field">
            <label for="category_description_long"><?php _e('Description Longue', 'biaquiz-core'); ?></label>
            <textarea name="category_description_long" id="category_description_long" rows="5" cols="50"></textarea>
            <p><?php _e('Description détaillée de la catégorie de quiz.', 'biaquiz-core'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="category_icon"><?php _e('Icône CSS', 'biaquiz-core'); ?></label>
            <input type="text" name="category_icon" id="category_icon" value="" size="40" />
            <p><?php _e('Classe CSS pour l\'icône (ex: dashicons-airplane).', 'biaquiz-core'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="category_color"><?php _e('Couleur', 'biaquiz-core'); ?></label>
            <input type="color" name="category_color" id="category_color" value="#0073aa" />
            <p><?php _e('Couleur associée à cette catégorie.', 'biaquiz-core'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="category_order"><?php _e('Ordre d\'affichage', 'biaquiz-core'); ?></label>
            <input type="number" name="category_order" id="category_order" value="0" size="10" />
            <p><?php _e('Ordre d\'affichage de la catégorie (0 = premier).', 'biaquiz-core'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Modifier les champs personnalisés lors de l'édition d'une catégorie
     */
    public static function edit_category_fields($term) {
        $description_long = get_term_meta($term->term_id, 'description_long', true);
        $icon = get_term_meta($term->term_id, 'icon', true);
        $color = get_term_meta($term->term_id, 'color', true);
        $order = get_term_meta($term->term_id, 'order', true);
        
        if (empty($color)) {
            $color = '#0073aa';
        }
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="category_description_long"><?php _e('Description Longue', 'biaquiz-core'); ?></label>
            </th>
            <td>
                <textarea name="category_description_long" id="category_description_long" rows="5" cols="50"><?php echo esc_textarea($description_long); ?></textarea>
                <p class="description"><?php _e('Description détaillée de la catégorie de quiz.', 'biaquiz-core'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="category_icon"><?php _e('Icône CSS', 'biaquiz-core'); ?></label>
            </th>
            <td>
                <input type="text" name="category_icon" id="category_icon" value="<?php echo esc_attr($icon); ?>" size="40" />
                <p class="description"><?php _e('Classe CSS pour l\'icône (ex: dashicons-airplane).', 'biaquiz-core'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="category_color"><?php _e('Couleur', 'biaquiz-core'); ?></label>
            </th>
            <td>
                <input type="color" name="category_color" id="category_color" value="<?php echo esc_attr($color); ?>" />
                <p class="description"><?php _e('Couleur associée à cette catégorie.', 'biaquiz-core'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="category_order"><?php _e('Ordre d\'affichage', 'biaquiz-core'); ?></label>
            </th>
            <td>
                <input type="number" name="category_order" id="category_order" value="<?php echo esc_attr($order); ?>" size="10" />
                <p class="description"><?php _e('Ordre d\'affichage de la catégorie (0 = premier).', 'biaquiz-core'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Sauvegarder les champs personnalisés de la catégorie
     */
    public static function save_category_fields($term_id) {
        if (isset($_POST['category_description_long'])) {
            update_term_meta($term_id, 'description_long', sanitize_textarea_field($_POST['category_description_long']));
        }
        
        if (isset($_POST['category_icon'])) {
            update_term_meta($term_id, 'icon', sanitize_text_field($_POST['category_icon']));
        }
        
        if (isset($_POST['category_color'])) {
            update_term_meta($term_id, 'color', sanitize_hex_color($_POST['category_color']));
        }
        
        if (isset($_POST['category_order'])) {
            update_term_meta($term_id, 'order', intval($_POST['category_order']));
        }
    }
    
    /**
     * Colonnes personnalisées pour la liste des catégories
     */
    public static function category_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['name'] = $columns['name'];
        $new_columns['icon'] = __('Icône', 'biaquiz-core');
        $new_columns['color'] = __('Couleur', 'biaquiz-core');
        $new_columns['quiz_count'] = __('Nombre de Quiz', 'biaquiz-core');
        $new_columns['order'] = __('Ordre', 'biaquiz-core');
        $new_columns['slug'] = $columns['slug'];
        $new_columns['posts'] = $columns['posts'];
        
        return $new_columns;
    }
    
    /**
     * Contenu des colonnes personnalisées
     */
    public static function category_column_content($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'icon':
                $icon = get_term_meta($term_id, 'icon', true);
                if ($icon) {
                    $content = '<span class="dashicons ' . esc_attr($icon) . '"></span>';
                } else {
                    $content = '-';
                }
                break;
                
            case 'color':
                $color = get_term_meta($term_id, 'color', true);
                if ($color) {
                    $content = '<div style="width: 20px; height: 20px; background-color: ' . esc_attr($color) . '; border: 1px solid #ddd; display: inline-block;"></div> ' . esc_html($color);
                } else {
                    $content = '-';
                }
                break;
                
            case 'quiz_count':
                $count = wp_count_posts('biaquiz');
                $published_count = isset($count->publish) ? $count->publish : 0;
                
                // Compter les quiz de cette catégorie
                $quiz_count = get_posts(array(
                    'post_type' => 'biaquiz',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'quiz_category',
                            'field' => 'term_id',
                            'terms' => $term_id
                        )
                    ),
                    'fields' => 'ids'
                ));
                
                $content = count($quiz_count);
                break;
                
            case 'order':
                $order = get_term_meta($term_id, 'order', true);
                $content = $order ? $order : '0';
                break;
        }
        
        return $content;
    }
    
    /**
     * Obtenir les catégories triées par ordre
     */
    public static function get_ordered_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'quiz_category',
            'hide_empty' => false,
            'meta_key' => 'order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        ));
        
        return $categories;
    }
    
    /**
     * Obtenir les métadonnées d'une catégorie
     */
    public static function get_category_meta($term_id) {
        return array(
            'description_long' => get_term_meta($term_id, 'description_long', true),
            'icon' => get_term_meta($term_id, 'icon', true),
            'color' => get_term_meta($term_id, 'color', true),
            'order' => get_term_meta($term_id, 'order', true)
        );
    }
}

