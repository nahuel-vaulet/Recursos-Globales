/**
 * Cuadrillas (Work Squads) Management JavaScript
 */

let cuadrillasData = [];

document.addEventListener('DOMContentLoaded', async () => {
    await loadCuadrillas();
});

async function loadCuadrillas() {
    try {
        const response = await CuadrillasService.getAll();
        cuadrillasData = response.data;
        renderCuadrillas();
    } catch (error) {
        console.error('Error loading cuadrillas:', error);
        Toast.error('Error al cargar cuadrillas');
    }
}

function renderCuadrillas() {
    const container = document.getElementById('cuadrillas-grid');

    if (cuadrillasData.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="grid-column: span 3;">
                <i class="fas fa-users"></i>
                <div class="empty-state-title">No hay cuadrillas registradas</div>
                <p class="text-muted">Crea una nueva cuadrilla para empezar</p>
            </div>
        `;
        return;
    }

    container.innerHTML = cuadrillasData.map(c => `
        <div class="card">
            <div class="card-header">
                <span class="card-title">${c.nombre}</span>
                <div class="table-actions">
                    <button class="btn btn-icon btn-outline" onclick="editCuadrilla(${c.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-icon btn-outline text-danger" onclick="deleteCuadrilla(${c.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-sm">
                    <i class="fas fa-map-marker-alt text-muted"></i>
                    <span class="text-muted">${c.zona_trabajo || 'Sin zona asignada'}</span>
                </p>
                <p class="mb-md">
                    <i class="fas fa-user text-muted"></i>
                    <span class="text-muted">${c.responsable || 'Sin responsable'}</span>
                </p>
                <div class="d-flex justify-between align-center">
                    <span class="text-muted">Consumo (30 días)</span>
                    <span class="metric-value" style="font-size: var(--font-size-xl);">
                        ${formatNumber(c.consumo_mensual || 0)}
                    </span>
                </div>
                <div class="progress" style="margin-top: var(--spacing-sm);">
                    <div class="progress-bar" style="width: ${Math.min(100, (c.consumo_mensual || 0) / 10)}%"></div>
                </div>
            </div>
        </div>
    `).join('');
}

function openCreateModal() {
    document.getElementById('modal-title').textContent = 'Nueva Cuadrilla';
    document.getElementById('cuadrilla-form').reset();
    document.getElementById('cuadrilla-id').value = '';
    Modal.open('cuadrilla-modal');
}

async function editCuadrilla(id) {
    try {
        const response = await CuadrillasService.getById(id);
        const cuadrilla = response.data;

        document.getElementById('modal-title').textContent = 'Editar Cuadrilla';
        document.getElementById('cuadrilla-id').value = cuadrilla.id;
        document.getElementById('cuadrilla-nombre').value = cuadrilla.nombre;
        document.getElementById('cuadrilla-zona').value = cuadrilla.zona_trabajo || '';
        document.getElementById('cuadrilla-responsable').value = cuadrilla.responsable || '';

        Modal.open('cuadrilla-modal');
    } catch (error) {
        Toast.error('Error al cargar la cuadrilla');
    }
}

async function saveCuadrilla() {
    const id = document.getElementById('cuadrilla-id').value;

    const data = {
        nombre: document.getElementById('cuadrilla-nombre').value.trim(),
        zona_trabajo: document.getElementById('cuadrilla-zona').value.trim(),
        responsable: document.getElementById('cuadrilla-responsable').value.trim()
    };

    if (!data.nombre) {
        Toast.warning('El nombre es obligatorio');
        return;
    }

    try {
        if (id) {
            data.id = parseInt(id);
            await CuadrillasService.update(data);
            Toast.success('Cuadrilla actualizada correctamente');
        } else {
            await CuadrillasService.create(data);
            Toast.success('Cuadrilla creada correctamente');
        }

        Modal.close();
        await loadCuadrillas();
    } catch (error) {
        Toast.error(error.message || 'Error al guardar la cuadrilla');
    }
}

function deleteCuadrilla(id) {
    const cuadrilla = cuadrillasData.find(c => c.id === id);

    Modal.confirm(
        `¿Está seguro de eliminar la cuadrilla "${cuadrilla?.nombre}"?`,
        async () => {
            try {
                await CuadrillasService.delete(id);
                Toast.success('Cuadrilla eliminada correctamente');
                await loadCuadrillas();
            } catch (error) {
                Toast.error(error.message || 'Error al eliminar la cuadrilla');
            }
        }
    );
}
