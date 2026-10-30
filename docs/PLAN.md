# Plan de TaskFlow

> Hoja de ruta personal del proyecto (en español). Las normas técnicas están en [`CLAUDE.md`](../CLAUDE.md) y el flujo de Git en [`CONTRIBUTING.md`](../CONTRIBUTING.md).

## Progreso

| Fase                                            | Rama                 | Estado      |
| ----------------------------------------------- | -------------------- | ----------- |
| 0 · Preparación del entorno                     | —                    | ✅          |
| 1 · Esqueleto y estándares profesionales        | `chore/setup`        | ✅ v0.1.0   |
| 2 · Base de datos y modelos                     | `feat/database`      | ✅ v0.2.0   |
| 3 · Autenticación (GitHub y tests de navegador) | `feat/auth`          | ✅ v0.3.0   |
| 4 · Equipos y roles                             | `feat/teams`         | ✅ v0.4.0   |
| 5 · Tableros, listas y tarjetas                 | `feat/boards`        | ✅ v0.5.0   |
| 6 · Drag & drop                                 | `feat/drag-and-drop` | 🔄 En curso |
| 7 · Tiempo real                                 | `feat/realtime`      | ⬜          |
| 8 · Pulido de UI y accesibilidad                | `feat/polish`        | ⬜          |
| 9 · Despliegue                                  | `chore/deploy`       | ⬜          |
| 10 · README final de portfolio                  | `docs/readme`        | ⬜          |

## Decisiones tomadas

| Tema              | Decisión                                                                                      |
| ----------------- | --------------------------------------------------------------------------------------------- |
| Arquitectura      | Monolito Laravel con MVC + Inertia + React ([ADR-0001](adr/0001-laravel-inertia-monolith.md)) |
| Quién programa    | Claude escribe el código. Tú revisas, pruebas, haces commits, push y PR                       |
| Idioma            | Interfaz, código, commits y documentación en inglés. Este plan, en español                    |
| Login             | Fortify del kit (email, 2FA, passkeys) + GitHub (Socialite)                                   |
| Tiempo real       | Pusher, plan gratuito ([ADR-0002](adr/0002-pusher-for-realtime.md))                           |
| Orden de tarjetas | Posiciones enteras con huecos ([ADR-0003](adr/0003-integer-gap-positions.md))                 |
| Capas             | Actions + Policies ([ADR-0004](adr/0004-actions-and-policies.md))                             |
| Hosting (gratis)  | Render (web) + Neon (PostgreSQL) + Pusher. Se confirma en la Fase 9                           |

## Rutina de cada fase

```powershell
# EMPEZAR
git switch main
git pull
git switch -c <rama-de-la-fase>

# CADA COMMIT (Claude te da la lista exacta de archivos)
git add <archivos>
git commit -m "<mensaje>"

# TERMINAR
git push -u origin <rama-de-la-fase>
gh pr create --base main --title "<título>" --body-file <archivo que te da Claude>
gh pr checks --watch            # espera a que el CI termine
gh pr merge --merge --delete-branch
gh pr view --json state -q .state   # tiene que decir MERGED antes de seguir
git switch main
git pull
gh release create v0.X.0 --target main --title "<título>" --notes-file <archivo que te da Claude>
```

> ⚠️ `gh pr checks --watch` termina cuando acaba el CI, **no** cuando se fusiona el PR. Crea la release solo después de ver `MERGED`: si no, la etiqueta apunta al `main` anterior (pasó con la v0.5.0).

---

## Fase 1 · Esqueleto y estándares profesionales · `chore/setup`

