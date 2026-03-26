<?php echo $this->extend('templates/dashboard_panel') ?>

<?php echo $this->section('title') ?>
<title>Panel</title>
<?php echo $this->endSection() ?>


<?php echo $this->section('content') ?>

<div class="container d-flex align-items-center justify-content-center mt-5">

    <?php foreach ($errors as $error) : ?>
        <li><?= esc($error) ?></li>
    <?php endforeach ?>

    <form action="<?= base_url('upload/upload') ?>" method="POST" enctype="multipart/form-data">

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

        <!-- <a href="<?= base_url('deleteBackground') ?>" type="button" id="deleteBg" class="btn mt-2 mb-2" id="">Eliminar fondo actual</a> -->

        <div id="formUpload">
            <input type="file" name="userfile" size="20" class="form-control">
            <label for="userfile">Seleccione un archivo o arrastre la imagen dentro de la línea punteada</label>
        </div>

        <br><br>

        <div class="uploadButtons d-flex justify-content-center align-items-center ">
            <input type="submit" value="Subir" style="color: #fff; background-color: <?= isset($userData) ? $userData['secondary_color'] : '#0064b0' ?>;" class="form-control">
        </div>

    </form>

</div>

<?php echo $this->endSection() ?>

<?php echo $this->section('footer') ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('scripts') ?>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/abmSuperadmin.js") ?>"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/searchReports.js") ?>"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/searchBookings.js") ?>"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/customers.js") ?>"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/editReserva.js") ?>"></script>


<?php echo $this->endSection() ?>