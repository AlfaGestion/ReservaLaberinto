# Mercado Pago: expiracion centralizada por cron

## Comando Spark

```bash
php spark mp:expire-pending-reservations
```

## Cron sugerido (cada 5 minutos)

```bash
*/5 * * * * cd /ruta/al/proyecto && php spark mp:expire-pending-reservations >> writable/logs/mp-expiration-cron.log 2>&1
```

## Notas

- El comando usa solo `MercadoPagoReservationService::expirePendingReservations()`.
- Es idempotente: no debe duplicar pagos, emails ni anulaciones si corre varias veces.
- `Home::deleteRejected()` y `Superadmin::expireStaleMercadoPagoBookings()` quedan como auxiliares; el flujo operativo recomendado es este cron.
