<div class="wrap">
    <h1>Filtres</h1>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'filters_saved'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Filtres sauvegardés avec succès !</p>
        </div>
    <?php endif; ?>
    
    <div class="wp-comparator-admin-content">
        <!-- Sélecteur de type -->
        <div class="admin-section">
            <h2>Sélectionner un type de comparateur</h2>
            <form method="get" action="">
                <input type="hidden" name="page" value="wp-comparator-filters">
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
            <!-- Formulaire de gestion des filtres -->
            <div class="admin-section">
                <h2>Gérer les filtres</h2>
                <p>Créez des filtres pour permettre aux utilisateurs de filtrer les résultats sur le site.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('wp_comparator_add_filter', '_wpnonce'); ?>
                    <input type="hidden" name="wp_comparator_action" value="add_filter">
                    <input type="hidden" name="type_id" value="<?php echo $selected_type; ?>">
                    
                    <div id="filters-container">
                        <?php if (!empty($filters)): ?>
                            <?php foreach ($filters as $index => $filter): ?>
                                <div class="filter-row" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row" style="width: 150px;">
                                                <label>Titre du filtre</label>
                                            </th>
                                            <td>
                                                <input type="text" name="filter_title[]" value="<?php echo esc_attr($filter->filter_title); ?>" class="regular-text" placeholder="Ex: Délai maladie">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label>Champ filtre</label>
                                            </th>
                                            <td>
                                                <input type="text" name="filter_field[]" value="<?php echo esc_attr($filter->filter_field); ?>" class="regular-text" placeholder="Ex: delai_maladie">
                                                <p class="description">Nom technique du champ (sans espaces, en minuscules)</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <button type="button" class="button button-link-delete" onclick="removeFilter(this)">Supprimer ce filtre</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="filter-row" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row" style="width: 150px;">
                                            <label>Titre du filtre</label>
                                        </th>
                                        <td>
                                            <input type="text" name="filter_title[]" class="regular-text" placeholder="Ex: Délai maladie">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label>Champ filtre</label>
                                        </th>
                                        <td>
                                            <input type="text" name="filter_field[]" class="regular-text" placeholder="Ex: delai_maladie">
                                            <p class="description">Nom technique du champ (sans espaces, en minuscules)</p>
                                        </td>
                                    </tr>
                                </table>
                                <button type="button" class="button button-link-delete" onclick="removeFilter(this)">Supprimer ce filtre</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <p>
                        <button type="button" id="add-filter-btn" class="button">Ajouter un autre filtre</button>
                    </p>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="Sauvegarder les filtres">
                    </p>
                </form>
            </div>
            
            <!-- Aperçu des filtres -->
            <?php if (!empty($filters)): ?>
                <div class="admin-section">
                    <h2>Aperçu des filtres</h2>
                    <p>Voici comment les filtres apparaîtront sur le site :</p>
                    
                    <div style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
                        <h3>Filtrer les résultats</h3>
                        <?php foreach ($filters as $filter): ?>
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: bold; display: block; margin-bottom: 5px;">
                                    <?php echo esc_html($filter->filter_title); ?>
                                </label>
                                <select style="width: 200px;">
                                    <option>Tous</option>
                                    <option>Option 1</option>
                                    <option>Option 2</option>
                                </select>
                            </div>
                        <?php endforeach; ?>
                        <button class="button-primary">Filtrer</button>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="admin-section">
                <p>Veuillez d'abord sélectionner un type de comparateur pour gérer ses filtres.</p>
                <p><a href="<?php echo admin_url('admin.php?page=wp-comparator-types'); ?>" class="button">Créer un nouveau type</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addFilterBtn = document.getElementById('add-filter-btn');
    const filtersContainer = document.getElementById('filters-container');
    
    addFilterBtn.addEventListener('click', function() {
        const filterRow = document.createElement('div');
        filterRow.className = 'filter-row';
        filterRow.style.cssText = 'margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;';
        
        filterRow.innerHTML = `
            <table class="form-table">
                <tr>
                    <th scope="row" style="width: 150px;">
                        <label>Titre du filtre</label>
                    </th>
                    <td>
                        <input type="text" name="filter_title[]" class="regular-text" placeholder="Ex: Délai maladie">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label>Champ filtre</label>
                    </th>
                    <td>
                        <input type="text" name="filter_field[]" class="regular-text" placeholder="Ex: delai_maladie">
                        <p class="description">Nom technique du champ (sans espaces, en minuscules)</p>
                    </td>
                </tr>
            </table>
            <button type="button" class="button button-link-delete" onclick="removeFilter(this)">Supprimer ce filtre</button>
        `;
        
        filtersContainer.appendChild(filterRow);
    });
});

function removeFilter(button) {
    const filterRow = button.closest('.filter-row');
    filterRow.remove();
}
</script>