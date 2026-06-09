        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function (event) {
    if (window.innerWidth > 991) return;
    const sidebar = document.querySelector('.admin-sidebar');
    const toggle = document.querySelector('.admin-mobile-toggle');
    if (!sidebar || !document.body.classList.contains('admin-sidebar-open')) return;
    if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
        document.body.classList.remove('admin-sidebar-open');
    }
});
</script>
</body>
</html>
