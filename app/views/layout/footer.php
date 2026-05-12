  </div><!-- .content -->

  <footer style="
    border-top: 1px solid var(--bd);
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    font-size: .72rem;
    color: var(--mt);
  ">
    <span>
      <?= h(DB::setting('app_name', 'FlexCore')) ?>
      &nbsp;·&nbsp;
      <?= __('layout.footer.version') ?> <strong style="color:var(--mt2)"><?= defined('APP_VERSION') ? h(APP_VERSION) : '—' ?></strong>
    </span>
    <span>
      <?= __('layout.footer.powered_by') ?> ❤️ - <a href="https://sancalproducoes.com.br">SanCal Produções</a>
      &nbsp;·&nbsp;
      <?= date('Y') ?> <?= __('layout.footer.rights') ?>
    </span>
  </footer>

</div><!-- .main -->
</body>
</html>
