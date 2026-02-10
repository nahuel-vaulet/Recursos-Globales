/**
 * Usuarios (Users) Management JavaScript
 */

let usersData = [];

document.addEventListener('DOMContentLoaded', async () => {
    await loadUsers();
});

async function loadUsers() {
    try {
        Loading.show('#users-table');

        const response = await UsuariosService.getAll();
        usersData = response.data;

        renderTable();

    } catch (error) {
        console.error('Error loading users:', error);
        Toast.error('Error al cargar usuarios');
    }
}

function renderTable() {
    const rolLabels = {
        admin: { text: 'Administrador', class: 'danger' },
        supervisor: { text: 'Supervisor', class: 'warning' },
        operador: { text: 'Operador', class: 'ok' }
    };

    DataTable.init('users-table', {
        data: usersData,
        columns: [
            { key: 'nombre', label: 'Nombre' },
            { key: 'email', label: 'Email' },
            {
                key: 'rol',
                label: 'Rol',
                render: (val) => {
                    const role = rolLabels[val] || { text: val, class: 'ok' };
                    return `<span class="stock-indicator ${role.class}">${role.text}</span>`;
                }
            },
            {
                key: 'total_movimientos',
                label: 'Movimientos',
                render: (val) => formatNumber(val || 0)
            },
            {
                key: 'created_at',
                label: 'Creado',
                render: (val) => formatDate(val)
            }
        ],
        pageSize: 10,
        onEdit: 'editUser',
        onDelete: 'deleteUser'
    });
}

function openCreateModal() {
    document.getElementById('modal-title').textContent = 'Nuevo Usuario';
    document.getElementById('user-form').reset();
    document.getElementById('user-id').value = '';
    document.getElementById('user-password').required = true;
    document.getElementById('password-label').textContent = 'Contraseña *';
    document.getElementById('password-hint').textContent = 'Mínimo 6 caracteres';
    Modal.open('user-modal');
}

async function editUser(id) {
    try {
        const response = await UsuariosService.getById(id);
        const user = response.data;

        document.getElementById('modal-title').textContent = 'Editar Usuario';
        document.getElementById('user-id').value = user.id;
        document.getElementById('user-nombre').value = user.nombre;
        document.getElementById('user-email').value = user.email;
        document.getElementById('user-password').value = '';
        document.getElementById('user-password').required = false;
        document.getElementById('password-label').textContent = 'Contraseña';
        document.getElementById('password-hint').textContent = 'Dejar vacío para mantener la actual';
        document.getElementById('user-rol').value = user.rol;

        Modal.open('user-modal');
    } catch (error) {
        Toast.error('Error al cargar el usuario');
    }
}

async function saveUser() {
    const id = document.getElementById('user-id').value;

    const data = {
        nombre: document.getElementById('user-nombre').value.trim(),
        email: document.getElementById('user-email').value.trim(),
        password: document.getElementById('user-password').value,
        rol: document.getElementById('user-rol').value
    };

    // Validation
    if (!data.nombre) {
        Toast.warning('El nombre es obligatorio');
        return;
    }

    if (!data.email || !data.email.includes('@')) {
        Toast.warning('Ingrese un email válido');
        return;
    }

    if (!id && !data.password) {
        Toast.warning('La contraseña es obligatoria');
        return;
    }

    if (data.password && data.password.length < 6) {
        Toast.warning('La contraseña debe tener al menos 6 caracteres');
        return;
    }

    if (!data.rol) {
        Toast.warning('Seleccione un rol');
        return;
    }

    // Remove password if empty (for updates)
    if (!data.password) {
        delete data.password;
    }

    try {
        if (id) {
            data.id = parseInt(id);
            await UsuariosService.update(data);
            Toast.success('Usuario actualizado correctamente');
        } else {
            await UsuariosService.create(data);
            Toast.success('Usuario creado correctamente');
        }

        Modal.close();
        await loadUsers();
    } catch (error) {
        Toast.error(error.message || 'Error al guardar el usuario');
    }
}

function deleteUser(id) {
    const user = usersData.find(u => u.id === id);

    Modal.confirm(
        `¿Está seguro de eliminar el usuario "${user?.nombre}"?`,
        async () => {
            try {
                await UsuariosService.delete(id);
                Toast.success('Usuario eliminado correctamente');
                await loadUsers();
            } catch (error) {
                Toast.error(error.message || 'Error al eliminar el usuario');
            }
        }
    );
}
