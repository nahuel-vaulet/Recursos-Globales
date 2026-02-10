/**
 * Movimientos (Stock Movements) Management JavaScript
 */

let movementsData = [];
let materialesOptions = [];
let cuadrillasOptions = [];

document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([
        loadMateriales(),
        loadCuadrillas()
    ]);
    await loadMovements();
    setupFilters();
});

async function loadMateriales() {
    try {
        const response = await MaterialesService.getAll();
        materialesOptions = response.data;

        // Populate filter
        const filterSelect = document.getElementById('filter-material');
        filterSelect.innerHTML = '<option value="">Todos los materiales</option>' +
            materialesOptions.map(m => `<option value="${m.id}">${m.nombre}</option>`).join('');

        // Populate form select
        const formSelect = document.getElementById('movement-material');
        formSelect.innerHTML = '<option value="">Seleccionar material...</option>' +
            materialesOptions.map(m =>
                `<option value="${m.id}" data-stock="${m.stock_actual}" data-unidad="${m.unidad_medida}">
                    ${m.nombre} (Stock: ${formatNumber(m.stock_actual)} ${m.unidad_medida})
                </option>`
            ).join('');

    } catch (error) {
        console.error('Error loading materials:', error);
    }
}

async function loadCuadrillas() {
    try {
        const response = await CuadrillasService.getAll();
        cuadrillasOptions = response.data;

        // Populate filter
        const filterSelect = document.getElementById('filter-cuadrilla');
        filterSelect.innerHTML = '<option value="">Todas las cuadrillas</option>' +
            cuadrillasOptions.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');

        // Populate form select
        const formSelect = document.getElementById('movement-cuadrilla');
        formSelect.innerHTML = '<option value="">Seleccionar cuadrilla...</option>' +
            cuadrillasOptions.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');

    } catch (error) {
        console.error('Error loading cuadrillas:', error);
    }
}

async function loadMovements() {
    try {
        Loading.show('#movements-table');

        const params = {};
        const tipo = document.getElementById('filter-tipo').value;
        const materialId = document.getElementById('filter-material').value;
        const cuadrillaId = document.getElementById('filter-cuadrilla').value;
        const desde = document.getElementById('filter-desde').value;
        const hasta = document.getElementById('filter-hasta').value;

        if (tipo) params.tipo = tipo;
        if (materialId) params.material_id = materialId;
        if (cuadrillaId) params.cuadrilla_id = cuadrillaId;
        if (desde) params.fecha_desde = desde;
        if (hasta) params.fecha_hasta = hasta;

        const response = await MovimientosService.getAll(params);
        movementsData = response.data;

        renderTable();

    } catch (error) {
        console.error('Error loading movements:', error);
        Toast.error('Error al cargar movimientos');
    }
}

function renderTable() {
    DataTable.init('movements-table', {
        data: movementsData,
        columns: [
            {
                key: 'fecha',
                label: 'Fecha',
                render: (val) => formatDate(val)
            },
            {
                key: 'tipo',
                label: 'Tipo',
                render: (val) => `
                    <span class="stock-indicator ${val === 'entrada' ? 'ok' : 'warning'}">
                        <i class="fas fa-arrow-${val === 'entrada' ? 'down' : 'up'}"></i>
                        ${val.charAt(0).toUpperCase() + val.slice(1)}
                    </span>
                `
            },
            { key: 'material_nombre', label: 'Material' },
            {
                key: 'cantidad',
                label: 'Cantidad',
                render: (val, row) => `${formatNumber(val, 2)} ${row.unidad_medida}`
            },
            {
                key: 'cuadrilla_nombre',
                label: 'Cuadrilla',
                render: (val) => val || '<span class="text-muted">-</span>'
            },
            { key: 'usuario_nombre', label: 'Usuario' }
        ],
        pageSize: 15,
        searchable: false
    });
}

