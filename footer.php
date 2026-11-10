    </div>
    <!-- End Content -->

</div>
<!-- End d-flex -->

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap5.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom JS -->
<script src="assets/js/script.js"></script>

<script>
$(document).ready(function () {

    // Initialize DataTables
    $('.datatable').DataTable({
        responsive: true,
        pageLength: 10,
        ordering: true
    });

});
</script>

</body>
</html>