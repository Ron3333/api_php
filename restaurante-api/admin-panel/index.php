<?php require "layouts/header.php"; ?>

<script>
    if (!localStorage.getItem('adminToken')) {
        window.location.href = 'admins/login-admins.php';
    }
</script>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard Administrativo</h2>
        <a href="admins/logout.php" class="btn btn-danger">Cerrar Sesión</a>
    </div>

    <div class="row" id="stats">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5>Comidas</h5>
                    <h3 id="count_foods">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5>Pedidos</h5>
                    <h3 id="count_orders">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5>Reservas</h5>
                    <h3 id="count_bookings">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5>Admins</h5>
                    <h3 id="count_admins">0</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="admins/admins.php" class="btn btn-outline-primary">Gestionar Admins</a>
    </div>
</div>

<script src="js/admin.js"></script>
<script>
async function loadDashboard() {
    try {
        const data = await apiCall('/admin/dashboard');
        document.getElementById('count_foods').textContent = data.data.foods;
        document.getElementById('count_orders').textContent = data.data.orders;
        document.getElementById('count_bookings').textContent = data.data.bookings;
        document.getElementById('count_admins').textContent = data.data.admins;
    } catch (err) {
        showAlert(err.message, 'danger');
    }
}

loadDashboard();
</script>


<?php require "layouts/footer.php"; ?>