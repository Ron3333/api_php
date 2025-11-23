<?php
// logout.php
?>
<script>
    localStorage.removeItem('adminToken');
    alert('Has cerrado sesión');
    window.location.href = 'login-admins.php';
</script>