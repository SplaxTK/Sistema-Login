document.addEventListener('DOMContentLoaded', () => {
    const userMenu = document.getElementById('userMenuToggle');
    const dropdown = document.getElementById('dropdownMenu');
    const avatar = userMenu ? userMenu.querySelector('.avatar') : null;

    if (userMenu && dropdown && avatar) {
        avatar.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = dropdown.classList.toggle('active');
            avatar.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', () => {
            dropdown.classList.remove('active');
            avatar.setAttribute('aria-expanded', 'false');
        });
    }
});

function abrirModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
    }
}

function fecharModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
    }
}

function confirmarLogout() {
    abrirModal('modalLogout');
}

function confirmarDeletar(id) {
    const campoId = document.getElementById('deleteUserId');
    if (campoId) {
        campoId.value = id;
        abrirModal('modalDelete');
    }
}
