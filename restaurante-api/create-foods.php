<?php require "../layouts/header.php"; ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Crear Nuevo Plato</h5>
            <form id="createFoodForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label>Nombre</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Precio ($)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Imagen</label>
                    <input type="file" name="image" class="form-control" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label>Descripción</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label>Tipo de Comida</label>
                    <select name="meal_id" class="form-select" required>
                        <option value="1">Breakfast</option>
                        <option value="2">Lunch</option>
                        <option value="3">Dinner</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Crear</button>
                <a href="show-foods.php" class="btn btn-secondary">Volver</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js"></script>

<script>

document.getElementById('createFoodForm').onsubmit = async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);

    try {
        const data = await apiCallFile('/admin/foods', formData);
        showAlert('Plato creado con éxito', 'success');
        setTimeout(() => window.location.href = 'show-foods.php', 1000);
    } catch (err) {
        showAlert(err.message, 'danger');
    }
};
</script>
</script>

<?php require "../layouts/footer.php"; ?>