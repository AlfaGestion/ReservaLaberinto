<?php

use App\Models\MercadoPagoKeysModel;
use App\Models\UploadModel;

$modelUploads = new UploadModel();
$userData = $modelUploads->first();

$mpKeysModel = new MercadoPagoKeysModel();
$mpKeys = $mpKeysModel->first();

$hasBookingRequestPrefill = !empty($prefill['request_id']);
$prefillDateLabel = '';
if (!empty($prefill['date'])) {
    try {
        $prefillDateLabel = (new DateTime($prefill['date']))->format('d/m/Y');
    } catch (\Throwable $exception) {
        $prefillDateLabel = $prefill['date'];
    }
}
$prefillTimeLabel = trim((string) ($prefill['time_from'] ?? ''));
if (!empty($prefill['time_until'])) {
    $prefillTimeLabel = $prefillTimeLabel !== '' ? $prefillTimeLabel . ' a ' . $prefill['time_until'] : $prefill['time_until'];
}

?>

<?php echo $this->extend('templates/dashboard') ?>



<?php echo $this->section('content') ?>

<style>
    .booking-prefill-pending {
        display: none;
    }
</style>
<div class="container">
    <div
        id="bookingRequestPrefill"
        class="d-none"
        data-request-id="<?= esc($prefill['request_id'] ?? '') ?>"
        data-request-token="<?= esc($prefillToken ?? '') ?>"
        data-type="<?= esc($prefill['type'] ?? '') ?>"
        data-date="<?= esc($prefill['date'] ?? '') ?>"
        data-field-id="<?= esc($prefill['field_id'] ?? '') ?>"
        data-field-name="<?= esc($prefill['field_name'] ?? '') ?>"
        data-time-from="<?= esc($prefill['time_from'] ?? '') ?>"
        data-time-until="<?= esc($prefill['time_until'] ?? '') ?>"
        data-visitors="<?= esc($prefill['visitors'] ?? '') ?>"
        data-minimum-visitors="<?= esc($prefill['minimum_visitors'] ?? '') ?>"
        data-name="<?= esc($prefill['name'] ?? '') ?>"
        data-phone="<?= esc($prefill['phone'] ?? '') ?>"
        data-email="<?= esc($prefill['email'] ?? '') ?>"
        data-dni="<?= esc($prefill['dni'] ?? '') ?>"
        data-city="<?= esc($prefill['city'] ?? '') ?>"
        data-type-institution="<?= esc($prefill['type_institution'] ?? '') ?>"></div>

    <div id="bookingPrefillLoader" class="<?= $hasBookingRequestPrefill ? '' : 'd-none' ?>">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <span class="badge rounded-pill mb-2" style="background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>; color: #fff;">Solicitud aprobada</span>
                        <h3 class="h5 mb-2">Estamos preparando tu reserva</h3>
                        <p class="text-muted mb-0">Ya cargamos los datos que elegiste. En un instante vas a ver el formulario listo para revisar y pagar.</p>
                    </div>
                    <div class="spinner-border flex-shrink-0" style="width: 3rem; height: 3rem; color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" role="status">
                        <span class="visually-hidden">Cargando reserva...</span>
                    </div>
                </div>

                <?php if ($hasBookingRequestPrefill) : ?>
                    <div class="row g-3 mt-1">
                        <?php if ($prefillDateLabel !== '') : ?>
                            <div class="col-md-4">
                                <div class="border rounded-3 h-100 p-3">
                                    <small class="text-muted d-block mb-1">Fecha</small>
                                    <strong><?= esc($prefillDateLabel) ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($prefillTimeLabel !== '') : ?>
                            <div class="col-md-4">
                                <div class="border rounded-3 h-100 p-3">
                                    <small class="text-muted d-block mb-1">Horario</small>
                                    <strong><?= esc($prefillTimeLabel) ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($prefill['field_name'])) : ?>
                            <div class="col-md-4">
                                <div class="border rounded-3 h-100 p-3">
                                    <small class="text-muted d-block mb-1">Servicio</small>
                                    <strong><?= esc($prefill['field_name']) ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($prefill['visitors'])) : ?>
                            <div class="col-md-4">
                                <div class="border rounded-3 h-100 p-3">
                                    <small class="text-muted d-block mb-1">Visitantes</small>
                                    <strong><?= esc($prefill['visitors']) ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($prefill['name'])) : ?>
                            <div class="col-md-4">
                                <div class="border rounded-3 h-100 p-3">
                                    <small class="text-muted d-block mb-1">Titular</small>
                                    <strong><?= esc($prefill['name']) ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($prefill['phone'])) : ?>
                            <div class="col-md-4">
                                <div class="border rounded-3 h-100 p-3">
                                    <small class="text-muted d-block mb-1">Telefono</small>
                                    <strong><?= esc($prefill['phone']) ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Modal de bienvenida -->
    <div class="modal fade" data-bs-backdrop="static" id="welcomeModal" tabindex="-1" aria-labelledby="welcomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content text-start p-4">

                <div class="modal-header d-flex justify-content-center border-0 pb-2">
                    <h5 class="modal-title fw-bold text-center" id="welcomeModalLabel">TERMINOS Y CONDICIONES DE VISITA</h5>
                </div>

                <div class="modal-body p-0">
                    <div class="terms-audio-actions d-flex justify-content-center align-items-center flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary terms-audio-icon-btn" id="termsPrevButton" aria-label="Leer punto anterior" title="Anterior">
                            <i class="fa-solid fa-backward-step"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary terms-audio-icon-btn" id="toggleTermsAudio" aria-label="Reproducir o pausar lectura" title="Reproducir">
                            <i class="fa-solid fa-play"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary terms-audio-icon-btn" id="termsNextButton" aria-label="Leer punto siguiente" title="Siguiente">
                            <i class="fa-solid fa-forward-step"></i>
                        </button>
                        <select class="form-select" id="termsAudioRate" aria-label="Velocidad de lectura">
                            <option value="1" selected>x1</option>
                            <option value="1.2">x1.2</option>
                            <option value="1.5">x1.5</option>
                            <option value="2">x2</option>
                        </select>
                        <span class="terms-audio-hint">Click en un punto para leer desde ahi</span>
                    </div>
                    <div class="terms-container p-3 rounded" style="background-color: #f8f9fa; max-height: 60vh; overflow-y: auto;">

                        <div class="mb-3 d-flex flex-column align-items-center justify-content-center">
                            <h2 class="fw-bold text-center" style="color: <?= isset($userData) ? $userData['secondary_color'] : '#0064b0' ?>;">Laberinto Patagonia - Reservas Grupales</h2>
                            <p style="font-size: 0.9rem;" class="text-center">Al efectuar la reserva y el pago de una visita grupal a Laberinto Patagonia, el visitante y su grupo aceptan de manera plena, expresa e irrevocable los presentes terminos y condiciones, que rigen la experiencia dentro de nuestro espacio natural y cultural.</p>
                            <p>Leer los terminos y condiciones y marcar la casilla para comenzar la reserva.</p>
                        </div>

                        <hr class="my-3">

                        <div class="form-check d-block d-none" id="check1Div">
                            <p><strong>1/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check1">
                            <label class="form-check-label" for="check1">
                                <h6 class="fw-bold" style="font-size: 1.5em">1. Riesgos inherentes a la actividad</h6>
                                <p style="font-size: 0.9rem;">El recorrido del laberinto y del Parque se desarrolla en un entorno natural, con senderos rodeados de vegetacion y aire libre. Esta experiencia puede implicar riesgos propios de la naturaleza: caminatas en superficies irregulares, cambios climaticos repentinos, exposicion solar, viento, picaduras de insectos o eventuales tropiezos. Cada visitante asume estos riesgos y declara encontrarse en condiciones fisicas y de salud aptas para disfrutar de la actividad.</p>
                            </label>
                        </div>

                        <div class="form-check d-block d-none" id="check2Div">
                            <p><strong>2/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check2">
                            <label class="form-check-label" for="check2">
                                <h6 class="fw-bold" style="font-size: 1.5em">2. Responsabilidad del visitante</h6>
                                <p style="font-size: 0.9rem;">Cada persona es responsable de sus objetos personales. El Parque no responde por perdidas, extravios, hurtos o robos. Los adultos responsables de grupos familiares o escolares deben garantizar en todo momento la supervision de los menores. No esta permitido el ingreso con objetos peligrosos, sustancias prohibidas ni elementos que afecten la seguridad o la armonia del lugar.</p>
                            </label>
                        </div>

                        <div class="form-check d-block d-none" id="check3Div">
                            <p><strong>3/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check3">
                            <label class="form-check-label" for="check3">
                                <h6 class="fw-bold" style="font-size: 1.5em">3. Exoneracion de responsabilidad</h6>
                                <p style="font-size: 0.9rem;">El visitante y/o responsable del grupo reconoce la naturaleza recreativa y contemplativa de la actividad, y renuncia expresamente a realizar reclamos o acciones legales contra el Parque, sus propietarios o colaboradores, por accidentes, lesiones, perdidas o danos ocurridos durante la visita, salvo en los casos en que se acredite dolo o negligencia grave por parte del Parque.</p>
                            </label>
                        </div>

                        <div class="form-check d-block d-none" id="check4Div">
                            <p><strong>4/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check4">
                            <label class="form-check-label" for="check4">
                                <h6 class="fw-bold" style="font-size: 1.5em">4. Uso de instalaciones</h6>
                                <p style="font-size: 0.9rem;">El espiritu del Parque invita a disfrutar con respeto. Por ello, cada visitante se compromete a: Utilizar banos, senderos e instalaciones de manera adecuada. No danar ni retirar plantas, arboles, senaletica ni elementos que forman parte del predio. Cumplir con las indicaciones del personal del Parque, guardianes del orden y la experiencia.</p>
                            </label>
                        </div>

                        <div class="form-check d-block d-none" id="check5Div">
                            <p><strong>5/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check5">
                            <label class="form-check-label" for="check5">
                                <h6 class="fw-bold" style="font-size: 1.5em">5. Condiciones medicas</h6>
                                <p style="font-size: 0.9rem;">El Parque no se responsabiliza por descompensaciones medicas, enfermedades preexistentes ni reacciones alergicas que pudieran producirse durante la visita. Se sugiere que cada grupo cuente con un botiquin basico y cobertura medica propia.</p>
                            </label>
                        </div>

                        <div class="form-check d-block d-none" id="check6Div">
                            <p><strong>6/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check6">
                            <label class="form-check-label" for="check6">
                                <h6 class="fw-bold" style="font-size: 1.5em">6. Permanencia en el Parque</h6>
                                <p style="font-size: 0.9rem;">Para preservar la calidad de la experiencia, cada grupo dispone de un maximo de dos (2) horas de estadia, contadas desde el horario asignado en la reserva. La llegada tardia no modifica el horario de finalizacion.</p>
                            </label>
                        </div>

                        <div class="form-check d-block d-none" id="check7Div">
                            <p><strong>7/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check7">
                            <label class="form-check-label" for="check7">
                                <h6 class="fw-bold" style="font-size: 1.5em">7. Cancelaciones y reprogramaciones</h6>
                                <p style="font-size: 0.9rem;">Las reservas abonadas podran reprogramarse con un aviso minimo de siete (7) dias corridos. Si las condiciones climaticas extremas impidieran la visita, el Parque ofrecera reprogramar la fecha sin costo adicional. No habra reintegros por llegadas tardias, inasistencia o retiro anticipado.</p>
                            </label>
                        </div>

                        <div class="form-check d-block d-none" id="check8Div">
                            <p><strong>8/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check8">
                            <label class="form-check-label" for="check8">
                                <h6 class="fw-bold" style="font-size: 1.5em">8. Autorizacion de uso de imagen</h6>
                                <p style="font-size: 0.9rem;">El visitante autoriza al Parque a registrar y difundir fotografias o videos tomados durante la visita en medios institucionales, digitales o promocionales, sin derecho a compensacion economica. Quien no lo consienta debera manifestarlo por escrito previo al ingreso.</p>
                            </label>
                        </div>
                        
                        <div class="form-check d-block d-none" id="check9Div">
                            <p><strong>9/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check9">
                            <label class="form-check-label" for="check9">
                                <h6 class="fw-bold" style="font-size: 1.5em">9. Normas de convivencia</h6>
                                <p style="font-size: 0.9rem;">El Parque es un espacio de contemplacion, juego y encuentro con la naturaleza. Por ello: Se solicita mantener un comportamiento respetuoso y sereno. No se permite reproducir musica a volumen elevado ni realizar conductas que perturben la experiencia de otros visitantes. Los menores de 12 anos deberan permanecer acompanados en todo momento por un adulto responsable.</p>
                            </label>
                        </div>

                        <div class="form-check d-block d-none" id="check10Div">
                            <p><strong>10/10</strong></p>
                            <input class="form-check-input term-check" type="checkbox" id="check10">
                            <label class="form-check-label" for="check10">
                                <h6 class="fw-bold" style="font-size: 1.5em">10. Jurisdiccion y ley aplicable</h6>
                                <p style="font-size: 0.9rem;">Toda controversia derivada de la interpretacion o aplicacion de estos terminos y condiciones sera resuelta por los tribunales ordinarios con asiento en la Provincia de Chubut, Republica Argentina, con renuncia expresa a cualquier otro fuero.</p>
                            </label>
                        </div>

                        <div class="terms-stepper mt-3">
                            <div class="terms-stepper__nav d-flex align-items-center justify-content-between gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="termsStepPrev">Anterior</button>
                                <span class="terms-stepper__status" id="termsStepStatus">Punto 1 de 10</span>
                                <button type="button" class="btn btn-success" id="termsStepNext">Siguiente</button>
                            </div>
                            <div class="terms-stepper__final d-none" id="termsFinalBlock">
                                <p class="fw-bold text-center mb-3" id="termsFinalNotice" style="font-size: 0.9rem;">Confirmar la reserva y efectuar el pago constituye aceptacion plena y definitiva de todos los puntos aqui establecidos.</p>
                                <div class="terms-acceptance form-check" id="termsAcceptanceWrapper">
                                    <input class="form-check-input" type="checkbox" id="termsAccepted">
                                    <label class="form-check-label fw-semibold" for="termsAccepted">Lei y acepto los terminos y condiciones de visita.</label>
                                </div>
                            </div>
                        </div>

                        <button data-bs-target="#verifyVisitorsModal" data-bs-toggle="modal" disabled id="confirmRulesButton" type="button" class="btn d-none" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" data-bs-dismiss="modal">Siguiente</button>
                    </div>
                </div>

                <div class="d-flex flex-column justify-content-center align-items-center mb-3 mt-3 gap-2">
                    <a href="<?= base_url('MisReservas') ?>" id="showBooking" class="btn btn-outline-primary fw-bold text-center">
                        <i class="fa-solid fa-calendar-check me-2"></i>Ver mi reserva
                    </a>
                    <a href="<?= base_url('Registrarme') ?>" id="showRegister" class="btn btn-outline-secondary fw-bold text-center">
                        <i class="fa-solid fa-user-plus me-2"></i>Darse de alta
                    </a>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="static" id="verifyVisitorsModal" aria-hidden="true" aria-labelledby="welcomeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="welcomeModal">Validar datos</h5>
                </div>

                <div class="modal-body">
                    <div class="form-floating flex-nowrap mb-3">
                        <input type="number" class="form-control" name="telefono" id="inputTelefono" placeholder="Ingresa el telefono" aria-label="telefono" required>
                        <label for="inputTelefono">Telefono</label>
                    </div>
                    <div class="form-floating flex-nowrap mb-3">
                        <input type="email" class="form-control" name="inputEmail" id="inputEmail" placeholder="Ingrese el email" aria-label="email" required>
                        <label for="inputEmail">Email</label>
                    </div>

                    <div id="divMessages"></div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" id="validateDataButton" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;">Validar</button>
                    <button data-bs-dismiss="modal" class="btn btn-secondary d-none" id="closeModalValidate" style="color: #fff; background-color: <?= isset($userData) ? $userData['secondary_color'] : '#0064b0' ?>;">Comenzar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de bienvenida -->

    <!-- Modal de oferta -->
    <div class="modal fade" data-bs-backdrop="static" id="ofertaModal" tabindex="-1" aria-labelledby="ofertaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content d-flex justify-content-center align-items-center flex-column text-center" id="ofertaModalContent">

            </div>
        </div>
    </div>
    <!-- Modal de oferta -->

    <input type="text" name="publicKeyMp" id="publicKeyMp" class="form-control" value="<?= isset($mpKeys) ? $mpKeys['public_key'] : '' ?>" aria-label="date" hidden>

    <div id="isSunday" class="d-flex justify-content-center align-items-center mt-5 d-none">
        <span style="color: #fff; font-weight: bold; background-color: red; padding: 10px 10px; border-radius: 30px">Hoy el Laberinto permanecera cerrado</span>
    </div>

    <div id="formBooking" class="<?= $hasBookingRequestPrefill ? 'booking-prefill-pending' : '' ?>">
        <form action="" id="bookingForm">

            <?php if (session('msg')) : ?>
                <div class="alert alert-<?= session('msg.type') ?> alert-dismissible fade show" role="alert">
                    <small> <?= session('msg.body') ?> </small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="booking-stage booking-stage--active" id="bookingStageAvailability">
                <div class="booking-stage__header">
                    <span class="booking-stage__step">Paso 1</span>
                    <h3 class="booking-stage__title">Elegi fecha y disponibilidad</h3>
                    <p class="booking-stage__subtitle">Primero selecciona la fecha, el horario y la cantidad para ver la disponibilidad real.</p>
                </div>

                <div class="d-flex flex-column align-items-start justify-content-center">
                    <div class="form-floating mb-1 mt-3" style="width: 100%;">
                        <input type="text" name="fecha" id="fecha" class="form-control" value="" aria-label="date" placeholder="Selecciona una fecha" autocomplete="off">
                        <label for="fecha">Fecha</label>
                    </div>
                </div>

                <div class="booking-availability-inline mt-3" id="availabilityInlineWrapper">
                    <div class="booking-availability-inline__header">
                        <h4 class="booking-availability-inline__title">Disponibilidad</h4>
                        <p class="booking-availability-inline__subtitle">Primero elegi una fecha disponible y despues selecciona el horario que mejor te sirva.</p>
                    </div>
                    <div class="booking-availability-inline__content" id="availabilityInlineResult"></div>
                </div>

                <div class="alert alert-warning d-none mt-3" id="shortNoticeBookingAlert" role="alert">
                    <strong>Reserva con menos de 48 hs.</strong>
                    <span id="shortNoticeBookingMessage">Las reservas online requieren una anticipacion minima de 48 horas. Si queres consultar por una fecha cercana, completa la cantidad minima de visitantes y envia una solicitud.</span>
                </div>

                <div class="horario d-flex flex-row d-none">
                    <div class="form-floating" id="div-time-h" style="width: 100%;">
                        <select class="form-select mb-3" name="horarioDesde" id="horarioDesde" aria-label="l">
                            <option value="">Seleccionar</option>

                            <?php if (!empty($time)): ?>
                                <?php
                                $totalHours = count($time);
                                foreach ($time as $key => $hour):
                                    if ($key !== $totalHours - 1):
                                ?>
                                        <option value="<?= $hour ?>"><?= $hour ?></option>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            <?php else: ?>
                                <option value="">No hay horarios cargados</option>
                            <?php endif; ?>

                        </select>
                        <label for="horarioDesde">Horario desde</label>
                    </div>

                    <div class="form-floating  ms-4 d-none" id="div-time" style="width: 49%;">
                        <select class="form-select mb-3" name="horarioHasta" id="horarioHasta" aria-label="" disabled>
                            <option value="">Seleccionar</option>
                            <?php if ($time != null) : ?>

                                <?php foreach ($time as $hour) : ?>
                                    <option value="<?= $hour ?>"><?= $hour ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </select>
                        <label for="horarioHasta">Horario hasta</label>
                    </div>
                </div>

                <div id="divSelectCancha" class="d-flex flex-row">
                    <div class="form-floating" style="width: 50%;" id="selectServicio">
                        <select class="form-select mb-3 d-none" name="cancha" id="cancha" aria-label="Default floating label" disabled>
                            <?php foreach ($fields as $field) : ?>
                                <option selected value="<?= $field['id'] ?>"><?= $field['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="cancha">Seleccionar servicio</label>
                    </div>

                    <div class="form-floating flex-nowrap mb-3 ms-4 d-none" style="width: 50%;" id="div-qtyvisitors">
                        <input type="number" min="1" step="1" inputmode="numeric" class="form-control" name="inputqtyvisitors" id="inputqtyvisitors" value="0" aria-label="name" disabled>
                        <label for="inputqtyvisitors">Cantidad de personas</label>
                        <small class="booking-coordinator-notice d-none mt-2" id="groupCoordinatorNotice" role="status">1 persona queda como coordinador sin cargo por grupo.</small>
                    </div>
                </div>

                <div class="booking-stage__actions">
                    <button type="button" class="btn booking-stage__button booking-stage__button--secondary d-none" id="continueBookingStep">
                        Siguiente <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <div class="booking-stage d-none" id="bookingStageDetails">
                <div class="booking-stage__header">
                    <span class="booking-stage__step">Paso 2</span>
                    <h3 class="booking-stage__title">Revisa el resumen y confirma</h3>
                    <p class="booking-stage__subtitle">Con la disponibilidad ya definida, completa los datos del cliente y confirma la reserva.</p>
                </div>

                <div class="form-floating flex-nowrap mb-3 d-none" id="div-monto">
                    <input type="text" class="form-control" name="inputMonto" id="inputMonto" value="0" aria-label="name" disabled>
                    <label for="inputMonto">Monto</label>
                </div>

                <div class="form-floating flex-nowrap mb-3">
                    <input type="number" class="form-control" name="telefono" id="telefono" placeholder="Ingresa el telefono" aria-label="telefono" required>
                    <label for="telefono">Telefono</label>
                </div>

                <div class="form-floating flex-nowrap mb-3">
                    <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ingrese el nombre" aria-label="name" required>
                    <label for="nombre">Nombre</label>
                </div>
                <div class="form-floating flex-nowrap mb-3">
                    <input type="email" class="form-control" name="email" id="email" placeholder="Ingrese el email" aria-label="email" required>
                    <label for="email">Email</label>
                </div>

                <button type="button" class="btn btn-link px-0 mb-2 text-decoration-none" id="showTermsLink">
                    Ver terminos y condiciones
                </button>

                <div class="booking-stage__actions booking-stage__actions--final">
                    <button type="button" class="btn booking-stage__button booking-stage__button--ghost" id="backBookingStep">
                        <i class="fa-solid fa-arrow-left me-2"></i>Volver
                    </button>

                    <?php if (session()->logueado) : ?>
                        <button type="button" class="btn" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" id="confirmarAdminReserva">Confirmar reserva</button>
                    <?php else : ?>
                        <button type="button" class="btn" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" id="confirmarReserva">Confirmar reserva</button>
                    <?php endif; ?>

                    <button type="button" class="btn" style="color: #fff; background-color: <?= isset($userData) ? $userData['secondary_color'] : '#5a5a5a' ?>;" id="cancelarReserva">Cancelar reserva</button>
                </div>
            </div>

        </form>
    </div>

    <div class="modal fade" id="publicNoticeModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="publicNoticeTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content public-notice-modal">
                <button type="button" class="btn-close public-notice-modal__close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                    <div class="public-notice-inline" id="publicNoticeInline">
                        <div class="public-notice-inline__icon" id="publicNoticeIcon">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <div class="public-notice-inline__content">
                            <span class="public-notice-inline__title" id="publicNoticeTitle">Importante</span>
                            <span class="public-notice-inline__message" id="publicNoticeMessage"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <button type="button" class="btn public-notice-modal__button" id="publicNoticeAccept">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
    <div>
        <!-- First modal -->
        <div class="modal fade" id="modalConfirmarReserva" data-bs-backdrop="static" aria-hidden="true" aria-labelledby="confirmarReservaLabel" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="confirmarReservaLabel">Resumen reserva</h1>
                        <button type="button" id="buttonCancel" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-resume-body">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" style="color: #fff; background-color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" id="abonarReservaBoton" data-bs-toggle="modal">Abonar reserva</button>
                        <button type="button" class="btn" style="color: #fff; background-color: <?= isset($userData) ? $userData['secondary_color'] : '#0064b0' ?>;" data-bs-dismiss="modal">Volver</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Modal -->
        <div class="modal fade" id="ingresarPago" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="ingresarPagoLabel" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="ingresarPagoLabel">Ingresar pago</h1>
                        <button type="button" id="buttonCancel" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <?php if (session()->logueado) : ?>
                            <div class="mb-3">
                                <div class="form-floating flex-nowrap mb-3">
                                    <input type="text" class="form-control" name="adminBookingTotalAmount" id="adminBookingTotalAmount" placeholder="Ingrese el monto" aria-label="Amount" required>
                                    <label for="adminBookingTotalAmount">Ingresar total de la reserva</label>
                                </div>

                                <div class="form-floating flex-nowrap mb-3">
                                    <input type="text" class="form-control" name="adminBookingAmount" id="adminBookingAmount" placeholder="Ingrese el monto" aria-label="Amount" required>
                                    <label for="adminBookingAmount">Ingresar monto a abonar de la reserva</label>
                                </div>

                                <div class="form-floating mb-3">
                                    <select class="form-select" id="adminPaymentMethod" aria-label="Floating label select example" required>
                                        <option value="">Seleccionar medio de pago</option>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Transferencia">Transferencia</option>
                                        <option value="Mercado Pago">Mercado Pago</option>
                                    </select>
                                    <label for="adminPaymentMethod">Medio de pago</label>
                                </div>

                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Ingrese el motivo de la reserva" id="adminBookingDescription"></textarea>
                                    <label for="adminBookingDescription">Descripcion</label>
                                </div>
                            </div>

                        <?php else : ?>
                            <div class="mb-3">
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <small style="font-size: 0.65rem;">Importante: Lo que esta por abonar corresponde unicamente a la sena para la reserva. El saldo restante se abona al momento de concurrir al establecimiento. En caso de abonar el total de la reserva no debera realizar ningun pago adicional al momento de asistir.
                                    </small>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" name="switchPagoTotal" id="switchPagoTotal">
                                    <label class="form-check-label" for="switchPagoTotal">Pagar el total</label>
                                </div>
                                <label for="inputPagoReserva" class="form-label">A abonar</label>
                                <input type="text" class="form-control" id="inputPagoReserva" name="inputPagoReserva" placeholder="" disabled value="0" style="font-size: 1.5rem;">
                                <div id="payByEntriesToggleWrapper" class="border rounded-3 p-3 mt-3 d-none">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="payByEntriesToggle">
                                        <label class="form-check-label fw-semibold" for="payByEntriesToggle">Abonar por cantidad de entradas</label>
                                    </div>

                                    <div id="payByEntriesSection" class="d-none">
                                        <div class="form-floating mb-3">
                                            <input type="number" class="form-control" id="payByEntriesInput" min="1" step="1" value="1" placeholder="Cantidad de entradas">
                                            <label for="payByEntriesInput">Cantidad de entradas a abonar ahora</label>
                                        </div>

                                        <div class="small">
                                            <div>Total de entradas reservadas: <strong id="payByEntriesTotal">0</strong></div>
                                            <div>Total de la reserva: <strong id="payByEntriesBookingTotal">$0</strong></div>
                                            <div>Precio por entrada hoy: <strong id="payByEntriesUnitPrice">$0</strong></div>
                                            <div>Total a pagar ahora: <strong id="payByEntriesAmount">$0</strong></div>
                                            <div>Entradas pendientes: <strong id="payByEntriesPending">0</strong></div>
                                        </div>

                                        <div class="alert alert-warning mt-3 mb-0">
                                            <small id="payByEntriesHelp"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <small style="font-size: 0.65rem;"> <b>UNA VEZ EFECTUADO EL PAGO, AGUARDE EL TIEMPO ESTIPULADO POR MERCADO PAGO PARA SER REDIRECCIONADO AL SITIO. DE OTRA FORMA, EL PAGO NO SERA CONFIRMADO.</b></small>
                            </div>
                        <?php endif; ?>

                    </div>
                    <div class="modal-footer d-flex justify-contente-center align-items-center">
                        <div id="checkout-btn-parcial"></div>
                        <div id="checkout-btn-total" style="display:none;"></div>
                        <?php if (session()->logueado) : ?>
                            <button type="button" class="btn btn-primary" id="confirmBooking">Reservar</button>
                        <?php endif; ?>
                        <button type="button" class="btn" style="background-color: #5a5a5a; color: #ffffff" id="" data-bs-target="#modalConfirmarReserva" data-bs-toggle="modal">Volver</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal result -->
        <div class="modal fade" id="modalResult" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalResultLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" id="bookingResult">

                </div>
            </div>
        </div>

        <!-- Modal availability -->
        <div class="modal fade" id="modalAvailability" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalAvailabilityLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAvailabilityLabel">Disponibilidad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="availabilityResult" style="background-color: #f8f9fa; max-height: 60vh; overflow-y: auto;">

                    </div>
                </div>
            </div>
        </div>


        <!-- modal spinner -->
        <div class=" modal fade" id="modalSpinner" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalSpinnerLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered d-flex justify-content-center">

                <div class="d-flex justify-content-center align-items-center">
                    <div class="spinner-border" style="width: 4rem; height: 4rem; color: <?= isset($userData) ? $userData['main_color'] : '#0064b0' ?>;" role="status">
                        <span class="visually-hidden">Procesando reserva...</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>


<?php echo $this->endSection() ?>

<?php echo $this->section('footer') ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('scripts') ?>
<script>
    let esDomingo = <?php echo json_encode($esDomingo); ?>;
</script>
<script>
    const time = <?= json_encode((new \App\Models\TimeModel())->schedules); ?>;
</script>
<style>
    .public-notice-modal {
        position: relative;
        border: 0;
        border-radius: 24px;
        box-shadow: 0 26px 60px rgba(21, 36, 24, 0.2);
        overflow: hidden;
    }

    .public-notice-modal__close {
        position: absolute;
        top: 18px;
        right: 18px;
        z-index: 2;
        opacity: 1;
    }

    .public-notice-inline {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 10px 4px 0;
    }

    .public-notice-inline__icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #fdecee;
        color: #dc3545;
    }

    .public-notice-inline--info .public-notice-inline__icon {
        background: #eef4ff;
        color: #0d6efd;
    }

    .public-notice-inline--success .public-notice-inline__icon {
        background: #edf7f0;
        color: #198754;
    }

    .public-notice-inline__content {
        min-width: 0;
    }

    .public-notice-inline__title {
        display: block;
        font-weight: 800;
        font-size: 1.25rem;
        margin-bottom: 6px;
        color: #1e2e22;
    }

    .public-notice-inline__message {
        display: block;
        line-height: 1.6;
        font-size: 1.08rem;
        color: #556a5d;
    }

    .public-notice-modal__button {
        min-width: 220px;
        min-height: 52px;
        border-radius: 16px;
        border: 0;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #0d6a3a 0%, #157347 100%);
        box-shadow: 0 16px 28px rgba(13, 106, 58, 0.22);
    }

    .public-notice-modal__button:hover {
        color: #fff;
        background: linear-gradient(135deg, #0b5c33 0%, #12643d 100%);
    }
</style>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script src="<?= base_url(PUBLIC_FOLDER . "assets/js/formReserva.js") ?>"></script>


<?php echo $this->endSection() ?>
