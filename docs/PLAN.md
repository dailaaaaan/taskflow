# Plan de TaskFlow

> Hoja de ruta personal del proyecto (en español). Las normas técnicas están en [`CLAUDE.md`](../CLAUDE.md) y el flujo de Git en [`CONTRIBUTING.md`](../CONTRIBUTING.md).

## Progreso

| Fase                                            | Rama                 | Estado      |
| ----------------------------------------------- | -------------------- | ----------- |
| 0 · Preparación del entorno                     | —                    | ✅          |
| 1 · Esqueleto y estándares profesionales        | `chore/setup`        | 🔄 En curso |
| 2 · Base de datos y modelos                     | `feat/database`      | ⬜          |
| 3 · Autenticación (GitHub y tests de navegador) | `feat/auth`          | ⬜          |
| 4 · Equipos y roles                             | `feat/teams`         | ⬜          |
| 5 · Tableros, listas y tarjetas                 | `feat/boards`        | ⬜          |
| 6 · Drag & drop                                 | `feat/drag-and-drop` | ⬜          |
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
git switch main
git pull
```

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

| #   | Mensaje                                                    |
| --- | ---------------------------------------------------------- |
| 2.1 | `chore(db): add docker-compose with PostgreSQL`            |
| 2.2 | `test: run the test suite against PostgreSQL` (local y CI) |
| 2.3 | `feat(db): add teams and memberships with TeamRole enum`   |
| 2.4 | `feat(db): add boards, lists and cards`                    |
| 2.5 | `feat(db): add demo seeder`                                |

**✅ Hecho cuando:** `php artisan migrate:fresh --seed` funciona y se ven los datos de ejemplo.

## Fase 3 · Autenticación · `feat/auth`

| #   | Mensaje                                                      |
| --- | ------------------------------------------------------------ |
| 3.1 | `feat(auth): redirect to dashboard and customize auth pages` |
| 3.2 | `feat(auth): add GitHub login with Socialite`                |
| 3.3 | `test(browser): set up browser testing with auth journey`    |

## Fase 4 · Equipos y roles · `feat/teams`

| #   | Mensaje                                                    |
| --- | ---------------------------------------------------------- |
| 4.1 | `feat(teams): add team policy with admin and member rules` |
| 4.2 | `feat(teams): create team and list my teams`               |
| 4.3 | `feat(teams): invite members with signed invite links`     |
| 4.4 | `feat(teams): manage member roles and removal`             |
| 4.5 | `feat(teams): allow members to leave a team`               |
| 4.6 | `test(browser): add team invitation journey`               |

**✅ Hecho cuando:** A invita a B, B entra como miembro, no ve los botones de admin, y si los fuerza el servidor responde 403.

## Fase 5 · Tableros, listas y tarjetas · `feat/boards`

| #   | Mensaje                                                 |
| --- | ------------------------------------------------------- |
| 5.1 | `feat(boards): create and list team boards`             |
| 5.2 | `feat(boards): show board with lists and cards`         |
| 5.3 | `feat(lists): create, rename and delete lists`          |
| 5.4 | `feat(cards): create cards and edit details in a modal` |
| 5.5 | `feat(boards): rename and delete boards (admin only)`   |
| 5.6 | `test(browser): add board CRUD journey`                 |

## Fase 6 · Drag & drop · `feat/drag-and-drop`

| #   | Mensaje                                                 |
| --- | ------------------------------------------------------- |
| 6.1 | `feat(board): add position helper with gap rebalancing` |
| 6.2 | `feat(cards): add move card endpoint`                   |
| 6.3 | `feat(board): add pure board reducer` (+ Vitest)        |
| 6.4 | `feat(cards): drag and drop cards with dnd-kit`         |
| 6.5 | `feat(lists): reorder lists with drag and drop`         |
| 6.6 | `feat(board): add optimistic updates with rollback`     |
| 6.7 | `test(browser): add drag and drop journey`              |

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
