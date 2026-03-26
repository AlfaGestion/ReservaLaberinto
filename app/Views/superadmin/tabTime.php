<!-- horarios -->
<div class="openingTime mt-2" id="openingTime">
    <form action="<?= base_url('saveTime') ?>" method="POST">

        <h5 class="mb-2 mt-2">Configurar horarios de apertura</h5>

        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="switchMonday" id="switchMonday"
                <?= (!empty($time) && isset($time['is_monday']) && $time['is_monday']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="switchMonday">Cerrar los lunes</label>
        </div>

        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="switchTuesday" id="switchTuesday"
                <?= (!empty($time) && isset($time['is_tuesday']) && $time['is_tuesday']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="switchTuesday">Cerrar los martes</label>
        </div>

        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="switchWednesday" id="switchWednesday"
                <?= (!empty($time) && isset($time['is_wednesday']) && $time['is_wednesday']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="switchWednesday">Cerrar los miércoles</label>
        </div>

        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="switchThursday" id="switchThursday"
                <?= (!empty($time) && isset($time['is_thursday']) && $time['is_thursday']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="switchThursday">Cerrar los jueves</label>
        </div>

        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="switchFriday" id="switchFriday"
                <?= (!empty($time) && isset($time['is_friday']) && $time['is_friday']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="switchFriday">Cerrar los viernes</label>
        </div>

        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="switchSaturday" id="switchSaturday"
                <?= (!empty($time) && isset($time['is_saturday']) && $time['is_saturday']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="switchSaturday">Cerrar los sábados</label>
        </div>

        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="switchSunday" id="switchSunday"
                <?= (!empty($time) && isset($time['is_sunday']) && $time['is_sunday']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="switchSunday">Cerrar los domingos</label>
        </div>


        <div class="d-flex flex-row nowrap justify-content-start align-items-center gap-2">
            <div class="form-floating mt-3">
                <select class="form-select" id="changeTimeFrom" name="from" aria-label="Floating label select example">
                    <option value="">Seleccionar</option>
                    <?php
                    $hora_inicio = "07:00";
                    $hora_fin = "23:30";
                    $intervalo = 30; // minutos
                    $hora = strtotime($hora_inicio);
                    $fin = strtotime($hora_fin);

                    while ($hora <= $fin) {
                        $hora_str = date("H:i", $hora);
                        $selected = ($time['from'] == $hora_str) ? "selected" : "";
                        echo "<option value=\"$hora_str\" $selected>$hora_str</option>";
                        $hora = strtotime("+$intervalo minutes", $hora);
                    }
                    ?>
                </select>

                <label for="selectEditFields">Apertura</label>
            </div>

            <div class="form-floating mt-3">
                <select class="form-select" id="changeTimeUntil" name="until" aria-label="Floating label select example">
                    <option value="">Seleccionar</option>
                    <?php
                    $hora_inicio = "07:00";
                    $hora_fin = "23:30";
                    $intervalo = 30; // minutos
                    $hora = strtotime($hora_inicio);
                    $fin = strtotime($hora_fin);

                    while ($hora <= $fin) {
                        $hora_str = date("H:i", $hora);
                        $selected = ($time['until'] == $hora_str) ? "selected" : "";
                        echo "<option value=\"$hora_str\" $selected>$hora_str</option>";
                        $hora = strtotime("+$intervalo minutes", $hora);
                    }
                    ?>
                </select>

                <label for="selectEditFields">Cierre</label>
            </div>
        </div>

        <!-- <h5 class="mb-2 mt-2">Configurar inicio de horario nocturno</h5>

        <div class="form-floating mt-3">
            <select class="form-select" style="width: 10%;" id="horarioNocturno" name="horarioNocturno" aria-label="Floating label select example">
                <option value="">Seleccionar</option>
                <?php
                $hora_inicio = "07:00";
                $hora_fin = "23:30";
                $intervalo = 30; // minutos
                $hora = strtotime($hora_inicio);
                $fin = strtotime($hora_fin);

                while ($hora <= $fin) {
                    $hora_str = date("H:i", $hora);
                    $selected = ($time['nocturnal_time'] == $hora_str) ? "selected" : "";
                    echo "<option value=\"$hora_str\" $selected>$hora_str</option>";
                    $hora = strtotime("+$intervalo minutes", $hora);
                }
                ?>
            </select>

            <label for="selectEditFields">Horario nocturno</label>
        </div> -->

        <button type="submit" class="btn btn-success mt-2">Guardar</button>
        <a href="<?= base_url() ?>" type="button" class="btn btn-danger mt-2">Cancelar</a>
    </form>
</div>