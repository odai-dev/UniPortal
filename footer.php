</main>

<?php if (isLoggedIn()): ?>
<!-- Footer for logged in users -->
<footer class="bg-light mt-5 py-3">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">Logged in as: <?= sanitizeInput($_SESSION['name'] ?? 'User') ?></small>
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!-- Custom JavaScript -->
<script>
// Initialize DataTables on all tables with class 'datatable'
$(document).ready(function() {
    $('.datatable').DataTable({
        "pageLength": 10,
        "responsive": true,
        "order": [[ 0, "asc" ]],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
});

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 5000);
</script>

</div> <!-- Close page-transition -->
</body>
</html>