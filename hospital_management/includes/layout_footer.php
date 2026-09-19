    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $base ?? '../'; ?>assets/js/realtime.js"></script>
<script src="<?php echo $base ?? '../'; ?>assets/js/notifications.js"></script>
<script src="<?php echo $base ?? '../'; ?>assets/js/dashboard.js"></script>
<script>
  window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
  Realtime.init('<?php echo $base ?? '../'; ?>');
  Notifications.init();
  Dashboard.init('<?php echo e($user_role ?? ''); ?>', <?php echo (int)($user_id ?? 0); ?>);

  // Toggle notification dropdown
  Notifications.toggleDropdown = function() {
    const dd = document.getElementById('notifDropdown');
    dd.classList.toggle('show');
  };

  // Close dropdown on outside click
  document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notif-wrapper');
    const dd = document.getElementById('notifDropdown');
    if (wrapper && !wrapper.contains(e.target) && dd) {
      dd.classList.remove('show');
    }
  });
</script>
</body>
</html>
