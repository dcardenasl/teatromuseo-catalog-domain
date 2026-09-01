# Referencia rápida para agentes — `teatromuseo-catalog-domain`

Lee `CLAUDE.md` y `TASKS.md` antes de editar. Este dominio usa el puerto
`8191`, posee los datos del catálogo y delega autenticación e IAM al Hub en
`8180`.

```bash
php spark serve --port 8191
php spark migrate
php spark domain:sync-permissions

bash vendor/bin/make-crud.sh ResourceName Catalog 'field:type:rules,...' yes [route]
php spark module:check ResourceName --domain Catalog
php spark swagger:generate

composer test:unit
composer test:integration
composer test:feature
composer quality
composer cs-fix
```

La sincronización normal usa el `X-App-Key` de este dominio y es idempotente.
`--admin-token` solo se necesita para espejado o asignación opcionales. Reinicia
el servidor después de agregar rutas.

## Reglas

- `DomainAuthFilter`/`domainauth` y `HubClient` gestionan las rutas protegidas;
  nunca emitas JWT ni llames al Hub directamente desde controladores.
- Las rutas públicas `/api/v1/public/catalog/*` usan `webappkey`, no JWT de
  usuario.
- Mantén traducciones y slugs públicos por locale dentro de servicios.
- Usa DTOs, servicios/repositorios, constantes de permisos y tests en cada
  cambio. Los permisos usan `.` y no `:`.
- No hagas commit de `.env`, tokens, credenciales ni lógica de negocio en
  vistas.
