  </main>

  <footer class="border-top bg-body-tertiary mt-5 py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span class="text-body-secondary">&copy; <?= date('Y') ?> DormFinder. Find a place to stay near school.</span>
      <nav aria-label="Footer navigation">
        <a class="link-secondary me-3" href="<?= url('index.php') ?>">Home</a>
        <a class="link-secondary me-3" href="<?= url('listings.php') ?>">Find a dorm</a>
        <a class="link-secondary" href="<?= url('login.php') ?>">Log in</a>
      </nav>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
