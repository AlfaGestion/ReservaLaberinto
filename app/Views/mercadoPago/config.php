<?php echo $this->extend('templates/dashboard_panel') ?>

<?php echo $this->section('title') ?>
<title>Panel</title>
<?php echo $this->endSection() ?>


<?php echo $this->section('content') ?>

<?php

use App\Models\MercadoPagoKeysModel;

$mpKeysModel = new MercadoPagoKeysModel();
$mpKeys = $mpKeysModel->first();

?>

<div class="container w-75 mt-5">

    <?php foreach ($errors as $error) : ?>
        <li><?= esc($error) ?></li>
    <?php endforeach ?>

    <form action="<?= base_url('configMp') ?>" method="POST">

        <?php if (session('msg')) : ?>
            <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show" role="alert">

                <?php foreach (session('msg.body') as $msg) : ?>

                    <?php if (isset($msg['userfile'])) : ?>
                        <small> <?= $msg['userfile'] ?> </small>
                    <?php else : ?>
                        <small> <?= $msg ?> </small>
                    <?php endif; ?>

                <?php endforeach; ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="form-floating flex-nowrap mb-3 me-1">
            <input type="text" class="form-control" name="publicKeyMp" id="publicKeyMp" placeholder="" value="<?= !is_null($mpKeys) ? $mpKeys['public_key'] : '' ?>" aria-label="codigo" required>
            <label for="publicKeyMp">Public Key</label>
        </div>

        <div class="form-floating flex-nowrap mb-3 me-1">
            <input type="text" class="form-control" name="accesTokenMp" id="accesTokenMp" placeholder="" value="<?= !is_null($mpKeys) ? $mpKeys['access_token'] : '' ?>" aria-label="codigo" required>
            <label for="accesTokenMp">Access Token</label>
        </div>

        <div class="d-flex justify-content-center align-items-center ">
            <button type="submit" id="configKeysMp" class="btn btn-primary">Actualizar</button>
        </div>

    </form>

</div>

<?php echo $this->endSection() ?>

<?php echo $this->section('footer') ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('scripts') ?>
<script>
    window.appBaseUrl = <?= json_encode(rtrim(site_url('/'), '/') . '/') ?>;
</script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/config.js") ?>"></script>

<?php echo $this->endSection() ?>
