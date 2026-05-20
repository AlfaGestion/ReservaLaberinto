<?php
$notice = $customerNotice ?? null;
?>

<?php if (!empty($notice)) : ?>
    <?php
    $noticeType = in_array(($notice['type'] ?? ''), ['info', 'warning', 'important', 'success'], true) ? $notice['type'] : 'info';
    $noticeIcons = [
        'info' => 'fa-circle-info',
        'warning' => 'fa-triangle-exclamation',
        'important' => 'fa-bullhorn',
        'success' => 'fa-circle-check',
    ];
    $noticeTitles = [
        'info' => 'Informacion',
        'warning' => 'Advertencia',
        'important' => 'Importante',
        'success' => 'Listo',
    ];
    ?>
    <section
        class="customer-notice customer-notice--<?= esc($noticeType) ?>"
        data-customer-notice-id="<?= esc($notice['id']) ?>"
        role="status"
        aria-live="polite">
        <div class="customer-notice__icon" aria-hidden="true">
            <i class="fa-solid <?= esc($noticeIcons[$noticeType]) ?>"></i>
        </div>
        <div class="customer-notice__content">
            <span class="customer-notice__title"><?= esc($noticeTitles[$noticeType]) ?></span>
            <div class="customer-notice__message"><?= nl2br(esc($notice['message'])) ?></div>
        </div>
        <button type="button" class="customer-notice__close" aria-label="Cerrar aviso">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-customer-notice-id]').forEach((notice) => {
                const noticeId = notice.dataset.customerNoticeId
                const storageKey = `customer_notice_closed_${noticeId}`

                if (sessionStorage.getItem(storageKey) === '1') {
                    notice.remove()
                    return
                }

                notice.querySelector('.customer-notice__close')?.addEventListener('click', () => {
                    sessionStorage.setItem(storageKey, '1')
                    notice.remove()
                })
            })
        })
    </script>
<?php endif; ?>
