<?php

class WP_Comparator_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_form_submissions'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Rendre la méthode accessible globalement
        global $wp_comparator_admin;
        $wp_comparator_admin = $this;
    }
    
    public function register_settings() {
        register_setting('wp_comparator_settings', 'wp_comparator_default_columns');
        register_setting('wp_comparator_settings', 'wp_comparator_show_filters');
        register_setting('wp_comparator_settings', 'wp_comparator_max_comparison');
        register_setting('wp_comparator_settings', 'wp_comparator_primary_color');
        register_setting('wp_comparator_settings', 'wp_comparator_secondary_color');
    }
    
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            'WP Comparator',
            'Comparateur',
            'manage_options',
            'wp-comparator',
            array($this, 'admin_page_overview'),
            'dashicons-analytics',
            30
        );
        
        add_submenu_page(
            'wp-comparator',
            'Types de comparateurs',
            'Types',
            'manage_options',
            'wp-comparator-types',
            array($this, 'admin_page_types')
        );
        
        add_submenu_page(
            'wp-comparator',
            'Champs et Catégories',
            'Champs',
            'manage_options',
            'wp-comparator-fields',
            array($this, 'admin_page_fields')
        );
        
        add_submenu_page(
            'wp-comparator',
            'Filtres',
            'Filtres',
            'manage_options',
            'wp-comparator-filters',
            array($this, 'admin_page_filters')
        );
        
        add_submenu_page(
            'wp-comparator',
            'Contrats/Produits',
            'Contrats',
            'manage_options',
            'wp-comparator-items',
            array($this, 'admin_page_items')
        );
        
        add_submenu_page(
            'wp-comparator',
            'Paramètres',
            'Paramètres',
            'manage_options',
            'wp-comparator-settings',
            array($this, 'admin_page_settings')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wp-comparator') !== false) {
            wp_enqueue_style('wp-comparator-admin', WP_COMPARATOR_PLUGIN_URL . 'assets/css/admin.css', array(), WP_COMPARATOR_VERSION);
            wp_enqueue_script('wp-comparator-admin', WP_COMPARATOR_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WP_COMPARATOR_VERSION, true);
            
            wp_localize_script('wp-comparator-admin', 'wpComparator', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_comparator_nonce')
            ));
        }
    }
    
    public function handle_form_submissions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Gestion des actions spéciales
        if (isset($_GET['force_create_tables'])) {
            $database = new WP_Comparator_Database();
            $database->create_tables();
            wp_redirect(add_query_arg('message', 'tables_created', remove_query_arg('force_create_tables')));
            exit;
        }
        
        if (isset($_GET['check_tables'])) {
            $this->check_all_tables();
            wp_redirect(add_query_arg('message', 'tables_checked', remove_query_arg('check_tables')));
            exit;
        }
        
        if (isset($_POST['wp_comparator_action'])) {
            $action = sanitize_text_field($_POST['wp_comparator_action']);
            $nonce_name = 'wp_comparator_' . $action;
            
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $nonce_name)) {
                wp_die('Erreur de sécurité');
            }
            
            switch ($action) {
                case 'add_type':
                    $this->handle_add_type();
                    break;
                case 'update_type':
                    $this->handle_update_type();
                    break;
                case 'add_field':
                    $this->handle_add_field();
                    break;
                case 'update_field':
                    $this->handle_update_field();
                    break;
                case 'add_filter':
                    $this->handle_add_filter();
                    break;
                case 'add_item':
                    $this->handle_add_item();
                    break;
                case 'save_item_data':
                    $this->handle_save_item_data();
                    break;
                case 'update_item':
                    $this->handle_update_item();
                    break;
            }
        }
    }
    
    public function activate() {
        // Charger les dépendances d'abord
        $this->load_dependencies();
        
        // Ajouter les capacités par défaut
        $this->add_capabilities();
        
        // Créer les tables de base de données
        if (class_exists('WP_Comparator_Database')) {
            $database = new WP_Comparator_Database();
            $database->create_tables();
        }
        
        // Ajouter les règles de réécriture
        if (class_exists('WP_Comparator_Pages')) {
            $pages = new WP_Comparator_Pages();
            $pages->add_rewrite_rules();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function admin_page_overview() {
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $types_count = 0;
        $items_count = 0;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_types'") == $table_types) {
            $types_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_types");
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_items'") == $table_items) {
            $items_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_items");
        }
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/overview.php';
    }
    
    public function admin_page_types() {
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        $types = array();
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_types'") == $table_types) {
            $types = $wpdb->get_results("SELECT * FROM $table_types ORDER BY created_at DESC");
        }
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/types.php';
    }
    
    public function admin_page_fields() {
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        $types = array();
        $fields = array();
        $categories = array();
        $selected_type = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_types'") == $table_types) {
            $types = $wpdb->get_results("SELECT * FROM $table_types ORDER BY name");
        }
        
        if ($selected_type && $wpdb->get_var("SHOW TABLES LIKE '$table_fields'") == $table_fields) {
            $fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_fields WHERE type_id = %d ORDER BY field_type DESC, sort_order",
                $selected_type
            ));
            
            $categories = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_fields WHERE type_id = %d AND field_type = 'category' ORDER BY sort_order",
                $selected_type
            ));
        }
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/fields-simple.php';
    }
    
    public function admin_page_filters() {
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_filters = $wpdb->prefix . 'comparator_filters';
        
        $types = array();
        $filters = array();
        $selected_type = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_types'") == $table_types) {
            $types = $wpdb->get_results("SELECT * FROM $table_types ORDER BY name");
        }
        
        if ($selected_type && $wpdb->get_var("SHOW TABLES LIKE '$table_filters'") == $table_filters) {
            $filters = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_filters WHERE type_id = %d ORDER BY sort_order",
                $selected_type
            ));
        }
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/filters.php';
    }
    
    public function admin_page_items() {
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $types = array();
        $items = array();
        $selected_type = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_types'") == $table_types) {
            $types = $wpdb->get_results("SELECT * FROM $table_types ORDER BY name");
        }
        
        if ($selected_type && $wpdb->get_var("SHOW TABLES LIKE '$table_items'") == $table_items) {
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_items WHERE type_id = %d ORDER BY sort_order, name",
                $selected_type
            ));
        }
        
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/items-simple.php';
    }
    
    public function admin_page_settings() {
        include WP_COMPARATOR_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    public function render_item_data_form_simple($type_id, $item_id) {
        global $wpdb;
        
        $table_items = $wpdb->prefix . 'comparator_items';
        $table_fields = $wpdb->prefix . 'comparator_fields';
        $table_values = $wpdb->prefix . 'comparator_values';
        $table_filters = $wpdb->prefix . 'comparator_filters';
        $table_filter_values = $wpdb->prefix . 'comparator_filter_values';
        $table_item_filters = $wpdb->prefix . 'comparator_item_filters';
        $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
        
        // Récupérer l'élément
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE id = %d AND type_id = %d",
            $item_id, $type_id
        ));
        
        if (!$item) {
            echo '<div class="notice notice-error"><p>Contrat non trouvé.</p></div>';
            return;
        }
        
        // Récupérer les catégories
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_fields WHERE type_id = %d AND field_type = 'category' ORDER BY sort_order",
            $type_id
        ));
        
        // Récupérer les filtres
        $filters = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_filters WHERE type_id = %d ORDER BY sort_order",
            $type_id
        ));
        
        ?>
        <div class="admin-section">
            <h2>
                <?php 
                // Vérifier s'il y a déjà des données
                $has_existing_data = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_values WHERE item_id = %d",
                    $item_id
                ));
                echo $has_existing_data > 0 ? 'Modifier' : 'Saisir';
                ?> les données pour : <?php echo esc_html($item->name); ?>
            </h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('wp_comparator_save_item_data', '_wpnonce'); ?>
                <input type="hidden" name="wp_comparator_action" value="save_item_data">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                <input type="hidden" name="type_id" value="<?php echo $type_id; ?>">
                
                <?php foreach ($categories as $category): ?>
                    <?php
                    // Récupérer les champs de cette catégorie
                    $fields = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $table_fields WHERE parent_category_id = %d AND field_type = 'description' ORDER BY sort_order",
                        $category->id
                    ));
                    ?>
                    
                    <?php if (!empty($fields)): ?>
                        <div class="category-section" style="margin-bottom: 30px; border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                            <h3 style="margin-top: 0; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
                                <?php echo esc_html($category->name); ?>
                                <?php if ($category->short_description): ?>
                                    <small style="font-weight: normal; color: #666; display: block; margin-top: 5px;">
                                        <?php echo esc_html($category->short_description); ?>
                                    </small>
                                <?php endif; ?>
                            </h3>
                            
                            <table class="form-table">
                                <?php foreach ($fields as $field): ?>
                                    <?php
                                    // Récupérer la valeur existante
                                    $current_value = $wpdb->get_var($wpdb->prepare(
                                        "SELECT value FROM $table_values WHERE item_id = %d AND field_id = %d",
                                        $item_id, $field->id
                                    ));
                                    
                                    // Pour les champs filtrables, récupérer spécifiquement la valeur du filtre
                                    $current_filter_value = '';
                                    if ($field->is_filterable) {
                                        $current_filter_value = $wpdb->get_var($wpdb->prepare(
                                            "SELECT filter_value FROM $table_item_filters WHERE item_id = %d AND field_id = %d",
                                            $item_id, $field->id
                                        ));
                                    }
                                    
                                    // Récupérer la description longue existante
                                    $current_long_desc = $wpdb->get_var($wpdb->prepare(
                                        "SELECT long_description FROM $table_field_descriptions WHERE item_id = %d AND field_id = %d",
                                        $item_id, $field->id
                                    ));
                                    ?>
                                    <tr>
                                        <th scope="row">
                                            <label for="field_<?php echo $field->id; ?>">
                                                <?php echo esc_html($field->name); ?>
                                                <?php if ($field->short_description): ?>
                                                    <span class="description" style="font-weight: normal; display: block; color: #666;">
                                                        <?php echo esc_html($field->short_description); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </label>
                                        </th>
                                        <td>
                                            <?php if ($field->is_filterable && !empty($field->filter_options)): ?>
                                                <?php $filter_options = array_map('trim', explode(',', $field->filter_options)); ?>
                                                <div style="margin-bottom: 15px;">
                                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">
                                                        Valeur pour le filtre :
                                                    </label>
                                                    <select name="field_<?php echo $field->id; ?>_filter" class="regular-text">
                                                        <option value="">-- Choisir une option --</option>
                                                        <?php foreach ($filter_options as $option): ?>
                                                            <option value="<?php echo esc_attr($option); ?>" <?php selected(isset($current_filter_value) ? $current_filter_value : '', $option); ?>>
                                                                <?php echo esc_html($option); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <p class="description">
                                                        <strong>Champ filtrable :</strong> Cette valeur sera utilisée pour le filtrage
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div style="margin-bottom: 15px;">
                                                <label for="field_<?php echo $field->id; ?>" style="font-weight: bold; display: block; margin-bottom: 5px;">
                                                    Description courte :
                                                </label>
                                                <textarea id="field_<?php echo $field->id; ?>" name="field_<?php echo $field->id; ?>" rows="3" class="large-text"><?php echo esc_textarea(isset($current_value) ? $current_value : ''); ?></textarea>
                                                <p class="description">Texte qui s'affichera directement dans le comparateur</p>
                                            </div>
                                            
                                            <div style="margin-top: 10px;">
                                                <label for="long_desc_<?php echo $field->id; ?>" style="font-weight: bold; display: block; margin-bottom: 5px;">
                                                    Description longue (optionnelle) :
                                                </label>
                                                <textarea id="long_desc_<?php echo $field->id; ?>" name="long_desc_<?php echo $field->id; ?>" rows="4" class="large-text" placeholder="Description détaillée qui s'affichera avec le bouton 'En savoir plus'..."><?php echo esc_textarea(isset($current_long_desc) ? $current_long_desc : ''); ?></textarea>
                                            </div>
                                            
                                            <?php if ($field->long_description): ?>
                                                <p class="description">
                                                    <strong>Description détaillée :</strong> <?php echo esc_html($field->long_description); ?>
                                                </p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Sauvegarder les données">
                    <a href="?page=wp-comparator-items&type_id=<?php echo $type_id; ?>" class="button">Retour à la liste</a>
                </p>
            </form>
        </div>
        <?php
    }
    
    private function handle_add_type() {
        global $wpdb;
        $table_types = $wpdb->prefix . 'comparator_types';
        
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? $this->generate_slug($_POST['slug']) : $this->generate_slug($name);
        $description = sanitize_textarea_field($_POST['description']);
        $intro_text = sanitize_textarea_field($_POST['intro_text']);
        $custom_title = sanitize_textarea_field($_POST['custom_title']);
        $meta_title = sanitize_textarea_field($_POST['meta_title']);
        $meta_description = sanitize_textarea_field($_POST['meta_description']);
        
        // Vérifier que le slug est unique
        $existing_slug = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_types WHERE slug = %s",
            $slug
        ));
        
        if ($existing_slug > 0) {
            $counter = 1;
            $original_slug = $slug;
            while ($existing_slug > 0) {
                $slug = $original_slug . '-' . $counter;
                $existing_slug = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_types WHERE slug = %s",
                    $slug
                ));
                $counter++;
            }
        }
        
        $result = $wpdb->insert($table_types, array(
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'intro_text' => $intro_text,
            'custom_title' => $custom_title,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description
        ));
        
        if ($result) {
            wp_redirect(add_query_arg('message', 'type_added', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'type_not_added', wp_get_referer()));
        }
        exit;
    }
    
    private function handle_update_type() {
        global $wpdb;
        $table_types = $wpdb->prefix . 'comparator_types';
        
        $type_id = intval($_POST['type_id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? $this->generate_slug($_POST['slug']) : $this->generate_slug($name);
        $description = sanitize_textarea_field($_POST['description']);
        $intro_text = sanitize_textarea_field($_POST['intro_text']);
        $custom_title = sanitize_textarea_field($_POST['custom_title']);
        $meta_title = sanitize_textarea_field($_POST['meta_title']);
        $meta_description = sanitize_textarea_field($_POST['meta_description']);
        
        // Vérifier que le slug est unique (sauf pour ce type)
        $existing_slug = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_types WHERE slug = %s AND id != %d",
            $slug, $type_id
        ));
        
        if ($existing_slug > 0) {
            $counter = 1;
            $original_slug = $slug;
            while ($existing_slug > 0) {
                $slug = $original_slug . '-' . $counter;
                $existing_slug = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_types WHERE slug = %s AND id != %d",
                    $slug, $type_id
                ));
                $counter++;
            }
        }
        
        $result = $wpdb->update(
            $table_types,
            array(
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'intro_text' => $intro_text,
                'custom_title' => $custom_title,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description
            ),
            array('id' => $type_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(add_query_arg('message', 'type_updated', remove_query_arg('edit_type')));
        } else {
            wp_redirect(add_query_arg('error', 'type_not_updated', wp_get_referer()));
        }
        exit;
    }
    
    private function handle_add_field() {
        global $wpdb;
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        $type_id = intval($_POST['type_id']);
        $name = sanitize_text_field($_POST['name']);
        $field_type = sanitize_text_field($_POST['field_type']);
        $parent_category_id = ($field_type === 'description' && !empty($_POST['parent_category_id'])) ? intval($_POST['parent_category_id']) : null;
        $has_info_button = isset($_POST['has_info_button']) ? intval($_POST['has_info_button']) : 0;
        $info_content = isset($_POST['info_content']) ? sanitize_textarea_field($_POST['info_content']) : '';
        $short_description = isset($_POST['short_description']) ? sanitize_textarea_field($_POST['short_description']) : '';
        $long_description = isset($_POST['long_description']) ? sanitize_textarea_field($_POST['long_description']) : '';
        $is_filterable = isset($_POST['is_filterable']) ? 1 : 0;
        $filter_name = isset($_POST['filter_name']) ? sanitize_text_field($_POST['filter_name']) : '';
        $filter_options = '';
        
        // Traiter les options de filtre
        if ($is_filterable && isset($_POST['filter_option']) && is_array($_POST['filter_option'])) {
            $options = array_filter(array_map('trim', $_POST['filter_option']));
            $filter_options = implode(',', $options);
        }
        
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        
        $result = $wpdb->insert($table_fields, array(
            'type_id' => $type_id,
            'name' => $name,
            'field_type' => $field_type,
            'parent_category_id' => $parent_category_id,
            'has_info_button' => $has_info_button,
            'info_content' => $info_content,
            'short_description' => $short_description,
            'long_description' => $long_description,
            'is_filterable' => $is_filterable,
            'filter_name' => $filter_name,
            'filter_options' => $filter_options,
            'sort_order' => $sort_order
        ));
        
        if ($result) {
            wp_redirect(add_query_arg('message', 'field_added', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'field_not_added', wp_get_referer()));
        }
        exit;
    }
    
    private function handle_update_field() {
        global $wpdb;
        $table_fields = $wpdb->prefix . 'comparator_fields';
        
        $field_id = intval($_POST['field_id']);
        $name = sanitize_text_field(stripslashes($_POST['name']));
        $field_type = sanitize_text_field($_POST['field_type']);
        $parent_category_id = ($field_type === 'description' && !empty($_POST['parent_category_id'])) ? intval($_POST['parent_category_id']) : null;
        $has_info_button = isset($_POST['has_info_button']) ? intval($_POST['has_info_button']) : 0;
        $info_content = isset($_POST['info_content']) ? sanitize_textarea_field(stripslashes($_POST['info_content'])) : '';
        $short_description = isset($_POST['short_description']) ? sanitize_textarea_field(stripslashes($_POST['short_description'])) : '';
        $long_description = isset($_POST['long_description']) ? sanitize_textarea_field(stripslashes($_POST['long_description'])) : '';
        $is_filterable = isset($_POST['is_filterable']) ? 1 : 0;
        $filter_name = isset($_POST['filter_name']) ? sanitize_text_field(stripslashes($_POST['filter_name'])) : '';
        $filter_options = '';
        
        // Traiter les options de filtre
        if ($is_filterable && isset($_POST['filter_option']) && is_array($_POST['filter_option'])) {
            $options = array_filter(array_map(function($option) {
                return trim(stripslashes($option));
            }, $_POST['filter_option']));
            $filter_options = implode(',', $options);
        }
        
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        
        $result = $wpdb->update(
            $table_fields,
            array(
                'name' => $name,
                'field_type' => $field_type,
                'parent_category_id' => $parent_category_id,
                'has_info_button' => $has_info_button,
                'info_content' => $info_content,
                'short_description' => $short_description,
                'long_description' => $long_description,
                'is_filterable' => $is_filterable,
                'filter_name' => $filter_name,
                'filter_options' => $filter_options,
                'sort_order' => $sort_order
            ),
            array('id' => $field_id)
        );
        
        if ($result !== false) {
            wp_redirect(add_query_arg(array(
                'page' => 'wp-comparator-fields',
                'type_id' => $_POST['type_id'],
                'message' => 'field_updated'
            ), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array(
                'page' => 'wp-comparator-fields',
                'type_id' => $_POST['type_id'],
                'error' => 'field_not_updated'
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    private function handle_add_filter() {
        global $wpdb;
        $table_filters = $wpdb->prefix . 'comparator_filters';
        
        $type_id = intval($_POST['type_id']);
        $filter_titles = $_POST['filter_title'];
        $filter_fields = $_POST['filter_field'];
        
        // Supprimer les anciens filtres pour ce type
        $wpdb->delete($table_filters, array('type_id' => $type_id));
        
        // Ajouter les nouveaux filtres
        $sort_order = 1;
        foreach ($filter_titles as $index => $title) {
            if (!empty($title) && !empty($filter_fields[$index])) {
                $wpdb->insert($table_filters, array(
                    'type_id' => $type_id,
                    'filter_title' => sanitize_text_field($title),
                    'filter_field' => sanitize_text_field($filter_fields[$index]),
                    'sort_order' => $sort_order
                ));
                $sort_order++;
            }
        }
        
        wp_redirect(add_query_arg('message', 'filters_saved', wp_get_referer()));
        exit;
    }
    
    private function handle_add_item() {
        global $wpdb;
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $type_id = intval($_POST['type_id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? $this->generate_slug($_POST['slug']) : $this->generate_slug($name);
        $description = sanitize_textarea_field($_POST['description']);
        $logo_url = esc_url_raw($_POST['logo_url']);
        $contrat = sanitize_text_field($_POST['contrat']);
        $document_url = esc_url_raw($_POST['document_url']);
        $version = sanitize_text_field($_POST['version']);
        $assureur = sanitize_text_field($_POST['assureur']);
        $territorialite = sanitize_text_field($_POST['territorialite']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order']);
        
        $result = $wpdb->insert($table_items, array(
            'type_id' => $type_id,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'logo_url' => $logo_url,
            'contrat' => $contrat,
            'document_url' => $document_url,
            'version' => $version,
            'assureur' => $assureur,
            'territorialite' => $territorialite,
            'is_active' => $is_active,
            'sort_order' => $sort_order
        ));
        
        if ($result) {
            wp_redirect(add_query_arg(array('message' => 'item_added', 'edit_item' => $wpdb->insert_id), wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'item_not_added', wp_get_referer()));
        }
        exit;
    }
    
    private function handle_save_item_data() {
        global $wpdb;
        $table_values = $wpdb->prefix . 'comparator_values';
        $table_filter_values = $wpdb->prefix . 'comparator_filter_values';
        $table_item_filters = $wpdb->prefix . 'comparator_item_filters';
        $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
        
        $item_id = intval($_POST['item_id']);
        $type_id = intval($_POST['type_id']);
        
        // Vérifier que la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_field_descriptions'") != $table_field_descriptions) {
            wp_redirect(add_query_arg(array(
                'page' => 'wp-comparator-items',
                'type_id' => $type_id,
                'error' => 'table_missing'
            ), admin_url('admin.php')));
            exit;
        }
        
        // Sauvegarder les valeurs de filtres des champs filtrables
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'field_') === 0 && strpos($key, '_filter') !== false) {
                // Traiter les valeurs de filtre dans la nouvelle table
                $field_id = intval(str_replace(array('field_', '_filter'), '', $key));
                $filter_value = sanitize_text_field($value);
                
                $existing_filter = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_item_filters WHERE item_id = %d AND field_id = %d",
                    $item_id, $field_id
                ));
                
                if ($existing_filter) {
                    $wpdb->update(
                        $table_item_filters,
                        array('filter_value' => $filter_value),
                        array('item_id' => $item_id, 'field_id' => $field_id)
                    );
                } else {
                    $wpdb->insert(
                        $table_item_filters,
                        array(
                            'item_id' => $item_id,
                            'field_id' => $field_id,
                            'filter_value' => $filter_value
                        )
                    );
                }
            }
        }
        
        // Sauvegarder les descriptions courtes des champs
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'field_') === 0 && strpos($key, '_filter') === false) {
                // Traiter les descriptions courtes des champs
                $field_id = intval(str_replace('field_', '', $key));
                $value = sanitize_textarea_field($value);
                
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_values WHERE item_id = %d AND field_id = %d",
                    $item_id, $field_id
                ));
                
                if ($existing) {
                    $wpdb->update(
                        $table_values,
                        array('value' => $value),
                        array('item_id' => $item_id, 'field_id' => $field_id)
                    );
                } else {
                    $wpdb->insert(
                        $table_values,
                        array(
                            'item_id' => $item_id,
                            'field_id' => $field_id,
                            'value' => $value
                        )
                    );
                }
            }
        }
        
        // Sauvegarder les descriptions longues des champs
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'long_desc_') === 0) {
                $field_id = intval(str_replace('long_desc_', '', $key));
                $long_description = sanitize_textarea_field($value);
                
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_field_descriptions WHERE item_id = %d AND field_id = %d",
                    $item_id, $field_id
                ));
                
                if (!empty($long_description)) {
                    if ($existing) {
                        $wpdb->update(
                            $table_field_descriptions,
                            array('long_description' => $long_description),
                            array('item_id' => $item_id, 'field_id' => $field_id),
                            array('%s'),
                            array('%d', '%d')
                        );
                    } else {
                        $wpdb->insert(
                            $table_field_descriptions,
                            array(
                                'item_id' => $item_id,
                                'field_id' => $field_id,
                                'long_description' => $long_description
                            ),
                            array('%d', '%d', '%s')
                        );
                    }
                } elseif ($existing) {
                    // Supprimer si la description longue est vidée
                    $wpdb->delete(
                        $table_field_descriptions,
                        array('item_id' => $item_id, 'field_id' => $field_id),
                        array('%d', '%d')
                    );
                }
            }
        }
        
        // Sauvegarder les valeurs des filtres
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'filter_') === 0) {
                $filter_id = intval(str_replace('filter_', '', $key));
                $value = sanitize_text_field($value);
                
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_filter_values WHERE item_id = %d AND filter_id = %d",
                    $item_id, $filter_id
                ));
                
                if ($existing) {
                    $wpdb->update(
                        $table_filter_values,
                        array('value' => $value),
                        array('item_id' => $item_id, 'filter_id' => $filter_id)
                    );
                } else {
                    $wpdb->insert(
                        $table_filter_values,
                        array(
                            'item_id' => $item_id,
                            'filter_id' => $filter_id,
                            'value' => $value
                        )
                    );
                }
            }
        }
        
        wp_redirect(add_query_arg(array(
            'page' => 'wp-comparator-items',
            'type_id' => $type_id,
            'message' => 'item_data_saved'
        ), admin_url('admin.php')));
        exit;
    }
    
    public function render_edit_contract_form($type_id, $item_id) {
        global $wpdb;
        
        $table_items = $wpdb->prefix . 'comparator_items';
        
        // Récupérer l'élément
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_items WHERE id = %d AND type_id = %d",
            $item_id, $type_id
        ));
        
        if (!$item) {
            echo '<div class="notice notice-error"><p>Contrat non trouvé.</p></div>';
            return;
        }
        
        ?>
        <div class="admin-section">
            <h2>Modifier le contrat : <?php echo esc_html($item->name); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('wp_comparator_update_item', '_wpnonce'); ?>
                <input type="hidden" name="wp_comparator_action" value="update_item">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                <input type="hidden" name="type_id" value="<?php echo $type_id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="name">Nom du contrat/produit</label>
                        </th>
                        <td>
                            <input type="text" id="name" name="name" class="regular-text" value="<?php echo esc_attr($item->name); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="slug">Slug</label>
                        </th>
                        <td>
                            <input type="text" id="slug" name="slug" class="regular-text" value="<?php echo esc_attr($item->slug); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description">Description</label>
                        </th>
                        <td>
                            <textarea id="description" name="description" rows="4" class="large-text"><?php echo esc_textarea($item->description); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="logo_url">URL du logo</label>
                        </th>
                        <td>
                            <input type="url" id="logo_url" name="logo_url" class="regular-text" value="<?php echo esc_attr($item->logo_url); ?>">
                            <p class="description">URL de l'image du logo à afficher</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contrat">Contrat</label>
                        </th>
                        <td>
                            <input type="text" id="contrat" name="contrat" class="regular-text" value="<?php echo esc_attr($item->contrat); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="document_url">URL du document (PDF)</label>
                        </th>
                        <td>
                            <input type="url" id="document_url" name="document_url" class="regular-text" value="<?php echo esc_attr($item->document_url); ?>">
                            <p class="description">URL du document PDF à télécharger</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Statut</th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_active" value="1" <?php checked($item->is_active, 1); ?>>
                                Contrat actif (visible sur le site)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="version">Version</label>
                        </th>
                        <td>
                            <input type="text" id="version" name="version" class="regular-text" value="<?php echo esc_attr($item->version); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="assureur">Assureur</label>
                        </th>
                        <td>
                            <input type="text" id="assureur" name="assureur" class="regular-text" value="<?php echo esc_attr($item->assureur); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="territorialite">Territorialité</label>
                        </th>
                        <td>
                            <input type="text" id="territorialite" name="territorialite" class="regular-text" value="<?php echo esc_attr($item->territorialite); ?>">
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Mettre à jour le contrat">
                    <a href="?page=wp-comparator-items&type_id=<?php echo $type_id; ?>" class="button">Annuler</a>
                </p>
            </form>
        </div>
        <?php
    }
    
    private function handle_update_item() {
        global $wpdb;
        $table_items = $wpdb->prefix . 'comparator_items';
        
        $item_id = intval($_POST['item_id']);
        $type_id = intval($_POST['type_id']);
        $name = sanitize_text_field($_POST['name']);
        $logo_url = esc_url_raw($_POST['logo_url']);
        $slug = $this->generate_slug($_POST['slug']);
        $description = sanitize_textarea_field($_POST['description']);
        $contrat = sanitize_text_field($_POST['contrat']);
        $document_url = esc_url_raw($_POST['document_url']);
        $version = sanitize_text_field($_POST['version']);
        $assureur = sanitize_text_field($_POST['assureur']);
        $territorialite = sanitize_text_field($_POST['territorialite']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        
        $result = $wpdb->update(
            $table_items,
            array(
                'name' => $name,
                'logo_url' => $logo_url,
                'slug' => $slug,
                'description' => $description,
                'contrat' => $contrat,
                'document_url' => $document_url,
                'version' => $version,
                'assureur' => $assureur,
                'territorialite' => $territorialite,
                'is_active' => $is_active,
                'sort_order' => $sort_order
            ),
            array('id' => $item_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(add_query_arg(array(
                'page' => 'wp-comparator-items',
                'type_id' => $type_id,
                'message' => 'item_updated'
            ), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array(
                'page' => 'wp-comparator-items',
                'type_id' => $type_id,
                'error' => 'item_not_updated'
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Vérifier toutes les tables
     */
    private function check_all_tables() {
        global $wpdb;
        
        $tables_to_check = array(
            'comparator_types',
            'comparator_fields', 
            'comparator_filters',
            'comparator_items',
            'comparator_values',
            'comparator_filter_values',
            'comparator_field_descriptions'
        );
        
        $missing_tables = array();
        
        foreach ($tables_to_check as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            if ($wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") != $full_table_name) {
                $missing_tables[] = $table_name;
            }
        }
        
        if (!empty($missing_tables)) {
            // Créer les tables manquantes
            $database = new WP_Comparator_Database();
            $database->create_tables();
        }
    }
    
    /**
     * Générer un slug propre avec gestion des accents
     */
    private function generate_slug($text) {
        // Convertir les caractères accentués
        $text = remove_accents($text);
        
        // Convertir en minuscules
        $text = strtolower($text);
        
        // Remplacer les espaces et caractères spéciaux par des tirets
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // Supprimer les tirets en début et fin
        $text = trim($text, '-');
        
        // Supprimer les tirets multiples
        $text = preg_replace('/-+/', '-', $text);
        
        return $text;
    }
}