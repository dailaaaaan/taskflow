import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

async function signIn(page: Page): Promise<void> {
    await page.goto('/login');
    await page.locator('[name=email]').fill('demo@taskflow.test');
    await page.locator('[name=password]').fill('password');
    await page.getByTestId('login-button').click();
    await expect(
        page.getByRole('link', { name: 'Acme Product Team' }),
    ).toBeVisible();
}

test('shows a skeleton while a slow page is on its way', async ({ page }) => {
    await signIn(page);

    // Holding the answer makes the slow visit deterministic, with no sleeps.
    let answer = () => {};
    const answered = new Promise<void>((resolve) => (answer = resolve));

    await page.route('**/teams/*', async (route) => {
        await answered;
        await route.continue();
    });

    await page.getByRole('link', { name: 'Acme Product Team' }).click();
    await expect(page.getByTestId('page-skeleton')).toBeVisible();

    answer();

    await expect(
        page.getByRole('heading', { name: 'Acme Product Team', level: 1 }),
    ).toBeVisible();
    await expect(page.getByTestId('page-skeleton')).toBeHidden();
});

test('keeps the page and explains why when the server cannot be reached', async ({
    page,
}) => {
    await signIn(page);
    await page.route('**/teams/*', (route) => route.abort());

    await page.getByRole('link', { name: 'Acme Product Team' }).click();

    await expect(page.getByText('could not be reached')).toBeVisible();
    await expect(
        page.getByRole('heading', { name: 'Your teams' }),
    ).toBeVisible();
    await expect(page.getByTestId('page-skeleton')).toBeHidden();
});
