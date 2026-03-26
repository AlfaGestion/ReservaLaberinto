<div id="generalButtons" class="mt-3">
    <a type="button" href="<?= base_url('customers/register') ?>" class="btn btn-success mt-2 mb-2" id=""><i class="fa-solid fa-user-plus me-1"></i>Ingresar cliente</a>
    <!-- <button type="button" id="setOfferTrue" class="btn btn-warning mt-2 mb-2" id=""><i class="fa-solid fa-tags me-1"></i>Ofrecer oferta a todos los clientes</button>
    <button type="button" id="setOfferFalse" class="btn btn-danger mt-2 mb-2" id=""><i class="fa-solid fa-tag me-1"></i>Quitar oferta a todos los clientes</button> -->


    <!-- <div class="form-check form-switch mt-3">
        <input class="form-check-input" type="checkbox" role="switch" id="checkCustomersWithOffer">
        <label class="form-check-label" for="checkCustomersWithOffer">Ver clientes con oferta</label>
    </div> -->

    <div class="d-flex justify-content-center align-items-center flex-row">
        <div class="form-floating mb-3">
            <input type="search" class="form-control" id="searchCustomerInput" placeholder="">
            <label for="searchCustomerInput">Télefono</label>
        </div>
        <button class="btn btn-primary ms-2" id="searchCustomerButton">Buscar</button>
    </div>

</div>

<div class="table-responsive-sm" id="tableCustomers">
    <table class="table align-middle table-striped-columns mt-2">
        <thead>
            <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Tipo</th>
                <th scope="col">CUIT/CUIL</th>
                <th scope="col">Teléfono</th>
                <th scope="col">Localidad</th>
                <!-- <th scope="col">Oferta</th> -->
                <th scope="col">Email</th>
                <th scope="col">Reservas</th>
                <th scope="col">Dto</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody id="customersDiv">
            <?php foreach ($customers as $customer) : ?>
                <tr>
                    <td><?= isset($customer['name']) ? $customer['name'] : 'No indicado' ?></td>
                    <td><?= isset($customer['type_institution']) ? $customer['type_institution'] : 'No indicado' ?></td>
                    <td><?= isset($customer['dni']) ? $customer['dni'] : 'No indicado' ?></td>
                    <?php if (isset($customer['complete_phone']) && !empty($customer['complete_phone'])) { ?>
                        <td>
                            <a href="https://wa.me/+54<?= $customer['complete_phone'] ?>" target="_blank">
                                <?= $customer['complete_phone'] ?>
                            </a>
                        </td>
                    <?php } else { ?>
                        <td>
                            No indicado
                        </td>
                    <?php } ?>
                    <td><?= isset($customer['city']) ? $customer['city'] : 'No indicado' ?></td>
                    <!-- <td><input class="form-check-input offerCheckbox" type="checkbox" data-id="<?= $customer['id'] ?>" role="switch" id="offerCheckbox<?= $customer['id'] ?>" <?= $customer['offer'] ? 'checked' : '' ?>></td> -->
                    <?php if (isset($customer['email']) && !empty($customer['email'])) { ?>
                        <td>
                            <a href="mailto:<?= $customer['email'] ?>" target="_blank">
                                <?= $customer['email'] ?>
                            </a>
                        </td>
                    <?php } else { ?>
                        <td>
                            No indicado
                        </td>
                    <?php } ?>
                    <td><?= isset($customer['quantity']) ? $customer['quantity'] : '0' ?></td>
                    <td><?= isset($customer['offer']) ? $customer['offer'] : '0' ?>%</td>
                    <td>
                        <a href="<?= base_url('customers/editWindow/' . $customer['id']) ?>" class="btn btn-primary btn-sm mb-1"><i class="fa-solid fa-pen-to-square"></i></a>
                        <a href="<?= base_url('customers/deleteCustomer/' . $customer['id']) ?>" class="btn btn-danger btn-sm mb-1" data-id="<?= $customer['id'] ?>"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>