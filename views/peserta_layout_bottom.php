</body>

</html>
<script>
(function(){
  const btnSidebar = document.getElementById('btnSidebar');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  function openSidebar() {
    if (!sidebar || !overlay) return;
    sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeSidebar() {
    if (!sidebar || !overlay) return;
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  if (btnSidebar) {
    btnSidebar.addEventListener('click', function(e) {
      e.stopPropagation();
      if (sidebar.classList.contains('-translate-x-full')) {
        openSidebar();
      } else {
        closeSidebar();
      }
    });
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) {
      document.body.classList.remove('overflow-hidden');
      if (overlay) overlay.classList.add('hidden');
    } else {
      if (sidebar) sidebar.classList.add('-translate-x-full');
    }
  });

  const user = {
    id_pengguna: <?= (int)($_SESSION['user']['id_pengguna'] ?? 0) ?>,
    id_peserta: <?= (int)($_SESSION['user']['id_peserta'] ?? 0) ?>,
    nama: <?= json_encode($_SESSION['user']['nama_peserta'] ?? $_SESSION['user']['nama_lengkap'] ?? '') ?>,
    jenis_kelamin: <?= json_encode($_SESSION['user']['jenis_kelamin'] ?? '') ?>,
    pendidikan: <?= json_encode($_SESSION['user']['pendidikan'] ?? '') ?>,
  };

  if (user.id_pengguna) {
    try { localStorage.setItem('rmib_user', JSON.stringify(user)); } catch(e) {}
  }

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  }
})();
</script>

</body>
</html>