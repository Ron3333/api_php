<?php require "../layouts/header.php"; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Login Administrador</h4>
                </div>
                <div class="card-body">
                    <form id="loginForm">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Contraseña</label>
                            <input type="password" id="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/admin.js"></script>
<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    try {
        const response = await fetch('<?= ADMINAPI ?>/api-admin/admin/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        console.log(data);

        if (data.status === 'success') {
            setToken(data.data.token);
            showAlert('Login exitoso', 'success');
            setTimeout(() => window.location.href = '../index.php', 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (err) {
        showAlert(err.message, 'danger');
    }
});
</script>

<?php require "../layouts/footer.php"; ?>