| #    | Mensaje                                                            | Qué incluye                                            |
| ---- | ------------------------------------------------------------------ | ------------------------------------------------------ |
| 1.1  | `chore: initialize Laravel project with React starter kit`         | Proyecto base (en `main`) ✅                           |
| 1.2  | `refactor: satisfy PHPStan level 8 and raise analysis level`       | Larastan 7 → 8, arreglos de tipos con `#[CurrentUser]` |
| 1.3  | `chore: rename project to TaskFlow and raise PHPStan memory limit` | Nombre del proyecto y memoria de Larastan              |
| 1.4  | `chore: enable Eloquent strict mode outside production`            | Errores visibles para N+1 y atributos mal escritos     |
| 1.5  | `test: add architecture tests`                                     | 9 reglas de arquitectura                               |
| 1.6  | `chore: add git hooks with Husky, lint-staged and commitlint`      | Formateo automático y validación del mensaje de commit |
| 1.7  | `ci: add quality, test report and commit lint jobs`                | CI profesional                                         |
| 1.8  | `chore: enable Dependabot for Composer and npm`                    | Actualizaciones automáticas de dependencias            |
| 1.9  | `docs: add CLAUDE.md with AI collaboration guidelines`             | Contrato de trabajo con la IA                          |
| 1.10 | `docs: add README, license, changelog and security policy`         | Documentación base                                     |
| 1.11 | `docs: add contributing guide and GitHub templates`                | Plantillas de PR e issues, CODEOWNERS                  |
| 1.12 | `docs: add project plan, testing strategy and ADRs`                | Esta carpeta `docs/`                                   |

**✅ Hecho cuando:** el PR está fusionado con el CI en verde.

**🎓 Qué tienes que saber explicar**

- Qué es un **starter kit** y qué trae: Fortify para el login, Inertia y React.
- Qué hacen **Larastan** (encuentra errores de tipos sin ejecutar el código) y **Pint** (formatea el código PHP).
- Qué es el **strict mode** de Eloquent y qué es un problema **N+1**.
- Qué son los **tests de arquitectura**: tests que vigilan que cada clase está en su capa.
- Qué son los **hooks de Git** y para qué sirven **Conventional Commits** (commitlint).
- Qué hace cada job del **CI**: quality, tests y commits.
- Qué es un **ADR** y por qué se documentan las decisiones.

---

## Fase 2 · Base de datos y modelos · `feat/database`

