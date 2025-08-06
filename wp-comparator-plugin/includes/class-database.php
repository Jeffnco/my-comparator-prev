<?php

class WP_Comparator_Database {
    
    public function __construct() {
        // Constructor vide pour l'instant
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des types de comparateurs
        $table_types = $wpdb->prefix . 'comparator_types';
        $sql_types = "CREATE TABLE $table_types (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            url_prefix varchar(255) NULL,
            intro_text text,
            custom_title text,
            meta_title text,
            meta_description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        // Table des champs (catégories ET champs description)
        $table_fields = $wpdb->prefix . 'comparator_fields';
        $sql_fields = "CREATE TABLE $table_fields (
            id int(11) NOT NULL AUTO_INCREMENT,
            type_id int(11) NOT NULL,
            name varchar(255) NOT NULL,
            field_type enum('category', 'description') DEFAULT 'description',
            parent_category_id int(11) NULL,
            has_info_button tinyint(1) DEFAULT 0,
            info_content text,
            short_description text,
            long_description text,
            is_filterable tinyint(1) DEFAULT 0,
            filter_name varchar(255) NULL,
            filter_options text NULL,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type_id (type_id),
            KEY parent_category_id (parent_category_id)
        ) $charset_collate;";
        
        // Table des filtres
        $table_filters = $wpdb->prefix . 'comparator_filters';
        $sql_filters = "CREATE TABLE $table_filters (
            id int(11) NOT NULL AUTO_INCREMENT,
            type_id int(11) NOT NULL,
            filter_title varchar(255) NOT NULL,
            filter_field varchar(255) NOT NULL,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type_id (type_id)
        ) $charset_collate;";
        
        // Table des contrats/produits
        $table_items = $wpdb->prefix . 'comparator_items';
        $sql_items = "CREATE TABLE $table_items (
            id int(11) NOT NULL AUTO_INCREMENT,
            type_id int(11) NOT NULL,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            logo_url varchar(500),
            contrat varchar(255),
            document_url varchar(500),
            version varchar(100),
            assureur varchar(255),
            territorialite varchar(255),
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type_id (type_id),
            UNIQUE KEY type_slug (type_id, slug)
        ) $charset_collate;";
        
        // Table des valeurs des champs pour chaque contrat
        $table_values = $wpdb->prefix . 'comparator_values';
        $sql_values = "CREATE TABLE $table_values (
            id int(11) NOT NULL AUTO_INCREMENT,
            item_id int(11) NOT NULL,
            field_id int(11) NOT NULL,
            value text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY item_field (item_id, field_id),
            KEY item_id (item_id),
            KEY field_id (field_id)
        ) $charset_collate;";
        
        // Table des valeurs de filtres pour chaque contrat
        $table_filter_values = $wpdb->prefix . 'comparator_filter_values';
        $sql_filter_values = "CREATE TABLE $table_filter_values (
            id int(11) NOT NULL AUTO_INCREMENT,
            item_id int(11) NOT NULL,
            filter_id int(11) NOT NULL,
            value varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY item_filter (item_id, filter_id),
            KEY item_id (item_id),
            KEY filter_id (filter_id)
        ) $charset_collate;";
        
        // Table des valeurs de filtres par champ pour chaque contrat
        $table_item_filters = $wpdb->prefix . 'comparator_item_filters';
        $sql_item_filters = "CREATE TABLE $table_item_filters (
            id int(11) NOT NULL AUTO_INCREMENT,
            item_id int(11) NOT NULL,
            field_id int(11) NOT NULL,
            filter_value varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY item_field_filter (item_id, field_id),
            KEY item_id (item_id),
            KEY field_id (field_id)
        ) $charset_collate;";
        
        // Table des descriptions longues par champ et par contrat
        $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
        $sql_field_descriptions = "CREATE TABLE $table_field_descriptions (
            id int(11) NOT NULL AUTO_INCREMENT,
            item_id int(11) NOT NULL,
            field_id int(11) NOT NULL,
            long_description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY item_field_desc (item_id, field_id),
            KEY item_id (item_id),
            KEY field_id (field_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Créer les tables
        dbDelta($sql_types);
        dbDelta($sql_fields);
        dbDelta($sql_filters);
        dbDelta($sql_items);
        dbDelta($sql_values);
        dbDelta($sql_filter_values);
        dbDelta($sql_item_filters);
        dbDelta($sql_item_filters);
        dbDelta($sql_field_descriptions);
        
        // Vérifier que toutes les tables sont bien créées
        $this->verify_tables_exist();
        
        // Vérifier et ajouter les nouvelles colonnes si nécessaire
        $this->add_missing_columns();
        
        // Marquer que les tables sont créées
        update_option('wp_comparator_tables_created', '1');
        
        // Insérer des données de démonstration
        $this->insert_demo_data();
    }
    
    /**
     * Vérifier que toutes les tables existent et les créer si nécessaire
     */
    public function verify_tables_exist() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Vérifier la table des descriptions longues
        $table_field_descriptions = $wpdb->prefix . 'comparator_field_descriptions';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_field_descriptions'") != $table_field_descriptions) {
            $sql_field_descriptions = "CREATE TABLE $table_field_descriptions (
                id int(11) NOT NULL AUTO_INCREMENT,
                item_id int(11) NOT NULL,
                field_id int(11) NOT NULL,
                long_description text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY item_field_desc (item_id, field_id),
                KEY item_id (item_id),
                KEY field_id (field_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_field_descriptions);
        }
    }
    
    /**
     * Ajouter les colonnes manquantes aux tables existantes
     */
    public function add_missing_columns() {
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        
        // Vérifier si la colonne url_prefix existe
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_types LIKE %s",
            'url_prefix'
        ));
        
        // Ajouter la colonne si elle n'existe pas
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_types ADD COLUMN url_prefix VARCHAR(255) NULL AFTER description");
        }
    }
    
    private function insert_demo_data() {
        global $wpdb;
        
        $table_types = $wpdb->prefix . 'comparator_types';
        $table_fields = $wpdb->prefix . 'comparator_fields';
        $table_filters = $wpdb->prefix . 'comparator_filters';
        
        // Ne pas insérer de données de démonstration automatiquement
        // L'utilisateur créera ses propres données
        return;
        
    }
}