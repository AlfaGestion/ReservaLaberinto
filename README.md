# ReservaLaberinto

Aplicacion web de reservas para Laberinto Patagonia, desarrollada sobre CodeIgniter 4. Permite tomar reservas desde una interfaz publica, administrarlas desde un panel interno, registrar pagos con Mercado Pago, generar comprobantes PDF y enviar emails de confirmacion.

## Funcionalidades

- Reserva publica con validacion de cliente por telefono y email.
- Alta de clientes nuevos.
- Consulta de disponibilidad por fecha y horario.
- Control de dias cerrados y minimo de visitantes.
- Calculo automatico de importes por servicio y tipo de institucion.
- Pago parcial o total con Mercado Pago.
- Bloqueo temporal de turnos para evitar dobles reservas.
- Consulta publica de reservas por codigo o por telefono + email.
- Panel administrativo para reservas, clientes, valores, horarios, reportes y configuracion general.
- Reenvio manual de emails desde el panel.
- Generacion de PDF de reservas y reportes.

## Stack

- PHP 7.4+.
- CodeIgniter 4.
- MySQL / MariaDB.
- SDK `mercadopago/dx-php` 2.5.3.
- PHPUnit para pruebas.

## Estructura principal

- [app/Controllers](/c:/app/ReservaLaberinto/app/Controllers): logica de reservas, clientes, admin, pagos y autenticacion.
- [app/Models](/c:/app/ReservaLaberinto/app/Models): acceso a datos.
- [app/Views](/c:/app/ReservaLaberinto/app/Views): vistas publicas, administrativas, Mercado Pago y PDFs.
- [app/Database/Migrations](/c:/app/ReservaLaberinto/app/Database/Migrations): estructura versionada de base de datos.
- [public/assets/js](/c:/app/ReservaLaberinto/public/assets/js): comportamiento del frontend.
- [DOC](/c:/app/ReservaLaberinto/DOC): notas tecnicas y scripts SQL de apoyo.

## Requisitos

- PHP con extensiones `intl`, `mbstring`, `mysqli`, `curl` y `json`.
- Composer.
- MySQL o MariaDB.
- Un web server apuntando a [public](/c:/app/ReservaLaberinto/public) o el servidor embebido de CodeIgniter.

## Puesta en marcha

1. Instalar dependencias:

```bash
composer install
```

2. Revisar `.env` y la configuracion base de la app.

3. Configurar base de datos.
   La configuracion por defecto esta en [app/Config/Database.php](/c:/app/ReservaLaberinto/app/Config/Database.php). El proyecto hoy apunta a una base llamada `reserva-canchas`, pero en la documentacion funcional tambien aparece `Reserva_Laberinto`. Verificar el nombre correcto antes de levantar el entorno.

4. Crear la estructura de tablas.
   Se puede usar una de estas opciones:

- Ejecutar migraciones:

```bash
php spark migrate
```

- Importar alguno de los SQL incluidos en el repo si se necesita una base de referencia:
  [webreser_labepat.sql](/c:/app/ReservaLaberinto/webreser_labepat.sql),
  [backup_pre_encoding_fix.sql](/c:/app/ReservaLaberinto/backup_pre_encoding_fix.sql),
  [DOC/actualizacion_base_real_2026-03-30.sql](/c:/app/ReservaLaberinto/DOC/actualizacion_base_real_2026-03-30.sql).

5. Levantar el proyecto:

```bash
php spark serve
```

## Modulos principales

### Reserva publica

La pantalla principal permite validar al cliente, elegir fecha, horario, servicio y cantidad de visitantes, calcular el total y avanzar al pago.

Archivos clave:

- [app/Controllers/Home.php](/c:/app/ReservaLaberinto/app/Controllers/Home.php)
- [app/Controllers/Bookings.php](/c:/app/ReservaLaberinto/app/Controllers/Bookings.php)
- [app/Controllers/Customers.php](/c:/app/ReservaLaberinto/app/Controllers/Customers.php)
- [app/Views/index.php](/c:/app/ReservaLaberinto/app/Views/index.php)
- [public/assets/js/formReserva.js](/c:/app/ReservaLaberinto/public/assets/js/formReserva.js)

### Mercado Pago

El flujo de pago crea una reserva provisional y un bloqueo temporal en `booking_slots`. Si el pago se aprueba, la reserva se confirma; si falla o se cancela, el turno se libera.

