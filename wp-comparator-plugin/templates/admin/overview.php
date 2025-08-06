<div class="wrap">
    <h1>WP Comparator - Vue d'ensemble</h1>
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>Action effectuée avec succès !</p>
        </div>
    <?php endif; ?>
    
    <div class="wp-comparator-dashboard">
        <div class="dashboard-widgets">
            <div class="dashboard-widget">
                <h3>📊 Statistiques</h3>
                <div class="stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo isset($types_count) ? $types_count : 0; ?></span>
                        <span class="stat-label">Types de comparateurs</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo isset($items_count) ? $items_count : 0; ?></span>
                        <span class="stat-label">Éléments à comparer</span>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-widget">
                <h3>🚀 Actions rapides</h3>
                <div class="quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=wp-comparator-types'); ?>" class="button button-primary">
                        Gérer les types
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=wp-comparator-items'); ?>" class="button button-secondary">
                        Gérer les éléments
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=wp-comparator-fields'); ?>" class="button button-secondary">
                        Gérer les champs
                    </a>
                </div>
            </div>
            
            <div class="dashboard-widget">
                <h3>📖 Guide d'utilisation</h3>
                <div class="guide-steps">
                    <ol>
                        <li><strong>Créez un type</strong> de comparateur (ex: Assurance, Produits, Services)</li>
                        <li><strong>Définissez les catégories</strong> pour organiser vos champs</li>
                        <li><strong>Ajoutez des champs</strong> de comparaison dans chaque catégorie</li>
                        <li><strong>Créez les éléments</strong> à comparer et remplissez leurs données</li>
                        <li><strong>Utilisez les shortcodes</strong> pour afficher vos comparateurs</li>
                    </ol>
                </div>
            </div>
            
            <div class="dashboard-widget">
                <h3>🔧 Shortcodes disponibles</h3>
                <div class="shortcodes-list">
                    <code>[wp_comparator type="slug-du-type"]</code>
                    <p>Affiche la page de sélection avec vignettes</p>
                    
                    <code>[wp_comparator_compare type="slug-du-type" items="slug1,slug2"]</code>
                    <p>Compare deux éléments côte à côte</p>
                    
                    <code>[wp_comparator_single type="slug-du-type" item="slug-element"]</code>
                    <p>Affiche un seul élément en détail</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.wp-comparator-dashboard {
    margin-top: 20px;
}

.dashboard-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.dashboard-widget {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.dashboard-widget h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
}

.stats {
    display: flex;
    gap: 20px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
    line-height: 1;
}

.stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.guide-steps ol {
    margin: 0;
    padding-left: 20px;
}

.guide-steps li {
    margin-bottom: 8px;
}

.shortcodes-list code {
    display: block;
    background: #f1f1f1;
    padding: 8px;
    margin-bottom: 5px;
    border-radius: 3px;
}

.shortcodes-list p {
    margin-bottom: 15px;
    color: #666;
    font-size: 13px;
}
</style>