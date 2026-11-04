# Deployment

TaskFlow runs for free on [Render](https://render.com) (web), [Neon](https://neon.com) (PostgreSQL) and [Pusher](https://pusher.com) (live updates). The reasons are in [ADR-0011](adr/0011-free-production-hosting.md). The live instance is at <https://taskflow-d561.onrender.com>.

| Piece       | Where                                                                                                             |
| ----------- | ----------------------------------------------------------------------------------------------------------------- |
| Image       | [`Dockerfile`](../Dockerfile): FrankenPHP with built assets, running as a non-root user                           |
| First start | [`docker/start.sh`](../docker/start.sh): caches, migrations and the demo workspace, on every start                |
| Service     | [`render.yaml`](../render.yaml): the settings that are not secrets                                                |
| Check       | [`docker/smoke-test.sh`](../docker/smoke-test.sh): opens the demo in the real image, in CI, on every pull request |

## First deployment

Use the **same region** everywhere, because each page makes several database queries: Frankfurt on Render, `aws-eu-central-1` on Neon and `eu` on Pusher.

1. **Neon:** create a project. Copy the **direct** connection string, which has no `-pooler` in its host. The pooler runs in transaction mode and drops the `SET` commands that Laravel sends when it connects.
2. **Application key:** run `php artisan key:generate --show` on your machine and copy the result, including `base64:`.
3. **Render:** choose **New → Blueprint**, select the repository, and fill in the values it asks for:

    | Variable                                                                     | Value                           |
    | ---------------------------------------------------------------------------- | ------------------------------- |
    | `APP_KEY`                                                                    | The result of step 2            |
    | `DB_URL`                                                                     | The connection string of step 1 |
    | `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` | **App Keys** of your Pusher app |

4. **Check it:** open the address that Render gives you and click **Try the live demo**. Then open a private window and join as Sam: each one should see the avatar of the other, and every change should arrive without reloading.

Render deploys `main` after CI passes. `APP_URL` comes from Render's `RENDER_EXTERNAL_URL`.

## When a deploy fails

`docker/start.sh` stops at the first step that fails, and Render keeps the previous version running. Open the failed deploy in Render and read the end of its log: the first error names the value to fix. These are the errors that a mistyped value causes, and every one of them has happened or been reproduced with this image:

| The log says                                                          | Cause                                                              | Fix                                                   |
| --------------------------------------------------------------------- | ------------------------------------------------------------------ | ----------------------------------------------------- |
| `Unsupported driver [...]`                                            | `DB_URL` does not start with `postgresql://`                       | Clear the field and paste the connection string again |
| `could not translate host name`                                       | `DB_URL` is cut short, or holds two strings pasted into each other | Clear the field and paste the connection string once  |
| `password authentication failed`                                      | The Neon password was reset after the string was copied            | Copy the current string from Neon's **Connect**       |
| `Failed to create broadcaster for connection "pusher"` … `null given` | A `PUSHER_*` value is missing: `render.yaml` turns live updates on | Fill in the four values from Pusher's **App Keys**    |

Fix the value in **Environment** and deploy again.

Some database errors print the whole connection string, password included. Check a log before sharing it, and if a password leaks, reset it in Neon (**Roles → Reset password**) and paste the new string into Render.

## Optional settings

| Setting      | How                                                                                                                                                                          |
| ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GitHub login | Create a production OAuth App with the callback `https://<your-address>/auth/github/callback`, then set `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET` and `GITHUB_REDIRECT_URI` |
| Registration | Set `REGISTRATION_ENABLED=true`, only with an email provider that has an HTTP API: Render blocks SMTP on the free plan, and new accounts have to verify their email          |
| Own domain   | Add it on Render, then set `APP_URL` so that it wins over Render's address                                                                                                   |

## What to expect from the free plans

- **Cold starts.** Render stops the instance after 15 idle minutes, and the next visitor waits about a minute on a loading page. Neon also sleeps after 5 idle minutes.
- **Resources.** Measured locally with the limits of the free plan (512 MB and 0.1 CPU) and a local database: the image starts in about 14 seconds, uses about 50 MB of memory, and answers pages in 0.03 to 0.3 seconds. On the live site, measured from Spain once awake, a board opens in 0.25 to 0.4 seconds and a change reaches the other window in under a second.
- **Demo resets.** Each start puts the demo workspace back to its original state, so damage from visitors does not last. If someone deletes it all, the next click on the demo rebuilds it.
- **Quotas.** Render gives 750 instance hours per month, and Neon 100 compute hours. Stopping when idle keeps both well under the limit.
- **No shell.** Render offers no console on the free plan, so everything that must run on a deploy lives in `docker/start.sh`.

## Run the production image locally

```bash
docker build --tag taskflow:local .
docker run --rm --publish 10000:10000 --env-file docker/smoke.env \
    --env APP_KEY="base64:$(openssl rand -base64 32)" \
    --env DB_URL=postgresql://taskflow:password@host.docker.internal:5433/taskflow_test \
    taskflow:local
sh docker/smoke-test.sh
```
