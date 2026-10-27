<?php
require_once __DIR__ . '/includes/auth.php';

$pageTitle   = 'Find a dorm';
$pageStyles  = ['assets/css/listings.css'];
$pageScripts = ['assets/js/listings.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <h1 class="h2 mb-4">Find a dorm</h1>

  <form id="searchForm" class="search-bar mx-auto" role="search">
    <div class="input-group input-group-lg">
      <span class="input-group-text bg-body"><i class="bi bi-search" aria-hidden="true"></i></span>
      <input type="search" class="form-control" id="q" name="q" maxlength="100"
             placeholder="Search by school, area, or dorm name" aria-label="Search listings">
      <button class="btn btn-primary" type="submit">Search</button>
    </div>
  </form>

  <div class="d-flex flex-wrap justify-content-center gap-2 mt-3" id="typeChips" role="group" aria-label="Filter by type">
    <button type="button" class="chip filter-chip active" data-type="" aria-pressed="true">All</button>
    <button type="button" class="chip filter-chip" data-type="dormitory" aria-pressed="false">Dormitory</button>
    <button type="button" class="chip filter-chip" data-type="bedspace" aria-pressed="false">Bedspace</button>
    <button type="button" class="chip filter-chip" data-type="boarding_house" aria-pressed="false">Boarding house</button>
  </div>

  <form id="filters" class="row g-2 align-items-end mt-3 filter-row">
    <div class="col-6 col-md-2">
      <label for="minPrice" class="form-label small mb-1">Min price</label>
      <input type="number" class="form-control" id="minPrice" min="0" step="100" placeholder="0">
    </div>
    <div class="col-6 col-md-2">
      <label for="maxPrice" class="form-label small mb-1">Max price</label>
      <input type="number" class="form-control" id="maxPrice" min="0" step="100" placeholder="Any">
    </div>
    <div class="col-6 col-md-3">
      <label for="gender" class="form-label small mb-1">Who can stay</label>
      <select class="form-select" id="gender">
        <option value="">Any</option>
        <option value="male_only">Male</option>
        <option value="female_only">Female</option>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label for="sort" class="form-label small mb-1">Sort by</label>
      <select class="form-select" id="sort">
        <option value="newest">Newest</option>
        <option value="price_asc">Price: low to high</option>
        <option value="price_desc">Price: high to low</option>
        <option value="nearest">Nearest to me</option>
      </select>
    </div>
    <div class="col-12 col-md-2 d-flex flex-column gap-1">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="available">
        <label class="form-check-label small" for="available">Has free slots</label>
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="nearMe">
        <i class="bi bi-crosshair" aria-hidden="true"></i> Dorms near me
      </button>
    </div>
  </form>
  <div class="small text-danger mt-1" id="nearMsg" role="alert"></div>

  <p class="text-body-secondary mt-4 mb-3" id="resultCount" aria-live="polite"></p>
  <div class="row g-4" id="results"></div>

  <div class="text-center mt-4">
    <button type="button" class="btn btn-outline-secondary d-none" id="loadMore">Load more</button>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
