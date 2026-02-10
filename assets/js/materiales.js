/**
 * Materials Management JavaScript
 * Handles CRUD operations for materials
 */

let materialsData = [];
let categorias = [];

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', async () => {
    await loadMaterials();
    setupFilters();

    // Check for URL params
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('bajo_stock') === '1') {
        document.getElementById('filter-bajo-stock').checked = true;
        filterMaterials();
    }
    if (urlParams.get('search')) {
        document.getElementById('search-input').value = urlParams.get('search');
        filterMaterials();
    }
});

/**
 * Load materials from API
 */
async function loadMaterials() {
    try {
        Loading.show('#materials-table');

        const response = await MaterialesService.getAll();
        materialsData = response.data;
        categorias = response.categorias || [];

        // Populate category filter
        const catSelect = document.getElementById('filter-categoria');
        catSelect.innerHTML = '<option value="">Todas las categorías</option>' +
            categorias.map(c => `<option value="${c}">${c}</option>`).join('');

        renderTable(materialsData);

    } catch (error) {
        console.error('Error loading materials:', error);
        Toast.error('Error al cargar materiales');
    }
}

/**
 * Render materials table
 */
function renderTable(data) {
    DataTable.init('materials-table', {
        data: data,
        columns: [
            {
                key: 'codigo',
                label: 'Código',
                render: (val) => val || '<span class="text-muted">-</span>'
            },
            { key: 'nombre', label: 'Nombre' },
            { key: 'categoria', label: 'Categoría', render: (val) => val || '-' },
            { key: 'unidad_medida', label: 'Unidad' },
            {
                key: 'stock_actual',
                label: 'Stock',
                render: (val, row) => {
                    const status = getStockStatus(row.stock_actual, row.stock_minimo);
                    return `
                        <div class="d-flex align-center gap-sm">
                            <span>${formatNumber(val, 2)}</span>
                            <span class="stock-indicator ${status.class}">${status.text}</span>
                        </div>
                    `;
                }
            },
            {
                key: 'stock_minimo',
                label: 'Mínimo',
                render: (val) => formatNumber(val, 2)
            }
        ],
        pageSize: 10,
        searchable: false, // We have custom search
        onEdit: 'editMaterial',
        onDelete: 'deleteMaterial'
    });
}

/**
 * Setup filter listeners
 */
function setupFilters() {
    // Search with debounce
    let searchTimeout;
    document.getElementById('search-input').addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterMaterials, 300);
    });

    // Category filter
    document.getElementById('filter-categoria').addEventListener('change', filterMaterials);

    // Low stock filter
    document.getElementById('filter-bajo-stock').addEventListener('change', filterMaterials);
}

/**
 * Apply filters
 */
function filterMaterials() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const categoria = document.getElementById('filter-categoria').value;
    const bajoStock = document.getElementById('filter-bajo-stock').checked;

    let filtered = materialsData;

    if (search) {
        filtered = filtered.filter(m =>
            m.nombre.toLowerCase().includes(search) ||
            (m.codigo && m.codigo.toLowerCase().includes(search))
        );
    }

    if (categoria) {
        filtered = filtered.filter(m => m.categoria === categoria);
    }

    if (bajoStock) {
        filtered = filtered.filter(m => m.stock_actual <= m.stock_minimo);
    }

    renderTable(filtered);
}

/**
 * Open create modal
 */
function openCreateModal() {
    document.getElementById('modal-title').textContent = 'Nuevo Material';
    document.getElementById('material-form').reset();
    document.getElementById('material-id').value = '';
    document.getElementById('material-stock').disabled = false;
    Modal.open('material-modal');
}

/**
 * Edit material
 */
async function editMaterial(id) {
    try {
        const response = await MaterialesService.getById(id);
        const material = response.data;

        document.getElementById('modal-title').textContent = 'Editar Material';
        document.getElementById('material-id').value = material.id;
        document.getElementById('material-nombre').value = material.nombre;
        document.getElementById('material-codigo').value = material.codigo || '';
        document.getElementById('material-unidad').value = material.unidad_medida;
        document.getElementById('material-stock').value = material.stock_actual;
        document.getElementById('material-stock').disabled = true; // Stock changes via movements
        document.getElementById('material-minimo').value = material.stock_minimo;
        document.getElementById('material-categoria').value = material.categoria || '';

        Modal.open('material-modal');

    } catch (error) {
        console.error('Error loading material:', error);
        Toast.error('Error al cargar el material');
    }
}

/**
 * Save material (create or update)
 */
async function saveMaterial() {
    const id = document.getElementById('material-id').value;

    const data = {
        nombre: document.getElementById('material-nombre').value.trim(),
        codigo: document.getElementById('material-codigo').value.trim(),
        unidad_medida: document.getElementById('material-unidad').value,
        stock_actual: parseFloat(document.getElementById('material-stock').value) || 0,
        stock_minimo: parseFloat(document.getElementById('material-minimo').value) || 0,
        categoria: document.getElementById('material-categoria').value.trim()
    };

    // Validation
    if (!data.nombre) {
        Toast.warning('El nombre es obligatorio');
        return;
    }

    if (!data.unidad_medida) {
        Toast.warning('La unidad de medida es obligatoria');
        return;
    }

    try {
        if (id) {
            data.id = parseInt(id);
            await MaterialesService.update(data);
            Toast.success('Material actualizado correctamente');
        } else {
            await MaterialesService.create(data);
            Toast.success('Material creado correctamente');
        }

        Modal.close();
        await loadMaterials();
        filterMaterials(); // Reapply filters

    } catch (error) {
        console.error('Error saving material:', error);
        Toast.error(error.message || 'Error al guardar el material');
    }
}

/**
 * Delete material
 */
function deleteMaterial(id) {
    const material = materialsData.find(m => m.id === id);

    Modal.confirm(
        `¿Está seguro de eliminar el material "${material?.nombre}"?`,
        async () => {
            try {
                await MaterialesService.delete(id);
                Toast.success('Material eliminado correctamente');
                await loadMaterials();
                filterMaterials();
            } catch (error) {
                console.error('Error deleting material:', error);
                Toast.error(error.message || 'Error al eliminar el material');
            }
        }
    );
}
