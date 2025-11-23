<?php require "../layouts/header.php"; ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Lista de Platos</h5>
                <a href="create-foods.php" class="btn btn-primary">+ Crear Plato</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="foodsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Tipo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js"></script>

<script>

async function loadFoods() {
    try {
        const res = await apiCall('/admin/foods');
        const tbody = document.querySelector('#foodsTable tbody');
        tbody.innerHTML = '';

        res.data.forEach(food => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${food.id}</td>
                <td><img src="/api-admin/foods-images/${food.image}" width="50" height="50" class="rounded"></td>
                <td>${food.name}</td>
                <td>$${parseFloat(food.price).toFixed(2)}</td>
                <td>${food.meal_name}</td>
                <td>
                    <a href="edit-foods.php?id=${food.id}" class="btn btn-sm btn-warning">Editar</a>
                    <button onclick="deleteFood(${food.id}, '${food.image}')" class="btn btn-sm btn-danger">Eliminar</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (err) {
        showAlert(err.message, 'danger');
    }
}

async function deleteFood(id, image) {
    if (!confirm('¿Eliminar este plato?')) return;
    try {
        await apiCall(`/admin/foods/${id}`, { method: 'DELETE' });
        showAlert('Plato eliminado', 'success');
        loadFoods();
    } catch (err) {
        showAlert(err.message, 'danger');
    }
}

loadFoods();
</script>

<?php require "../layouts/footer.php"; ?>