function setupFilters() {
    document.getElementById('filter-tipo').addEventListener('change', loadMovements);
    document.getElementById('filter-material').addEventListener('change', loadMovements);
    document.getElementById('filter-cuadrilla').addEventListener('change', loadMovements);
    document.getElementById('filter-desde').addEventListener('change', loadMovements);
    document.getElementById('filter-hasta').addEventListener('change', loadMovements);

    // Material selection handler for stock info
    document.getElementById('movement-material').addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const stockInfo = document.getElementById('material-stock-info');

        if (selected.value) {
            const stock = selected.dataset.stock;
            const unidad = selected.dataset.unidad;
            stockInfo.textContent = `Stock disponible: ${formatNumber(parseFloat(stock))} ${unidad}`;
        } else {
            stockInfo.textContent = '';
        }
    });
}

function clearFilters() {
    document.getElementById('filter-tipo').value = '';
    document.getElementById('filter-material').value = '';
    document.getElementById('filter-cuadrilla').value = '';
    document.getElementById('filter-desde').value = '';
    document.getElementById('filter-hasta').value = '';
    loadMovements();
}

function openMovementModal(tipo) {
    document.getElementById('modal-title').textContent = tipo === 'entrada' ? 'Nueva Entrada' : 'Nueva Salida';
    document.getElementById('movement-form').reset();
    document.getElementById('movement-tipo').value = tipo;
    document.getElementById('material-stock-info').textContent = '';

    // Update badge
    const badge = document.getElementById('tipo-badge');
    badge.className = `stock-indicator ${tipo === 'entrada' ? 'ok' : 'warning'}`;
    badge.innerHTML = `<i class="fas fa-arrow-${tipo === 'entrada' ? 'down' : 'up'}"></i> ${tipo === 'entrada' ? 'Entrada' : 'Salida'}`;

    // Show/hide cuadrilla field (required for exits - RF-03)
    const cuadrillaGroup = document.getElementById('cuadrilla-group');
    const cuadrillaSelect = document.getElementById('movement-cuadrilla');

    if (tipo === 'salida') {
        cuadrillaGroup.style.display = 'block';
        cuadrillaSelect.required = true;
        cuadrillaGroup.querySelector('.form-label').innerHTML = 'Cuadrilla * <small class="text-muted">(obligatorio para salidas)</small>';
    } else {
        cuadrillaGroup.style.display = 'block';
        cuadrillaSelect.required = false;
        cuadrillaGroup.querySelector('.form-label').textContent = 'Cuadrilla';
    }

    Modal.open('movement-modal');
}

async function saveMovement() {
    const tipo = document.getElementById('movement-tipo').value;
    const materialId = document.getElementById('movement-material').value;
    const cuadrillaId = document.getElementById('movement-cuadrilla').value;
    const cantidad = parseFloat(document.getElementById('movement-cantidad').value);
    const observaciones = document.getElementById('movement-observaciones').value.trim();

    // Validation
    if (!materialId) {
        Toast.warning('Seleccione un material');
        return;
    }

    if (!cantidad || cantidad <= 0) {
        Toast.warning('Ingrese una cantidad válida');
        return;
    }

    if (tipo === 'salida' && !cuadrillaId) {
        Toast.warning('La cuadrilla es obligatoria para las salidas');
        return;
    }

    const data = {
        material_id: parseInt(materialId),
        tipo: tipo,
        cantidad: cantidad,
        observaciones: observaciones
    };

    if (cuadrillaId) {
        data.cuadrilla_id = parseInt(cuadrillaId);
    }

    try {
        const response = await MovimientosService.create(data);

        Toast.success('Movimiento registrado correctamente');

        // Show alert if stock is low
        if (response.alert) {
            setTimeout(() => Toast.warning(response.alert), 500);
        }

        Modal.close();

        // Reload data
        await Promise.all([
            loadMateriales(),
            loadMovements()
        ]);

    } catch (error) {
        console.error('Error saving movement:', error);
        Toast.error(error.message || 'Error al registrar el movimiento');
    }
}
