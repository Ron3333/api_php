<?php require "../layouts/header.php"; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Administradores</h2>
        <div>
            <!-- Botón -->
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
                + Crear Admin
            </button>


            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="adminsTable">
                    <!-- JS llenará aquí -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createForm">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-2" id="adminname" placeholder="Nombre" required>
                    <input type="email" class="form-control mb-2" id="email" placeholder="Email" required>
                    <input type="password" class="form-control mb-2" id="password" placeholder="Contraseña" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Crear</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('createModal');
    const modal = new bootstrap.Modal(modalElement);

    // Limpiar al cerrar
    modalElement.addEventListener('hidden.bs.modal', () => {
        document.getElementById('createForm').reset();
    });

    async function loadAdmins() {
        try {
            const data = await apiCall('/admin/admins');
            const tbody = document.getElementById('adminsTable');
            tbody.innerHTML = '';
            data.data.forEach(admin => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${admin.id}</td>
                    <td>${admin.adminname}</td>
                    <td>${admin.email}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteAdmin(${admin.id})">
                            Eliminar
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            showAlert(err.message || 'Error al cargar admins', 'danger');
        }
    }

    window.deleteAdmin = async function(id) {
        if (!confirm('¿Eliminar este admin?')) return;
        try {
            await fetch(`${API_BASE}/admin/admins/${id}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${getToken()}` }
            });
            showAlert('Admin eliminado', 'success');
            loadAdmins();
        } catch (err) {
            showAlert('Error al eliminar', 'danger');
        }
    };


    document.getElementById('createForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = {
            adminname: document.getElementById('adminname').value.trim(),
            email: document.getElementById('email').value.trim(),
            password: document.getElementById('password').value
        };

        if (!formData.adminname || !formData.email || !formData.password) {
            showAlert('Todos los campos son requeridos', 'warning');
            return;
        }

        let success = false;

        try {
            await apiCall('/admin/admins', {
                method: 'POST',
                body: JSON.stringify(formData)
            });
            success = true;
        } catch (err) {
            showAlert(err.message || 'Error al crear admin', 'danger');
        }

        if (success) {
            showAlert('Admin creado exitosamente', 'success');
            // CERRAR CON RETRASO
            setTimeout(() => {
                modal.hide();
                loadAdmins();
                window.location.href = `${ADMIN_BASE}/admins/admins.php`;
            }, 10);
        }
    });

   

    loadAdmins();
});
</script>


<?php require "../layouts/footer.php"; ?>