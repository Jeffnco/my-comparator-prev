<div class="wrap">
    <h1>Catégories de champs</h1>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'category_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Catégorie ajoutée avec succès !</p>
        </div>
    <?php endif; ?>
    
    <div class="wp-comparator-admin-content">
        <!-- Sélecteur de type -->
        <div class="admin-section">
            <h2>Sélectionner un type de comparateur</h2>
            <form method="get" action="">
                <input type="hidden" name="page" value="wp-comparator-categories">
                <select name="type_id" onchange="this.form.submit()">
                    <option value="">-- Choisir un type --</option>
                    <?php if (isset($types) && is_array($types)): ?>
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type->id; ?>" <?php selected($selected_type, $type->id); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
        </div>
        
        <?php if ($selected_type): ?>
            <!-- Formulaire d'ajout de catégorie -->
            <div class="admin-section">
                <h2>Ajouter une nouvelle catégorie</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('wp_comparator_add_category', '_wpnonce'); ?>
                    <input type="hidden" name="wp_comparator_action" value="add_category">
                    <input type="hidden" name="type_id" value="<?php echo $selected_type; ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="name">Nom de la catégorie</label>
                            </th>
                            <td>
                                <input type="text" id="name" name="name" class="regular-text" required>
                                <p class="description">Ex: Délais d'attente, Mode d'indemnisation</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="slug">Slug (optionnel)</label>
                            </th>
                            <td>
                                <input type="text" id="slug" name="slug" class="regular-text">
                                <p class="description">Laissez vide pour générer automatiquement</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="description">Description</label>
                            </th>
                            <td>
                                <textarea id="description" name="description" rows="3" class="large-text"></textarea>
                                <p class="description">Description qui apparaîtra dans l'infobulle</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="sort_order">Ordre d'affichage</label>
                            </th>
                            <td>
                                <input type="number" id="sort_order" name="sort_order" value="0" min="0" class="small-text">
                                <p class="description">Plus le nombre est petit, plus la catégorie apparaîtra en haut</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="Ajouter la catégorie">
                    </p>
                </form>
            </div>
            
            <!-- Liste des catégories existantes -->
            <div class="admin-section">
                <h2>Catégories existantes</h2>
                
                <?php if (!isset($categories) || empty($categories)): ?>
                    <p>Aucune catégorie créée pour ce type.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Ordre</th>
                                <th>Nom</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category->sort_order; ?></td>
                                    <td><strong><?php echo esc_html($category->name); ?></strong></td>
                                    <td><code><?php echo esc_html($category->slug); ?></code></td>
                                    <td><?php echo esc_html($category->description); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=wp-comparator-fields&type_id=' . $selected_type . '&category_id=' . $category->id); ?>" class="button button-small">
                                            Gérer les champs
                                        </a>
                                        <button class="button button-small button-link-delete" onclick="deleteCategory(<?php echo $category->id; ?>)">
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="admin-section">
                <p>Veuillez d'abord sélectionner un type de comparateur pour gérer ses catégories.</p>
                <p><a href="<?php echo admin_url('admin.php?page=wp-comparator-types'); ?>" class="button">Créer un nouveau type</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteCategory(categoryId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ? Tous les champs associés seront supprimés.')) {
        // Implémentation de la suppression via AJAX
        console.log('Suppression de la catégorie:', categoryId);
    }
}
</script>