<!DOCTYPE html>
<html lang="es">
<?php require "../layouts/header.php"; ?>
<div class="container mt-4" id="editFoodContainer">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Editar Plato</h5>
            <form id="editFoodForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="foodId">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Nombre</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Precio ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Descripción</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Tipo</label>
                            <select name="meal_id" class="form-select" required>
                                <option value="1">Breakfast</option>
                                <option value="2">Lunch</option>
                                <option value="3">Dinner</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Imagen Actual</label><br>
                            <img id="currentImage" src="" width="150" class="img-thumbnail">
                        </div>
                        <div class="mb-3">
                            <label>Nueva Imagen (opcional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="show-foods.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<script src="../js/admin.js"></script>
<script>

const urlParams = new URLSearchParams(window.location.search);
const foodId = urlParams.get('id');
let currentImageName = '';

if (!foodId) {
    showAlert('ID requerido', 'danger');
    setTimeout(() => window.location.href = 'show-foods.php', 1500);
}

async function loadFood() {
    try {
        const res = await apiCall(`/admin/foods/${foodId}`);
        const food = res.data;

        document.getElementById('foodId').value = food.id;
        document.querySelector('[name="name"]').value = food.name;
        document.querySelector('[name="price"]').value = food.price;
        document.querySelector('[name="description"]').value = food.description;
        document.querySelector('[name="meal_id"]').value = food.meal_id;
        document.getElementById('currentImage').src = `/api-admin/foods-images/${food.image}`;
        currentImageName = food.image;
    } catch (err) {
        showAlert(err.message || 'Error al cargar', 'danger');
        console.error(err);
    }
}

async function uploadImage(file) {
    const formData = new FormData();
    formData.append('image', file);

    const res = await apiCallFile('/admin/upload', formData);
    return res.data.image; // ← nombre del archivo
}

// === SUBIR IMAGEN AL CAMBIAR ===
document.querySelector('[name="image"]').addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    try {
        const imageName = await uploadImage(file);
        currentImageName = imageName;
        document.getElementById('currentImage').src = `/api-admin/foods-images/${imageName}`;
        showAlert('Imagen subida', 'success');
    } catch (err) {
        showAlert(err.message, 'danger');
    }
});

// === ENVIAR FORMULARIO CON PUT + JSON ===
document.getElementById('editFoodForm').onsubmit = async (e) => {
    e.preventDefault();

    const data = {
        name: document.querySelector('[name="name"]').value,
        price: document.querySelector('[name="price"]').value,
        description: document.querySelector('[name="description"]').value,
        meal_id: document.querySelector('[name="meal_id"]').value,
        image: currentImageName // ← usar imagen actual o nueva
    };

    try {
        await apiCall(`/admin/foods/${foodId}`, {
            method: 'PUT',
            body: data // ← JSON automático
        });
        showAlert('Actualizado con éxito', 'success');
        setTimeout(() => window.location.href = 'show-foods.php', 200);
    } catch (err) {
        showAlert(err.message, 'danger');
    }
};

loadFood();
</script>

<?php require "../layouts/footer.php"; ?>