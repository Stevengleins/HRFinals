</div> 
<footer class="main-footer">
    <strong>WorkForcePro</strong>
    <div class="float-right d-none d-sm-inline-block">
      <b>Web Development 2 Final Project</b>
    </div>
  </footer>
</div> 

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // 1. The Session Verification Function
    function enforceSecurity() {
        fetch('../check_session.php', { cache: "no-store", method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'logged_out') {
                    window.location.replace('../index.php');
                }
            })
            .catch(error => console.error('Security check failed'));
    }

    // 2. Catch Back/Forward Cache (BFCache)
    window.addEventListener("pageshow", function(event) {
        if (event.persisted) {
            window.location.reload(); // Force a hard refresh from the server
        } else {
            enforceSecurity(); // Check session anyway
        }
    });

    // 3. Catch Tab Switching & Forward Navigation
    // This fires the instant the tab becomes visible on the screen again
    document.addEventListener("visibilitychange", function() {
        if (document.visibilityState === 'visible') {
            enforceSecurity();
        }
    });
</script>
</body>
</html>