Issue: [#7](https://github.com/dailaaaaan/taskflow/issues/7) · Milestone: v0.2.0

| #   | Mensaje                                                          | Qué incluye                                                                          |
| --- | ---------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| 2.1 | `build: sync package-lock.json with package name`                | Lockfile con el nombre `taskflow`                                                    |
| 2.2 | `build(db): use PostgreSQL in development, tests and CI`         | docker-compose (puerto configurable), base de datos de test, servicio Postgres en CI |
| 2.3 | `feat(db): add domain schema for teams, boards, lists and cards` | Enum `TeamRole`, 5 migraciones, 5 modelos, factories y 10 tests                      |
| 2.4 | `feat(db): add demo seeder`                                      | Cuenta demo, equipo, 2 tableros con listas y tarjetas, y 2 tests                     |
| 2.5 | `docs: document PostgreSQL setup and prepare v0.2.0`             | README, CLAUDE.md, CONTRIBUTING, TESTING, PLAN, CHANGELOG                            |

**✅ Hecho cuando:** `php artisan migrate:fresh --seed` funciona y se ven los datos de ejemplo.

**🎓 Qué tienes que saber explicar**

- Qué es una **migración** y por qué no se edita una que ya está en `main`: se crea otra nueva.
- **Claves foráneas** y sus acciones: `cascadeOnDelete` (al borrar un equipo se borran sus tableros) frente a `nullOnDelete` (al borrar un usuario sus tarjetas se quedan sin asignar).
- Por qué hay **índices** en `team_id`, `user_id` y `(board_list_id, position)`: PostgreSQL no indexa las claves foráneas solo.
- Qué es una tabla **pivot** (`memberships`) y por qué lleva su propio modelo con el **rol** casteado al **enum** `TeamRole`.
- Las **relaciones** de Eloquent: `belongsTo`, `hasMany` y `belongsToMany`, con `->using()` y `->as('membership')`.
- Qué son las **factories** y los **seeders**, y por qué los tests usan factories.
- Por qué se testea contra **PostgreSQL** y no contra SQLite: es el mismo motor que producción, así que se comprueban las restricciones reales.
- Por qué la clase se llama `BoardList` y no `List`: `list` es una palabra reservada de PHP.

## Fase 3 · Autenticación · `feat/auth`

Issue: [#8](https://github.com/dailaaaaan/taskflow/issues/8) · Milestone: v0.3.0

| #   | Mensaje                                                         | Qué incluye                                                               |
| --- | --------------------------------------------------------------- | ------------------------------------------------------------------------- |
| 3.1 | `feat(ui): replace starter kit branding with the TaskFlow logo` | Logo, favicon (SVG + ICO) e icono de Apple                                |
| 3.2 | `build: add Socialite and Pest browser testing dependencies`    | Socialite, plugin de navegador de Pest y Playwright                       |
| 3.3 | `feat(auth): add GitHub login with Socialite`                   | Action, controller, migración, botón, 13 tests y corrección de `tabindex` |
| 3.4 | `test(browser): cover the authentication journey in Chromium`   | 6 tests de navegador con axe, y Chromium en CI                            |
| 3.5 | `docs: document GitHub login and prepare v0.3.0`                | ADR-0005, README, SECURITY, CLAUDE.md, TESTING, PLAN, CHANGELOG           |

**✅ Hecho cuando:** el login con GitHub funciona (o se oculta si no está configurado) y los tests de navegador pasan en el CI.

**🎓 Qué tienes que saber explicar**

- Cómo funciona **OAuth**: TaskFlow → GitHub (el usuario autoriza) → callback con un `code` → Socialite lo canjea por los datos del usuario.
- Por qué la cuenta se **vincula por email verificado** y qué es un ataque de **pre-account takeover**, y cómo lo evitamos borrando la contraseña de la cuenta no verificada.
- Por qué entrar con GitHub **no se salta el 2FA**: se reutiliza el reto de Fortify.
- Qué es un **feature flag**: sin `GITHUB_CLIENT_ID` no hay botón y las rutas devuelven 404.
- Para qué sirve **`Socialite::fake()`**: probar el flujo sin conectarse a GitHub.
- Qué es un **test de navegador** (Chromium real con Playwright) y en qué se diferencia de un feature test.
- Qué es **axe** y por qué un `tabindex` positivo es un fallo de accesibilidad.

## Fase 4 · Equipos y roles · `feat/teams`

Issue: [#9](https://github.com/dailaaaaan/taskflow/issues/9) · Milestone: v0.4.0

| #   | Mensaje                                                           | Qué incluye                                                                                                               |
| --- | ----------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| 4.1 | `feat(teams): add team policy, invite links and membership rules` | Capa de dominio: `TeamPolicy`, modelo `Invitation`, 5 Actions, invariante del último admin con bloqueo de filas, 16 tests |
| 4.2 | `feat(teams): manage teams, members and invite links`             | Capa HTTP y UI: rutas con `->can()`, controllers, Form Requests, API Resources, 4 páginas, 33 tests                       |
| 4.3 | `test(browser): cover team creation and invitation journeys`      | 3 tests de navegador con axe                                                                                              |
| 4.4 | `docs: document team roles and prepare v0.4.0`                    | ADR-0006, README, SECURITY, CLAUDE.md, TESTING, PLAN, CHANGELOG                                                           |

**✅ Hecho cuando:** A invita a B, B entra como miembro, no ve los botones de admin, y si los fuerza el servidor responde 403.

**🎓 Qué tienes que saber explicar**

- Qué es una **Policy** y cómo se prueba como **matriz rol × habilidad** con datasets de Pest.
- Por qué autorizamos en la **ruta** (`->can('update', 'team')`): se ejecuta **antes** de validar, así que quien no tiene permiso recibe 403 y no puede usar los errores de validación para averiguar nada.
- **Route model binding con scope** (`scopeBindings`): un miembro de otro equipo devuelve 404.
- Qué es un **invariante de dominio** ("siempre un admin") y por qué se usa **`lockForUpdate`** dentro de una transacción (condición de carrera entre dos admins).
- Qué es un **savepoint** (transacción anidada) y por qué hace falta en PostgreSQL cuando salta una restricción única.
- **URLs firmadas**: HMAC con `APP_KEY`, caducidad, 403 si se manipulan y 404 si se revocan. No se guarda ningún token secreto.
- **Excepciones de dominio** con `render()` y `ShouldntReport`: errores esperados que se muestran como aviso y no ensucian los logs.
- **API Resources**: deciden exactamente qué datos llegan al navegador.

## Fase 5 · Tableros, listas y tarjetas · `feat/boards`

Issue: [#10](https://github.com/dailaaaaan/taskflow/issues/10) · Milestone: v0.5.0

| #   | Mensaje                                                        | Qué incluye                                                                                                                                         |
| --- | -------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| 5.1 | `feat(boards): add board policy, positions and create actions` | Capa de dominio: `BoardPolicy`, enum `BoardColor`, `Position`, `Board::cards()`, 3 Actions, 18 tests                                                |
| 5.2 | `feat(teams): unassign the cards of members who leave a team`  | Al salir o ser expulsado de un equipo, sus tarjetas de ese equipo quedan sin asignar, 1 test                                                        |
| 5.3 | `feat(boards): manage boards, lists and cards`                 | Capa HTTP y UI: rutas anidadas con `scopeBindings`, 3 controllers, 4 Form Requests, 3 API Resources, página del tablero, modal de tarjeta, 40 tests |
| 5.4 | `test(browser): cover the board journey`                       | 3 tests de navegador con axe                                                                                                                        |
| 5.5 | `docs: document boards and prepare v0.5.0`                     | ADR-0007, README, SECURITY, CLAUDE.md, TESTING, PLAN, CHANGELOG                                                                                     |

**✅ Hecho cuando:** un miembro crea un tablero, añade listas y tarjetas y asigna una tarjeta a un compañero con fecha límite. No ve los botones de editar ni borrar el tablero, y si los fuerza el servidor responde 403.

**🎓 Qué tienes que saber explicar**

- Qué es un **agregado** y por qué listas y tarjetas se autorizan **a través del tablero** ([ADR-0007](adr/0007-authorize-lists-and-cards-through-the-board.md)): rutas anidadas y `scopeBindings`. Si alguien cambia en la URL el id de una tarjeta de otro tablero, recibe 404 (es el ataque llamado **IDOR**).
- `->can('create', [Board::class, 'team'])`: una Policy con **contexto**. Al crear todavía no existe el tablero, así que se pasa la clase y el equipo.
- Por qué existe la habilidad `collaborate` aunque hoy sea igual que `view`: si mañana hay un rol de "solo lectura", solo cambia un método.
- **Eager loading** y el problema **N+1**: `load(['team', 'lists.cards.assignee'])` hace 4 consultas tenga el tablero 3 tarjetas o 3.000. El _strict mode_ lanza una excepción si se te olvida.
- **Posiciones con huecos** (`Position::after`): por qué 1024, 2048… y no 1, 2, 3. En la Fase 6 mover una tarjeta será un único `UPDATE`.
- `HasManyThrough`: `Board::cards()` llega a las tarjetas pasando por las listas.
- Validación que depende de la ruta: `#[RouteParameter('board')]` en `rules()` y `Rule::exists('memberships', 'user_id')->where('team_id', …)`, para que solo se pueda asignar a compañeros.
- Por qué al salir de un equipo se desasignan sus tarjetas: el dato guardado tiene que seguir cumpliendo la misma regla que la validación.
- `<Form>` de Inertia con `resetOnSuccess`: el formulario de "Add a card" sigue abierto y vacío para añadir la siguiente.
- Por qué los colores de Tailwind van como **clases completas** en un objeto: Tailwind solo genera las clases que encuentra escritas literalmente en el código.

## Fase 6 · Drag & drop · `feat/drag-and-drop`

Issue: [#11](https://github.com/dailaaaaan/taskflow/issues/11) · Milestone: v0.6.0

| #   | Mensaje                                                    | Qué incluye                                                                                                                        |
| --- | ---------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| 6.1 | `feat(board): add midpoint positions with gap rebalancing` | `Position::between()` e `insert()` (renumera solo cuando no queda hueco), 24 tests unitarios                                       |
| 6.2 | `feat(board): add move endpoints for cards and lists`      | Actions `MoveCard` y `MoveList` con bloqueo de filas, 2 Form Requests, rutas, props diferidas en el tablero, 22 tests              |
| 6.3 | `build: add dnd-kit and the Vitest test script`            | Dependencias `@dnd-kit/*` y el script `npm test`                                                                                   |
| 6.4 | `feat(board): add a pure board reducer tested with Vitest` | `board-reducer.ts`, configuración de Vitest, Vitest en `composer ci:check` y en el CI, 12 tests                                    |
| 6.5 | `feat(board): drag and drop cards and lists with dnd-kit`  | Tablero ordenable con ratón, táctil y teclado, avisos para lector de pantalla, guardado optimista con rollback, 15 tests de Vitest |
| 6.6 | `test(browser): cover keyboard drag and drop and rollback` | 5 tests de navegador                                                                                                               |
| 6.7 | `docs: document drag and drop and prepare v0.6.0`          | ADR-0008, README, SECURITY, CLAUDE.md, TESTING, PLAN, CHANGELOG                                                                    |

**✅ Hecho cuando:** mueves una tarjeta a otra lista (con ratón o con teclado), recargas la página y sigue ahí. Si el servidor rechaza el movimiento, la tarjeta vuelve a su sitio y sale un aviso.

**🎓 Qué tienes que saber explicar**

- **Posiciones con huecos** ([ADR-0003](adr/0003-integer-gap-positions.md)): mover una tarjeta escribe **una sola fila** (el punto medio entre sus vecinas). Solo cuando dos vecinas quedan pegadas (1024 y 1025) se renumera la lista.
- **`lockForUpdate`** en `MoveCard`: si dos personas sueltan tarjetas en la misma lista a la vez, la segunda espera a la primera y no calculan la posición con los mismos datos.
- La convención del **índice "sin contar el elemento movido"**: el reducer de React y `Position::insert` de PHP usan la misma, por eso cliente y servidor siempre coinciden.
- **Reducer puro**: misma entrada, misma salida, sin modificar nada. Por eso se prueba con Vitest sin navegador y en la Fase 7 servirá también para los eventos en tiempo real.
- **Actualización optimista** ([ADR-0008](adr/0008-drag-and-drop-with-optimistic-inertia-visits.md)): `router.optimistic()` cambia la pantalla antes de que responda el servidor. Si falla, Inertia restaura el estado anterior y nosotros mostramos un aviso.
- **Recarga parcial** (`only: ['lists']`) y **props diferidas** (closures en el controller): tras mover, el servidor solo vuelve a consultar las listas.
- **Accesibilidad del drag & drop**: el asa (⠿) se activa con Espacio o Enter, las flechas mueven la tarjeta y los avisos describen cada paso para un lector de pantalla. Lo comprueban los tests de navegador.
- Por qué el ratón y el teclado empiezan el arrastre en sitios distintos: Enter sobre la tarjeta tiene que seguir abriéndola.
- Por qué quitamos la rotación de la tarjeta arrastrada: dnd-kit mide ese elemento para saber dónde la sueltas.

## Fase 7 · Tiempo real · `feat/realtime`

| #   | Mensaje                                                          |
| --- | ---------------------------------------------------------------- |
| 7.1 | `feat(realtime): configure Pusher broadcasting and Echo`         |
| 7.2 | `feat(realtime): authorize board presence channel by membership` |
| 7.3 | `feat(realtime): broadcast card and list events`                 |
| 7.4 | `feat(realtime): apply live events on the board`                 |
| 7.5 | `feat(realtime): show who is viewing the board`                  |
| 7.6 | `test(e2e): verify live updates between two users`               |

**✅ Hecho cuando:** con dos navegadores, lo que haces en uno aparece en el otro sin recargar.

## Fase 8 · Pulido · `feat/polish`

| #   | Mensaje                                                         |
| --- | --------------------------------------------------------------- |
| 8.1 | `feat(ui): add loading skeletons, empty states and error pages` |
| 8.2 | `feat(ui): make board responsive on mobile`                     |
| 8.3 | `fix(a11y): improve keyboard navigation and labels`             |
| 8.4 | `perf: eager load board data and add indexes`                   |
| 8.5 | `feat(ui): add landing page`                                    |

## Fase 9 · Despliegue · `chore/deploy`

| #   | Mensaje                                            |
| --- | -------------------------------------------------- |
| 9.1 | `build: add production Dockerfile`                 |
| 9.2 | `chore(deploy): add production configuration`      |
| 9.3 | `feat(db): add demo account seeder for production` |
| 9.4 | `docs: add live demo link to README`               |

## Fase 10 · README final · `docs/readme`

| #    | Mensaje                                          |
| ---- | ------------------------------------------------ |
| 10.1 | `docs: add screenshots and demo GIF`             |
| 10.2 | `docs: finalize README and changelog for v1.0.0` |

🏁 **Al final:** `git tag v1.0.0`, `git push origin v1.0.0` y una GitHub Release.

## Extras posibles

- API REST pública con Sanctum y documentación OpenAPI (reutiliza las Actions y las Policies)
- Comentarios, etiquetas, historial de actividad, búsqueda y filtros
- Monitorización de errores en producción (Sentry, plan gratuito)
- Mutation testing (`pest --mutate`) en Policies y `Position`
