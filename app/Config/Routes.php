<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
$routes->post('formInfo', 'Home::infoReserva');
$routes->get('getDataMp', 'Home::getDataMp');
$routes->get('deleteRejected', 'Home::deleteRejected');

$routes->post('setPreference', 'MercadoPago::setPreference');
$routes->post('cancelPendingMpReservation', 'MercadoPago::cancelPendingMpReservation');
$routes->post('savePreferenceIds', 'MercadoPago::savePreferenceIds');
$routes->get('payment/success', 'MercadoPago::success');
$routes->get('payment/failure', 'MercadoPago::failure');
$routes->get('pagoAprobado/(:num)', 'MercadoPago::successView/$1');
$routes->get('pagoRechazado', 'MercadoPago::failureView');

$routes->group('auth', function ($routes) {
    $routes->post('register', 'Auth::dbRegister');
    $routes->get('logOut', 'Auth::log_out');
    $routes->get('login', 'Auth::index');
    $routes->post('login', 'Auth::login');
    $routes->get('register', 'Auth::register');
});

$routes->post('saveBooking', 'Bookings::saveBooking');
$routes->get('getBookings/(:any)', 'Bookings::getBookings/$1');
$routes->get('getBooking/(:any)', 'Bookings::getBooking/$1');
$routes->get('scheduleAvailability/(:any)', 'Bookings::scheduleAvailability/$1');
$routes->get('bookingPdf/(:any)', 'Bookings::bookingPdf/$1');
$routes->post('editBooking', 'Bookings::editBooking');

$routes->get('getFields', 'Fields::getFields');
$routes->get('getField/(:any)', 'Fields::getField/$1');

$routes->get('getRate', 'Rate::getRate');

$routes->get('getOffersRate', 'Offers::getOffersRate');

$routes->get('getTime', 'Time::getTime');
$routes->get('getNocturnalTime', 'Time::getNocturnalTime');

$routes->get('customers/register', 'Customers::register');
$routes->post('customers/register', 'Customers::dbRegister');
$routes->get('getCustomer/(:any)', 'Customers::getCustomer/$1');
$routes->get('validateCustomer/(:any)/(:any)', 'Customers::validateCustomer/$1/$2');
$routes->post('validateCustomer', 'Customers::validateCustomerLookup');
$routes->get('MisReservas', 'Bookings::viewBookings');
$routes->get('MisReservas/(:segment)', 'Bookings::viewBookings/$1');
$routes->get('misreservas', 'Bookings::viewBookings');
$routes->get('misreservas/(:segment)', 'Bookings::viewBookings/$1');
$routes->get('customers/booking', 'Bookings::viewBookings');
$routes->get('customers/showCustomerBooking/(:any)', 'Bookings::showCustomerBooking/$1');
$routes->post('customers/showCustomerBookings', 'Bookings::showCustomerBookings');

$routes->get('getUser/(:any)', 'Users::getUser/$1');
$routes->post('editUser', 'Users::editUser');

$routes->get('getValue/(:any)', 'Superadmin::getValue/$1');



$routes->group('', ['filter' => 'auth'], function ($routes) {

    $routes->get('upload', 'Upload::index');
    $routes->get('uploadLogo', 'Upload::uploadLogo');
    $routes->post('upload/upload', 'Upload::upload');
    $routes->get('deleteBackground', 'Upload::deleteBackground');

    $routes->get('configMpView', 'Superadmin::configMpView');
    $routes->post('configMp', 'Superadmin::configMp');
    $routes->post('saveWebGeneral', 'Superadmin::saveWebGeneral');
    $routes->get('abmAdmin', 'Superadmin::index');
    $routes->post('saveField', 'Superadmin::saveField');
    $routes->post('saveValue', 'Superadmin::saveValue');
    $routes->post('editField/(:any)', 'Superadmin::editField/$1');
    $routes->post('disableField/(:any)', 'Superadmin::disableField/$1');
    $routes->post('getActiveBookings', 'Superadmin::getActiveBookings');
    $routes->post('getAnnulledBookings', 'Superadmin::getAnnulledBookings');
    $routes->post('resendBookingEmail/(:num)', 'Superadmin::resendBookingEmail/$1');

    $routes->post('saveTime', 'Time::saveTime');
    $routes->post('confirmMP', 'Bookings::confirmMP');

    $routes->post('completePayment/(:any)', 'Bookings::completePayment/$1');
    $routes->post('getReports', 'Bookings::getReports');
    $routes->post('getMpPayments', 'Bookings::getMpPayments');
    $routes->post('cancelBooking', 'Bookings::cancelBooking');
    $routes->post('saveAdminBooking', 'Bookings::saveAdminBooking');
    $routes->get('generateReportPdf/(:any)/(:any)/(:any)', 'Bookings::generateReportPdf/$1/$2/$3');
    $routes->get('generatePaymentsReportPdf/(:any)/(:any)', 'Bookings::generatePaymentsReportPdf/$1/$2');

    $routes->post('saveRate', 'Rate::saveRate');

    $routes->post('saveOfferRate', 'Offers::saveOfferRate');

    $routes->group('customers', function ($routes) {
        $routes->get('deleteCustomer/(:any)', 'Customers::delete/$1');
        $routes->post('editCustomer', 'Customers::edit');
        $routes->get('editWindow/(:any)', 'Customers::editWindow/$1');
        $routes->get('getCustomer/(:any)', 'Customers::getCustomer/$1');
        $routes->get('getCustomers', 'Customers::getCustomers');
        $routes->get('getCustomersWithOffer', 'Customers::getCustomersWithOffer');
        $routes->post('setOfferTrue', 'Customers::setOfferTrue');
        $routes->post('setOfferFalse', 'Customers::setOfferFalse');
    });
});


/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
