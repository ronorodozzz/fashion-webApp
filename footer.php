<?php
/**
 * TREND & FASHION BOUTIQUE SHOP - COMMON FOOTER PARTIAL
 */
?>
  </main>

  <!-- Site Footer -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="footer-brand-title">Trend <span>&</span> Fashion</div>
          <p class="footer-desc">
            A minimalist fashion boutique celebrating architectural restraint, sharp geometric silhouettes, and enduring craftsmanship. Built with high-performance standalone PHP architecture.
          </p>
        </div>

        <div>
          <div class="footer-col-title">Curated Collections</div>
          <ul class="footer-links">
            <li><a href="shopping.php?category=apparel">Apparel & Outerwear</a></li>
            <li><a href="shopping.php?gender=Men">Men's Wardrobe</a></li>
            <li><a href="shopping.php?gender=Women">Women's Wardrobe</a></li>
            <li><a href="shopping.php?category=bags">Artisan Leather Bags</a></li>
            <li><a href="shopping.php?category=shoes">Handcrafted Footwear</a></li>
          </ul>
        </div>

        <div>
          <div class="footer-col-title">Customer Atelier</div>
          <ul class="footer-links">
            <li><a href="cart.php">Shopping Bag</a></li>
            <li><a href="checkout.php">Secure Checkout</a></li>
            <li><a href="index.php">Member Portal</a></li>
            <li><a href="database_setup.txt" download>Download Schema (.txt)</a></li>
            <li><a href="database_setup.sql" download>Download Schema (.sql)</a></li>
          </ul>
        </div>

        <div>
          <div class="footer-col-title">Atelier Philosophy</div>
          <p class="footer-desc" style="font-size: 0.8rem;">
            All containers, buttons, and design cards feature sharp 90-degree edges, strictly free of rounded borders, reflecting pure modernism.
          </p>
          <div style="margin-top: 1rem;">
            <span class="badge badge-pink" style="margin-right: 0.5rem;">Soft Sand #F0E5DB</span>
            <span class="badge badge-dark">Mocha Green #474329</span>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div>&copy; <?php echo date('Y'); ?> Trend & Fashion Boutique Shop. All Rights Reserved.</div>
        <div>Engineered with Standalone Multi-Page PHP, PDO MySQL, and Vanilla JS.</div>
      </div>
    </div>
  </footer>

  <script src="script.js"></script>
</body>
</html>
