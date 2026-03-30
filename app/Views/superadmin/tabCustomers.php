<div class="admin-toolbar">
    <div class="admin-toolbar__actions">
        <button type="button" class="btn btn-success customer-frame-trigger" data-customer-frame-url="<?= base_url('customers/register?embed=1') ?>"><i class="fa-solid fa-user-plus me-1"></i>Ingresar cliente</button>
    </div>
    <!-- <button type="button" id="setOfferTrue" class="btn btn-warning mt-2 mb-2" id=""><i class="fa-solid fa-tags me-1"></i>Ofrecer oferta a todos los clientes</button>
    <button type="button" id="setOfferFalse" class="btn btn-danger mt-2 mb-2" id=""><i class="fa-solid fa-tag me-1"></i>Quitar oferta a todos los clientes</button> -->


    <!-- <div class="form-check form-switch mt-3">
        <input class="form-check-input" type="checkbox" role="switch" id="checkCustomersWithOffer">
        <label class="form-check-label" for="checkCustomersWithOffer">Ver clientes con oferta</label>
    </div> -->

    <div class="admin-toolbar__search">
        <div class="form-floating mb-3">
            <input type="search" class="form-control" id="searchCustomerInput" placeholder="">
            <label for="searchCustomerInput">Télefono</label>
        </div>
        <button class="btn btn-primary" id="searchCustomerButton">Buscar</button>
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
                <tr id="customer-row-<?= $customer['id'] ?>">
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
                        <button type="button" class="btn btn-primary btn-sm mb-1 customer-frame-trigger" data-customer-frame-url="<?= base_url('customers/editWindow/' . $customer['id'] . '?embed=1') ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                        <a href="<?= base_url('customers/deleteCustomer/' . $customer['id']) ?>" class="btn btn-danger btn-sm mb-1" data-id="<?= $customer['id'] ?>"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="customerFrameModal" tabindex="-1" aria-labelledby="customerFrameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerFrameModalLabel">Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe
                    id="customerFrame"
                    title="Cliente"
                    src="about:blank"
                    style="width: 100%; height: 78vh; border: 0; background: #f7f3ec;"></iframe>
            </div>
        </div>
    </div>
</div>