Archivos clave:

- [app/Controllers/MercadoPago.php](/c:/app/ReservaLaberinto/app/Controllers/MercadoPago.php)
- [app/Libraries/MercadoPagoLibrary.php](/c:/app/ReservaLaberinto/app/Libraries/MercadoPagoLibrary.php)
- [app/Models/BookingSlotsModel.php](/c:/app/ReservaLaberinto/app/Models/BookingSlotsModel.php)
- [app/Views/mercadoPago/success.php](/c:/app/ReservaLaberinto/app/Views/mercadoPago/success.php)
- [app/Views/mercadoPago/failure.php](/c:/app/ReservaLaberinto/app/Views/mercadoPago/failure.php)

### Panel administrativo

El panel autenticado permite operar reservas, clientes, valores, horarios, configuracion general, reportes y parametros de Mercado Pago.

Archivos clave:

- [app/Controllers/Superadmin.php](/c:/app/ReservaLaberinto/app/Controllers/Superadmin.php)
- [app/Controllers/Users.php](/c:/app/ReservaLaberinto/app/Controllers/Users.php)
- [app/Views/superadmin/index.php](/c:/app/ReservaLaberinto/app/Views/superadmin/index.php)
- [public/assets/js/abmSuperadmin.js](/c:/app/ReservaLaberinto/public/assets/js/abmSuperadmin.js)
- [public/assets/js/searchBookings.js](/c:/app/ReservaLaberinto/public/assets/js/searchBookings.js)
- [public/assets/js/searchReports.js](/c:/app/ReservaLaberinto/public/assets/js/searchReports.js)

## Rutas utiles

Definidas en [app/Config/Routes.php](/c:/app/ReservaLaberinto/app/Config/Routes.php).

- `GET /`: inicio publico.
- `POST /formInfo`: calculo y armado inicial de reserva.
- `POST /setPreference`: genera preferencias de pago en Mercado Pago.
- `GET /payment/success`: callback de pago aprobado.
- `GET /payment/failure`: callback de pago fallido.
- `GET /MisReservas` y `GET /MisReservas/{token}`: consulta publica de reservas.
- `GET /auth/login`: acceso al panel.
- `GET /abmAdmin`: panel administrativo autenticado.

## Base de datos

Tablas relevantes del dominio:

- `bookings`: reserva principal.
- `booking_slots`: bloqueo temporal y confirmacion de turnos.
- `customers`: clientes.
- `fields`: servicios o sectores reservables.
- `payments`: pagos registrados.
- `mercado_pago`: respuesta devuelta por MP.
- `rate`: porcentaje de sena y minimo de visitantes.
- `service_values`: valores por servicio y descuento.
- `time`: horarios y dias cerrados.
- `uploads`: logo y configuracion visual/notificaciones.
- `users`: usuarios del panel.

Migraciones recientes a tener en cuenta:

- [2026-03-30-210000_BookingSlots.php](/c:/app/ReservaLaberinto/app/Database/Migrations/2026-03-30-210000_BookingSlots.php)
- [2026-03-27-124500_AddNotificationEmailToUploads.php](/c:/app/ReservaLaberinto/app/Database/Migrations/2026-03-27-124500_AddNotificationEmailToUploads.php)
- [2026-03-27-114500_AddDiscountPercentageToServiceValues.php](/c:/app/ReservaLaberinto/app/Database/Migrations/2026-03-27-114500_AddDiscountPercentageToServiceValues.php)

## Emails y configuracion sensible

El sistema usa SMTP y cuentas de Mercado Pago. Antes de desplegar:

- mover credenciales fuera del codigo fuente;
- revisar [app/Config/Email.php](/c:/app/ReservaLaberinto/app/Config/Email.php);
- revisar `.env`, `baseURL` y configuracion de entorno;
- verificar cuentas de Gmail fallback y limites de envio;
- validar las claves de Mercado Pago segun el ambiente.

## Pruebas

Ejecutar:

```bash
composer test
```

o:

```bash
vendor\\bin\\phpunit
```

## Documentacion adicional

- [DOC/resumen_funcional_tecnico.txt](/c:/app/ReservaLaberinto/DOC/resumen_funcional_tecnico.txt)
- [DOC/schema_diff_remote_vs_local.json](/c:/app/ReservaLaberinto/DOC/schema_diff_remote_vs_local.json)
