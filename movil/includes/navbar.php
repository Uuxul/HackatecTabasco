<style>
    .bottom-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        gap: clamp(4px, 3vw, 20px);
        padding: 10px 12px;
        padding-bottom: max(10px, env(safe-area-inset-bottom));
        box-sizing: border-box;
    }

    .bottom-nav .nav-item {
        flex: 0 1 100px;
        min-width: 0;
        min-height: 48px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin: 0;
        padding: 8px 4px;
        text-align: center;
        text-decoration: none;
        box-sizing: border-box;
    }

    .bottom-nav .nav-item i {
        font-size: 20px;
    }

    .bottom-nav .nav-item span {
        font-size: 12px;
    }
</style>

<nav class="bottom-nav" aria-label="Navegación principal">

    <a href="index.php" class="nav-item">
        <i class="fa-solid fa-house" aria-hidden="true"></i>
        <span>Inicio</span>
    </a>

    <a href="reporte.php" class="nav-item">
        <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
        <span>Reportes</span>
    </a>

    <a href="perfil.php" class="nav-item">
        <i class="fa-solid fa-user" aria-hidden="true"></i>
        <span>Perfil</span>
    </a>

</nav>